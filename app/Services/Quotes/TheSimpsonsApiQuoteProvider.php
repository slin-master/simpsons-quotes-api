<?php

namespace App\Services\Quotes;

use App\Contracts\QuoteProvider;
use App\Data\QuoteData;
use App\Data\SimpsonsCharacterCandidate;
use App\Exceptions\UpstreamQuoteProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TheSimpsonsApiQuoteProvider implements QuoteProvider
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $cdnBaseUrl,
        private readonly int $imageSize,
        private readonly int $pageMin,
        private readonly int $pageMax,
        private readonly int $timeoutSeconds,
        private readonly int $retryAttempts,
    ) {
    }

    public function randomQuote(): QuoteData
    {
        $attempts = max(1, $this->retryAttempts + 1);
        $failures = [];

        try {
            /** @var QuoteData $quote */
            $quote = retry(
                $attempts,
                fn (): QuoteData => $this->fetchQuoteAttempt($failures),
                0,
                static fn (\Throwable $exception): bool => $exception instanceof UpstreamQuoteProviderException,
            );

            return $quote;
        } catch (UpstreamQuoteProviderException $exception) {
            throw $this->wrapFailures($failures, $exception);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $failures
     */
    private function fetchQuoteAttempt(array &$failures): QuoteData
    {
        $attempt = count($failures) + 1;
        $page = random_int($this->pageMin, $this->pageMax);

        $response = $this->requestPageOrFail($page, $attempt, $failures);
        $candidates = $this->quoteCandidatesFromResponse($response);

        if ($candidates->isEmpty()) {
            $this->recordFailure($failures, [
                'attempt' => $attempt,
                'page' => $page,
                'reason' => 'no_quote_candidates',
            ]);

            throw new UpstreamQuoteProviderException(context: $failures);
        }

        /** @var SimpsonsCharacterCandidate $candidate */
        $candidate = $candidates->random();

        return $this->mapCandidateToQuoteData($candidate);
    }

    /**
     * @param  array<int, array<string, mixed>>  $failures
     */
    private function requestPageOrFail(int $page, int $attempt, array &$failures): Response
    {
        try {
            $response = $this->requestCharactersPage($page);
        } catch (ConnectionException $exception) {
            $this->recordFailure($failures, [
                'attempt' => $attempt,
                'page' => $page,
                'reason' => 'connection_exception',
                'message' => $exception->getMessage(),
            ]);

            throw new UpstreamQuoteProviderException(
                context: $failures,
                previous: $exception,
            );
        }

        if ($response->successful()) {
            return $response;
        }

        $this->recordFailure($failures, [
            'attempt' => $attempt,
            'page' => $page,
            'reason' => 'http_error',
            'status' => $response->status(),
        ]);

        throw new UpstreamQuoteProviderException(context: $failures);
    }

    /**
     * @param  array<int, array<string, mixed>>  $failures
     */
    private function wrapFailures(array $failures, UpstreamQuoteProviderException $exception): UpstreamQuoteProviderException
    {
        $context = $failures !== [] ? $failures : $exception->context();

        return new UpstreamQuoteProviderException(
            message: $this->buildFailureMessage($context),
            context: $context,
            previous: $exception->getPrevious() ?? $exception,
        );
    }

    private function requestCharactersPage(int $page): Response
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout($this->timeoutSeconds)
            ->get('/characters', [
                'page' => $page,
            ]);
    }

    /**
     * @return Collection<int, SimpsonsCharacterCandidate>
     */
    private function quoteCandidatesFromResponse(Response $response): Collection
    {
        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return collect(Arr::wrap($payload['results'] ?? []))
            ->map(function (mixed $candidate): ?SimpsonsCharacterCandidate {
                if (! is_array($candidate)) {
                    return null;
                }

                /** @var array<string, mixed> $candidate */
                return $this->mapPayloadToCandidate($candidate);
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function mapPayloadToCandidate(array $candidate): ?SimpsonsCharacterCandidate
    {
        $phrases = array_values(array_filter(
            Arr::wrap($candidate['phrases'] ?? []),
            static fn (mixed $phrase): bool => is_string($phrase) && $phrase !== '',
        ));

        if ($phrases === []) {
            return null;
        }

        return new SimpsonsCharacterCandidate(
            name: is_string($candidate['name'] ?? null) && $candidate['name'] !== ''
                ? $candidate['name']
                : 'Unknown character',
            portraitPath: is_string($candidate['portrait_path'] ?? null) ? $candidate['portrait_path'] : null,
            phrases: $phrases,
        );
    }

    private function mapCandidateToQuoteData(SimpsonsCharacterCandidate $candidate): QuoteData
    {
        /** @var string $quote */
        $quote = Arr::random($candidate->phrases);

        return new QuoteData(
            quote: $quote,
            character: $candidate->name,
            imageUrl: $this->resolveImageUrl($candidate),
            source: 'thesimpsonsapi',
        );
    }

    private function resolveImageUrl(SimpsonsCharacterCandidate $candidate): ?string
    {
        $portraitPath = $candidate->portraitPath;

        if ($portraitPath === null) {
            return null;
        }

        return Str::startsWith($portraitPath, '/')
            ? rtrim($this->cdnBaseUrl, '/').'/'.$this->imageSize.$portraitPath
            : $portraitPath;
    }

    /**
     * @param  array<int, array<string, mixed>>  $failures
     * @param  array<string, mixed>  $failure
     */
    private function recordFailure(array &$failures, array $failure): void
    {
        $failures[] = $failure;
    }

    /**
     * @param  array<int, array<string, mixed>>  $failures
     */
    private function buildFailureMessage(array $failures): string
    {
        if ($failures === []) {
            return 'Could not fetch a quote from The Simpsons API.';
        }

        $summary = array_map(
            function (array $failure): string {
                $parts = [
                    'attempt='.$this->stringifyFailureValue($failure['attempt'] ?? '?'),
                    'page='.$this->stringifyFailureValue($failure['page'] ?? '?'),
                    'reason='.$this->stringifyFailureValue($failure['reason'] ?? 'unknown'),
                ];

                if (isset($failure['status'])) {
                    $parts[] = 'status='.$this->stringifyFailureValue($failure['status']);
                }

                if (isset($failure['message'])) {
                    $parts[] = 'message='.$this->stringifyFailureValue($failure['message']);
                }

                return implode(', ', $parts);
            },
            $failures,
        );

        return 'Could not fetch a quote from The Simpsons API. '.implode(' | ', $summary);
    }

    private function stringifyFailureValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '?';
    }
}

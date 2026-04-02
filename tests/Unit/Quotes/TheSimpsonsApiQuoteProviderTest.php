<?php

namespace Tests\Unit\Quotes;

use App\Exceptions\QuoteProviderException;
use App\Services\Quotes\TheSimpsonsApiQuoteProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TheSimpsonsApiQuoteProviderTest extends TestCase
{
    #[Test]
    public function it_maps_the_remote_payload_to_quote_data(): void
    {
        Http::fake([
            'https://thesimpsonsapi.com/api/characters*' => Http::response([
                'results' => [[
                    'name' => 'Bart Simpson',
                    'portrait_path' => '/character/3.webp',
                    'phrases' => [
                        'Eat my shorts.',
                        '',
                        'Ay, caramba!',
                    ],
                ]],
            ]),
        ]);

        $provider = $this->makeProvider();

        $quote = $provider->randomQuote();

        $this->assertContains($quote->quote, ['Eat my shorts.', 'Ay, caramba!']);
        $this->assertSame('Bart Simpson', $quote->character);
        $this->assertSame('https://cdn.thesimpsonsapi.com/500/character/3.webp', $quote->imageUrl);
        $this->assertSame('thesimpsonsapi', $quote->source);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET');
    }

    #[Test]
    public function it_throws_an_exception_when_no_valid_quote_can_be_fetched(): void
    {
        Http::fake([
            'https://thesimpsonsapi.com/api/characters*' => Http::response([
                'results' => [[
                    'name' => 'Moe Szyslak',
                    'phrases' => [],
                ]],
            ], 200),
        ]);

        $provider = $this->makeProvider(retryAttempts: 1);

        $this->expectException(QuoteProviderException::class);
        $this->expectExceptionMessage('Could not fetch a quote from The Simpsons API.');
        $this->expectExceptionMessage('reason=no_quote_candidates');

        $provider->randomQuote();
    }

    #[Test]
    public function it_wraps_timeouts_as_quote_provider_failures(): void
    {
        Http::fake(function (): void {
            throw new ConnectionException('cURL error 28: Operation timed out after 5008 milliseconds with 0 bytes received');
        });

        $provider = $this->makeProvider();

        $this->expectException(QuoteProviderException::class);
        $this->expectExceptionMessage('reason=connection_exception');
        $this->expectExceptionMessage('cURL error 28: Operation timed out after 5008 milliseconds with 0 bytes received');

        $provider->randomQuote();
    }

    private function makeProvider(int $retryAttempts = 0): TheSimpsonsApiQuoteProvider
    {
        return new TheSimpsonsApiQuoteProvider(
            baseUrl: 'https://thesimpsonsapi.com/api',
            cdnBaseUrl: 'https://cdn.thesimpsonsapi.com',
            imageSize: 500,
            pageMin: 1,
            pageMax: 1,
            timeoutSeconds: 5,
            retryAttempts: $retryAttempts,
        );
    }
}

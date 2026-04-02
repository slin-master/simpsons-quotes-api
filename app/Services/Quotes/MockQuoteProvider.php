<?php

namespace App\Services\Quotes;

use App\Contracts\QuoteProvider;
use App\Data\QuoteData;
use App\Exceptions\QuoteProviderException;
use Illuminate\Support\Arr;

class MockQuoteProvider implements QuoteProvider
{
    /**
     * @var array<int, array<string, string|null>>
     */
    private array $quotes;

    public function __construct(string $datasetPath)
    {
        if (! is_file($datasetPath) || ! is_readable($datasetPath)) {
            throw new QuoteProviderException('The local quote dataset could not be loaded.');
        }

        $contents = file_get_contents($datasetPath);

        if ($contents === false) {
            throw new QuoteProviderException('The local quote dataset could not be loaded.');
        }

        /** @var array<int, array<string, string|null>>|null $quotes */
        $quotes = json_decode($contents, true);

        if (! is_array($quotes) || $quotes === []) {
            throw new QuoteProviderException('The local quote dataset is empty or invalid.');
        }

        $this->quotes = $quotes;
    }

    public function randomQuote(): QuoteData
    {
        /** @var array<string, string|null>|null $quote */
        $quote = Arr::random($this->quotes);

        if (! is_array($quote) || ! isset($quote['quote'], $quote['character'])) {
            throw new QuoteProviderException('The local quote dataset does not contain a valid quote.');
        }

        return new QuoteData(
            quote: $quote['quote'],
            character: $quote['character'],
            imageUrl: $quote['image_url'] ?? null,
            source: 'mock',
        );
    }
}

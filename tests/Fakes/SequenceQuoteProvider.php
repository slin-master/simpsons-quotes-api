<?php

namespace Tests\Fakes;

use App\Contracts\QuoteProvider;
use App\Data\QuoteData;
use RuntimeException;

class SequenceQuoteProvider implements QuoteProvider
{
    /**
     * @param  array<int, QuoteData>  $quotes
     */
    public function __construct(
        private array $quotes,
    ) {
    }

    public function randomQuote(): QuoteData
    {
        $quote = array_shift($this->quotes);

        if (! $quote instanceof QuoteData) {
            throw new RuntimeException('No more fake quotes are available.');
        }

        return $quote;
    }
}

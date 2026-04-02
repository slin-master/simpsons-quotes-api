<?php

namespace App\Services\Quotes;

use App\Contracts\QuoteProvider;
use App\Data\QuoteData;
use App\Exceptions\UpstreamQuoteProviderException;
use Illuminate\Support\Facades\Log;

class ResilientQuoteProvider implements QuoteProvider
{
    public function __construct(
        private readonly QuoteProvider $primary,
        private readonly QuoteProvider $fallback,
    ) {
    }

    public function randomQuote(): QuoteData
    {
        try {
            return $this->primary->randomQuote();
        } catch (UpstreamQuoteProviderException $exception) {
            Log::warning('Primary quote provider failed, using fallback.', [
                'exception' => $exception->getMessage(),
                'failure_context' => $exception->context(),
            ]);

            return $this->fallback->randomQuote();
        }
    }
}

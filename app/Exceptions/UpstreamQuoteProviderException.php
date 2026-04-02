<?php

namespace App\Exceptions;

class UpstreamQuoteProviderException extends QuoteProviderException
{
    /**
     * @param  array<int, array<string, mixed>>  $context
     */
    public function __construct(
        string $message = '',
        private readonly array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function context(): array
    {
        return $this->context;
    }
}

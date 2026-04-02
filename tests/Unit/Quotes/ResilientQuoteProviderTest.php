<?php

namespace Tests\Unit\Quotes;

use App\Contracts\QuoteProvider;
use App\Data\QuoteData;
use App\Exceptions\UpstreamQuoteProviderException;
use App\Services\Quotes\ResilientQuoteProvider;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ResilientQuoteProviderTest extends TestCase
{
    #[Test]
    public function it_returns_the_primary_quote_when_the_primary_provider_succeeds(): void
    {
        $primaryQuote = new QuoteData(
            quote: 'Primary quote',
            character: 'Lisa Simpson',
            source: 'primary',
        );

        $primary = new class($primaryQuote) implements QuoteProvider
        {
            public function __construct(private readonly QuoteData $quote)
            {
            }

            public function randomQuote(): QuoteData
            {
                return $this->quote;
            }
        };

        $fallback = new class implements QuoteProvider
        {
            public function randomQuote(): QuoteData
            {
                throw new RuntimeException('Fallback should not be used.');
            }
        };

        Log::shouldReceive('warning')->never();

        $provider = new ResilientQuoteProvider($primary, $fallback);

        $quote = $provider->randomQuote();

        $this->assertSame('Primary quote', $quote->quote);
        $this->assertSame('primary', $quote->source);
    }

    #[Test]
    public function it_falls_back_when_the_primary_provider_throws(): void
    {
        $primary = new class implements QuoteProvider
        {
            public function randomQuote(): QuoteData
            {
                throw new UpstreamQuoteProviderException('Primary failed.');
            }
        };

        $fallback = new class implements QuoteProvider
        {
            public function randomQuote(): QuoteData
            {
                return new QuoteData(
                    quote: 'Fallback quote',
                    character: 'Homer Simpson',
                    source: 'fallback',
                );
            }
        };

        Log::shouldReceive('warning')->once()->with('Primary quote provider failed, using fallback.', [
            'exception' => 'Primary failed.',
            'failure_context' => [],
        ]);

        $provider = new ResilientQuoteProvider($primary, $fallback);

        $quote = $provider->randomQuote();

        $this->assertSame('Fallback quote', $quote->quote);
        $this->assertSame('fallback', $quote->source);
    }

    #[Test]
    public function it_does_not_hide_unexpected_primary_provider_errors(): void
    {
        $primary = new class implements QuoteProvider
        {
            public function randomQuote(): QuoteData
            {
                throw new RuntimeException('Unexpected failure.');
            }
        };

        $fallback = new class implements QuoteProvider
        {
            public function randomQuote(): QuoteData
            {
                throw new RuntimeException('Fallback should not be used.');
            }
        };

        Log::shouldReceive('warning')->never();

        $provider = new ResilientQuoteProvider($primary, $fallback);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected failure.');

        $provider->randomQuote();
    }
}

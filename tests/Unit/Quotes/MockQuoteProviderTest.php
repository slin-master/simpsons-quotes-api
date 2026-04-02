<?php

namespace Tests\Unit\Quotes;

use App\Exceptions\QuoteProviderException;
use App\Services\Quotes\MockQuoteProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MockQuoteProviderTest extends TestCase
{
    #[Test]
    public function it_returns_a_quote_from_the_local_dataset(): void
    {
        $provider = new MockQuoteProvider(resource_path('data/mock-simpsons-quotes.json'));

        $quote = $provider->randomQuote();

        $this->assertNotSame('', $quote->quote);
        $this->assertNotSame('', $quote->character);
        $this->assertSame('mock', $quote->source);
    }

    #[Test]
    public function it_throws_an_exception_when_the_dataset_file_is_missing(): void
    {
        $this->expectException(QuoteProviderException::class);
        $this->expectExceptionMessage('The local quote dataset could not be loaded.');

        new MockQuoteProvider('/definitely/not/here.json');
    }

    #[Test]
    public function it_throws_an_exception_when_the_dataset_does_not_contain_a_valid_quote(): void
    {
        $dataset = tempnam(sys_get_temp_dir(), 'quotes-invalid-');
        file_put_contents($dataset, json_encode([['character' => 'Homer']], JSON_THROW_ON_ERROR));

        try {
            $provider = new MockQuoteProvider($dataset);

            $this->expectException(QuoteProviderException::class);
            $this->expectExceptionMessage('The local quote dataset does not contain a valid quote.');

            $provider->randomQuote();
        } finally {
            if ($dataset !== false && file_exists($dataset)) {
                unlink($dataset);
            }
        }
    }
}

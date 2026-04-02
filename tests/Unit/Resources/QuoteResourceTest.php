<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\QuoteResource;
use App\Models\UserQuote;
use Illuminate\Http\Request;
use Tests\TestCase;

class QuoteResourceTest extends TestCase
{
    public function test_it_serializes_quote_with_iso_timestamp(): void
    {
        $quote = new UserQuote([
            'quote' => 'Woohoo!',
            'character' => 'Homer Simpson',
            'image_url' => null,
            'source' => 'fake',
            'fetched_at' => '2026-04-02 01:23:45',
        ]);
        $quote->id = 42;

        $payload = (new QuoteResource($quote))->toArray(new Request());

        $this->assertSame(42, $payload['id']);
        $this->assertSame('Woohoo!', $payload['quote']);
        $this->assertSame('Homer Simpson', $payload['character']);
        $this->assertSame('fake', $payload['source']);
        $this->assertSame('2026-04-02T01:23:45+00:00', $payload['fetched_at']);
    }

    public function test_it_serializes_null_timestamp_when_missing(): void
    {
        $quote = new UserQuote([
            'quote' => 'Doh!',
            'character' => 'Homer Simpson',
            'source' => 'fake',
            'fetched_at' => null,
        ]);
        $quote->id = 7;

        $payload = (new QuoteResource($quote))->toArray(new Request());

        $this->assertSame(7, $payload['id']);
        $this->assertNull($payload['fetched_at']);
    }
}

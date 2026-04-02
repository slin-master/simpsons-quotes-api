<?php

namespace Tests\Feature\Quotes;

use App\Contracts\QuoteProvider;
use App\Data\QuoteData;
use App\Exceptions\QuoteProviderException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Fakes\SequenceQuoteProvider;
use Tests\TestCase;

class FetchQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_fetch_requires_authentication(): void
    {
        $this->postJson('/api/quotes')
            ->assertUnauthorized();
    }

    public function test_quote_fetch_requires_authentication_without_json_headers(): void
    {
        $this->post('/api/quotes')
            ->assertUnauthorized();
    }

    public function test_fetching_a_quote_persists_it_and_returns_the_expected_payload(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->app->instance(QuoteProvider::class, new SequenceQuoteProvider([
            new QuoteData(
                quote: 'Everything is coming up Milhouse.',
                character: 'Milhouse Van Houten',
                imageUrl: null,
                source: 'fake',
            ),
        ]));

        $response = $this->postJson('/api/quotes');

        $response
            ->assertOk()
            ->assertJsonPath('current_quote.quote', 'Everything is coming up Milhouse.')
            ->assertJsonPath('current_quote.character', 'Milhouse Van Houten')
            ->assertJsonCount(0, 'recent_quotes');

        $this->assertDatabaseHas('user_quotes', [
            'user_id' => $user->id,
            'quote' => 'Everything is coming up Milhouse.',
            'character' => 'Milhouse Van Houten',
        ]);
    }

    public function test_only_the_latest_five_quotes_are_kept_per_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $quotes = collect(range(1, 6))
            ->map(fn (int $index): QuoteData => new QuoteData(
                quote: "Quote {$index}",
                character: "Character {$index}",
                source: 'fake',
            ))
            ->all();

        $this->app->instance(QuoteProvider::class, new SequenceQuoteProvider($quotes));

        $response = null;

        for ($index = 1; $index <= 6; $index++) {
            $response = $this->postJson('/api/quotes');
            $response->assertOk();
        }

        $response
            ->assertOk()
            ->assertJsonPath('current_quote.quote', 'Quote 6')
            ->assertJsonCount(4, 'recent_quotes')
            ->assertJsonPath('recent_quotes.0.quote', 'Quote 5')
            ->assertJsonPath('recent_quotes.3.quote', 'Quote 2');

        $this->assertDatabaseCount('user_quotes', 5);
        $this->assertDatabaseMissing('user_quotes', [
            'user_id' => $user->id,
            'quote' => 'Quote 1',
        ]);
        $this->assertDatabaseHas('user_quotes', [
            'user_id' => $user->id,
            'quote' => 'Quote 6',
        ]);
    }

    public function test_quotes_are_isolated_per_user(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->app->instance(QuoteProvider::class, new SequenceQuoteProvider([
            new QuoteData(
                quote: 'First user quote 1',
                character: 'Lisa Simpson',
                source: 'fake',
            ),
            new QuoteData(
                quote: 'First user quote 2',
                character: 'Bart Simpson',
                source: 'fake',
            ),
            new QuoteData(
                quote: 'Second user quote 1',
                character: 'Homer Simpson',
                source: 'fake',
            ),
        ]));

        Sanctum::actingAs($firstUser);
        $this->postJson('/api/quotes')->assertOk();
        $firstUserSecondResponse = $this->postJson('/api/quotes');

        Sanctum::actingAs($secondUser);
        $secondUserResponse = $this->postJson('/api/quotes');

        $firstUserSecondResponse
            ->assertJsonCount(1, 'recent_quotes')
            ->assertJsonPath('recent_quotes.0.quote', 'First user quote 1');

        $secondUserResponse
            ->assertJsonCount(0, 'recent_quotes');

        $this->assertDatabaseCount('user_quotes', 3);
        $this->assertDatabaseHas('user_quotes', [
            'user_id' => $firstUser->id,
            'quote' => 'First user quote 1',
        ]);
        $this->assertDatabaseHas('user_quotes', [
            'user_id' => $firstUser->id,
            'quote' => 'First user quote 2',
        ]);
        $this->assertDatabaseHas('user_quotes', [
            'user_id' => $secondUser->id,
            'quote' => 'Second user quote 1',
        ]);
    }

    public function test_quote_fetch_returns_a_service_unavailable_response_when_all_providers_fail(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->app->instance(QuoteProvider::class, new class implements QuoteProvider
        {
            public function randomQuote(): QuoteData
            {
                throw new QuoteProviderException('Upstream and fallback providers both failed.');
            }
        });

        $this->postJson('/api/quotes')
            ->assertStatus(503)
            ->assertJsonPath('message', 'Quote provider is currently unavailable.')
            ->assertJsonPath('error.code', 'QUOTE_PROVIDER_UNAVAILABLE')
            ->assertJsonPath('error.details', 'Upstream and fallback providers both failed.');
    }
}

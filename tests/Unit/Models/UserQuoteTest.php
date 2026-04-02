<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\UserQuote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_casts_fetched_at_and_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $quote = UserQuote::query()->create([
            'user_id' => $user->id,
            'quote' => 'Excellent.',
            'character' => 'Mr. Burns',
            'image_url' => null,
            'source' => 'fake',
            'fetched_at' => '2026-04-02 10:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $quote->fetched_at);
        $relatedUser = $quote->user;

        $this->assertInstanceOf(User::class, $relatedUser);
        $this->assertSame($user->id, $relatedUser->id);
    }
}

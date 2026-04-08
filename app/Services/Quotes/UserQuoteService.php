<?php

namespace App\Services\Quotes;

use App\Contracts\QuoteProvider;
use App\Models\User;
use App\Models\UserQuote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserQuoteService
{
    public function __construct(
        private readonly QuoteProvider $quoteProvider,
        private readonly int $quotesPerUser,
    ) {
    }

    /**
     * @return array{current: UserQuote, recent: Collection<int, UserQuote>}
     */
    #[\NoDiscard]
    public function fetchAndStoreFor(User $user): array
    {
        $quoteData = $this->quoteProvider->randomQuote();
        $limit = $this->quotesPerUser;
        $recentLimit = max(0, $limit - 1);

        /** @var array{current: UserQuote, recent: Collection<int, UserQuote>} $result */
        $result = DB::transaction(function () use ($user, $quoteData, $limit, $recentLimit): array {
            $currentQuote = $user->quotes()->create([
                ...$quoteData->toArray(),
                'fetched_at' => now(),
            ]);

            UserQuote::query()
                ->where('user_id', $user->getKey())
                ->whereNotIn('id', function ($query) use ($user, $limit): void {
                    $query->select('id')
                        ->from('user_quotes')
                        ->where('user_id', $user->getKey())
                        ->orderByDesc('fetched_at')
                        ->orderByDesc('id')
                        ->limit($limit);
                })
                ->delete();

            return [
                'current' => $currentQuote,
                'recent' => $user->quotes()
                    ->whereKeyNot($currentQuote->getKey())
                    ->orderByDesc('fetched_at')
                    ->orderByDesc('id')
                    ->limit($recentLimit)
                    ->get(),
            ];
        });

        return [
            'current' => $result['current'],
            'recent' => $result['recent']->values(),
        ];
    }
}

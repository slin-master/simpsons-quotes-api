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

        /** @var array{current: UserQuote, recent: array<int, UserQuote>} $result */
        $result = DB::transaction(function () use ($user, $quoteData, $limit): array {
            $currentQuote = $user->quotes()->create([
                ...$quoteData->toArray(),
                'fetched_at' => now(),
            ]);

            $idsToDelete = $user->quotes()
                ->orderByDesc('fetched_at')
                ->orderByDesc('id')
                ->toBase()
                ->limit(PHP_INT_MAX)
                ->offset($limit)
                ->pluck('id');

            if ($idsToDelete->isNotEmpty()) {
                UserQuote::query()->whereKey($idsToDelete)->delete();
            }

            /** @var array<int, UserQuote> $quotes */
            $quotes = $user->quotes()
                ->orderByDesc('fetched_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->all();

            return [
                'current' => $currentQuote->fresh(),
                'recent' => array_slice($quotes, 1, max(0, $limit - 1)),
            ];
        });

        $recentQuotes = collect($result['recent']);

        return [
            'current' => $result['current'],
            'recent' => $recentQuotes->values(),
        ];
    }
}

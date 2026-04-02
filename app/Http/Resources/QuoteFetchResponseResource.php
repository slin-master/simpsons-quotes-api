<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteFetchResponseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{current: \App\Models\UserQuote, recent: \Illuminate\Support\Collection<int, \App\Models\UserQuote>} $payload */
        $payload = $this->resource;

        return [
            'current_quote' => new QuoteResource($payload['current']),
            'recent_quotes' => QuoteResource::collection($payload['recent']),
        ];
    }
}

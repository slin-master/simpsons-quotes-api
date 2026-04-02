<?php

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\UserQuote
 */
class QuoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fetchedAt = $this->fetched_at;

        return [
            'id' => $this->id,
            'quote' => $this->quote,
            'character' => $this->character,
            'image_url' => $this->image_url,
            'source' => $this->source,
            'fetched_at' => $fetchedAt instanceof CarbonInterface ? $fetchedAt->toIso8601String() : null,
        ];
    }
}

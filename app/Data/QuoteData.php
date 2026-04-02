<?php

namespace App\Data;

final readonly class QuoteData
{
    public function __construct(
        public string $quote,
        public string $character,
        public ?string $imageUrl = null,
        public string $source = 'unknown',
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'quote' => $this->quote,
            'character' => $this->character,
            'image_url' => $this->imageUrl,
            'source' => $this->source,
        ];
    }
}

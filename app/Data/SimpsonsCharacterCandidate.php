<?php

namespace App\Data;

final readonly class SimpsonsCharacterCandidate
{
    /**
     * @param  array<int, string>  $phrases
     */
    public function __construct(
        public string $name,
        public ?string $portraitPath,
        public array $phrases,
    ) {
    }
}

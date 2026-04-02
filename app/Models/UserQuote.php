<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'quote',
    'character',
    'image_url',
    'source',
    'fetched_at',
])]
/**
 * @property int $id
 * @property string $quote
 * @property string $character
 * @property string|null $image_url
 * @property string $source
 * @property \Illuminate\Support\Carbon|null $fetched_at
 */
class UserQuote extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

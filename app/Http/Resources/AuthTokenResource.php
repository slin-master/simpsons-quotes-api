<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{token: string, user: \App\Models\User} $payload */
        $payload = $this->resource;

        return [
            'token_type' => 'Bearer',
            'access_token' => $payload['token'],
            'user' => new UserResource($payload['user']),
        ];
    }
}

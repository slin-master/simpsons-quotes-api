<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginService
{
    /**
     * @return array{token: string, user: User}|null
     */
    #[\NoDiscard]
    public function authenticate(string $username, string $password): ?array
    {
        /** @var User|null $user */
        $user = User::query()->where('username', $username)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return [
            'token' => $user->createToken('api-token')->plainTextToken,
            'user' => $user,
        ];
    }
}

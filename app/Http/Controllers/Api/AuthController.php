<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\AuthTokenResource;
use App\Services\Auth\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginService $loginService,
    ) {
    }

    public function login(LoginRequest $request): AuthTokenResource|JsonResponse
    {
        $result = $this->loginService->authenticate(
            $request->string('username')->toString(),
            $request->string('password')->toString(),
        );

        if ($result === null) {
            return response()->json([
                'message' => 'The provided credentials are invalid.',
            ], 401);
        }

        return new AuthTokenResource($result);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Token revoked successfully.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuoteFetchResponseResource;
use App\Models\User;
use App\Services\Quotes\UserQuoteService;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(
        private readonly UserQuoteService $userQuoteService,
    ) {
    }

    public function store(Request $request): QuoteFetchResponseResource
    {
        /** @var User $user */
        $user = $request->user();

        $payload = $this->userQuoteService->fetchAndStoreFor($user);

        return new QuoteFetchResponseResource($payload);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate extends Middleware
{
    /**
     * Return a JSON 401 for API routes regardless of client headers.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        try {
            $this->authenticate($request, $guards);
        } catch (AuthenticationException $exception) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $exception->getMessage()], 401);
            }

            throw $exception;
        }

        return $next($request);
    }

    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}

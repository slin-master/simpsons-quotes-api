<?php

namespace Tests\Unit\Http;

use App\Http\Middleware\Authenticate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthenticateMiddlewareTest extends TestCase
{
    #[Test]
    public function it_rethrows_authentication_failures_for_non_api_routes(): void
    {
        $middleware = new class(app('auth')) extends Authenticate
        {
            /**
             * @param  array<int, string>  $guards
             */
            protected function authenticate($request, array $guards): void
            {
                throw new AuthenticationException('Unauthenticated.');
            }
        };

        $this->expectException(AuthenticationException::class);

        $middleware->handle(Request::create('/web/profile', 'GET'), fn () => new Response('ok'));
    }

    #[Test]
    public function redirect_to_returns_null(): void
    {
        $middleware = new class(app('auth')) extends Authenticate
        {
            public function publicRedirectTo(Request $request): ?string
            {
                return $this->redirectTo($request);
            }
        };

        $this->assertNull($middleware->publicRedirectTo(Request::create('/api/quotes', 'POST')));
    }
}

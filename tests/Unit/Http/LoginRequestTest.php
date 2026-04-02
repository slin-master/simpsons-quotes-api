<?php

namespace Tests\Unit\Http;

use App\Http\Requests\LoginRequest;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    public function test_authorize_returns_true(): void
    {
        $request = new LoginRequest();

        $this->assertTrue($request->authorize());
    }

    public function test_rules_match_expected_contract(): void
    {
        $request = new LoginRequest();

        $this->assertSame([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], $request->rules());
    }
}

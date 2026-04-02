<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_username_and_password(): void
    {
        User::factory()->create([
            'username' => 'lisa',
            'password' => 'Saxophone123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'lisa',
            'password' => 'Saxophone123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token_type',
                'access_token',
                'user' => ['id', 'name', 'username'],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'username' => 'bart',
            'password' => 'Skateboard123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'bart',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'The provided credentials are invalid.',
            ]);
    }

    public function test_login_requires_username_and_password(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_authenticated_user_can_logout_and_revoke_current_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Token revoked successfully.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

}

<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $rawUser = User::factory()->make()->toArray();
        $rawUser['password'] = 'password123';
        $rawUser['password_confirmation'] = 'password123';

        $response = $this->postJson(route('api.auth.register'), $rawUser);

        $response->assertCreated()
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_register_validation_fails(): void
    {
        $response = $this->postJson(route('api.auth.register'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login(): void
    {
        $password = 'password123';

        $user = User::factory()->create([
            'password' => bcrypt($password),
        ]);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => __('Unauthorized')]);
    }

    public function test_user_can_get_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)
            ->getJson(route('api.auth.me'));

        $response->assertOk()
            ->assertJson(['id' => $user->id, 'email' => $user->email]);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)
            ->postJson(route('api.auth.logout'));

        $response->assertOk()
            ->assertJson(['message' => __('Successfully logged out')]);
    }

    public function test_token_can_be_refreshed(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)
            ->postJson(route('api.auth.refresh'));

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
    }
}

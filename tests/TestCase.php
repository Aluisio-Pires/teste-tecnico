<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class TestCase extends BaseTestCase
{
    public function authRequest($method, $route, $status = Response::HTTP_OK, $request = [], $user = null): TestResponse
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.$this->generateToken($user),
        ])->simpleRequest(
            $method,
            $route,
            $status,
            $request
        );
    }

    public function simpleRequest($method, $route, $status = Response::HTTP_OK, $request = []): TestResponse
    {
        $response = $this->json($method,
            $route,
            $request
        );

        if ($status === 0) {
            return $response;
        }

        if ($response->status() !== $status) {
            dump($response->json());
        }
        $response->assertStatus($status);

        return $response;
    }

    public function simpleTest($method, $route, $status = Response::HTTP_OK, $request = [], $user = null, $protected = true)
    {
        if ($protected) {
            $this->isProtected($method, $route, $request);
        }

        return $this->authRequest($method, $route, $status, $request, $user);
    }

    public function isProtected($method, $route, $request = [])
    {
        if (auth()->check()) {
            foreach (config('auth.guards') as $guardName => $guardConfig) {
                $guard = auth()->guard($guardName);
                if (method_exists($guard, 'forgetUser')) {
                    $guard->forgetUser();
                }
            }
        }
        $this->assertGuest();

        $response = $this->json($method,
            $route,
            $request,
        );
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function generateToken($user = null): string
    {
        $user = $user ?: User::factory()->create();

        return auth('api')->login($user);
    }
}

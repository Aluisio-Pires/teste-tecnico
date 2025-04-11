<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWTGuard;

final class AuthService
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): JsonResponse
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        /** @var string $token */
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], Response::HTTP_CREATED);
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public function login(array $credentials): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');

        $token = $guard->attempt($credentials);

        if (! is_string($token)) {
            return response()->json(['error' => __('Unauthorized')], Response::HTTP_UNAUTHORIZED);
        }

        return $this->respondWithToken($token, $guard);
    }

    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->guard('api')->user();

        return response()->json($user);
    }

    public function logout(): JsonResponse
    {
        auth()->guard('api')->logout();

        return response()->json(['message' => __('Successfully logged out')]);
    }

    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');

        $newToken = $guard->refresh();

        return $this->respondWithToken($newToken, $guard);
    }

    private function respondWithToken(string $token, JWTGuard $guard): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $guard->factory()->getTTL() * 60,
        ]);
    }
}

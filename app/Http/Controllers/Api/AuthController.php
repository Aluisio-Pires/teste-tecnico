<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

final class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    /**
     * Cria uma conta.
     *
     * @Request({
     *        tags: Autenticação
     *   })
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        /** @var array{name: string, email: string, password: string} $validated */
        $validated = $request->validated();

        return $this->authService->register($validated);
    }

    /**
     * Cria uma sessão.
     *
     * @Request({
     *         tags: Autenticação
     *    })
     */
    public function login(LoginRequest $request): JsonResponse
    {
        /** @var array{email: string, password: string} $validated */
        $validated = $request->validated();

        return $this->authService->login($validated);
    }

    /**
     * Recupera os dados da conta autenticada.
     *
     * @Request({
     *         tags: Autenticação
     *    })
     */
    public function me(): JsonResponse
    {
        return $this->authService->me();
    }

    /**
     * Invalida a sessão da conta autenticada.
     *
     * @Request({
     *         tags: Autenticação
     *    })
     */
    public function logout(): JsonResponse
    {
        return $this->authService->logout();
    }

    /**
     * Cria um ‘token’ para a conta autenticada.
     *
     * @Request({
     *         tags: Autenticação
     *    })
     */
    public function refresh(): JsonResponse
    {
        return $this->authService->refresh();
    }
}

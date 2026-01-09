<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Controller for authentication endpoints.
 *
 * Handles user registration, login, logout, and token management.
 *
 * @group Authentication
 */
class AuthController extends Controller
{
    /**
     * @param AuthService $authService Authentication service
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Register a new user.
     *
     * Creates a new user account and returns an access token.
     *
     * @param RegisterRequest $request Validated registration data
     * @return JsonResponse
     *
     * @response 201 {
     *   "success": true,
     *   "message": "User registered successfully",
     *   "data": {
     *     "user": {"id": 1, "name": "John Doe", "email": "john@example.com"},
     *     "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *     "token_type": "bearer",
     *     "expires_in": 3600
     *   }
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => new UserResource($result['user']),
                ...$this->authService->getTokenResponse($result['token']),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Authenticate user and get token.
     *
     * Validates credentials and returns a JWT access token.
     *
     * @param LoginRequest $request Validated login credentials
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Login successful",
     *   "data": {
     *     "user": {"id": 1, "name": "John Doe", "email": "john@example.com"},
     *     "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *     "token_type": "bearer",
     *     "expires_in": 3600
     *   }
     * }
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid credentials"
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($result['user']),
                ...$this->authService->getTokenResponse($result['token']),
            ],
        ]);
    }

    /**
     * Log the user out (invalidate token).
     *
     * Invalidates the current access token.
     *
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Successfully logged out"
     * }
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Refresh the access token.
     *
     * Generates a new access token using the current token.
     *
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *     "token_type": "bearer",
     *     "expires_in": 3600
     *   }
     * }
     */
    public function refresh(): JsonResponse
    {
        $token = $this->authService->refresh();

        return response()->json([
            'success' => true,
            'data' => $this->authService->getTokenResponse($token),
        ]);
    }

    /**
     * Get the authenticated user.
     *
     * Returns the currently authenticated user's information.
     *
     * @return JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {"id": 1, "name": "John Doe", "email": "john@example.com"}
     * }
     */
    public function me(): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser();

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }
}

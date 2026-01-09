<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Service class for authentication-related business logic.
 *
 * This service handles user registration, login, logout, and
 * JWT token management.
 */
class AuthService
{
    /**
     * Register a new user.
     *
     * @param array{name: string, email: string, password: string} $data User data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Authenticate a user and generate token.
     *
     * @param array{email: string, password: string} $credentials User credentials
     * @return array{user: User, token: string}|null Returns null if authentication fails
     */
    public function login(array $credentials): ?array
    {
        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return null;
        }

        // Get the user from the token since auth()->user() won't work immediately after attempt()
        $user = JWTAuth::setToken($token)->toUser();

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout the current user (invalidate token).
     *
     * @return void
     */
    public function logout(): void
    {
        $token = JWTAuth::getToken();

        if ($token) {
            JWTAuth::invalidate($token);
        }
    }

    /**
     * Refresh the current token.
     *
     * @return string New token
     */
    public function refresh(): string
    {
        $token = JWTAuth::getToken();

        if ($token) {
            return JWTAuth::refresh($token);
        }

        // If no token exists, generate a new one for the authenticated user
        $user = auth('api')->user();
        return JWTAuth::fromUser($user);
    }

    /**
     * Get the authenticated user.
     *
     * @return User|null
     */
    public function getAuthenticatedUser(): ?User
    {
        return auth('api')->user();
    }

    /**
     * Get token response structure.
     *
     * @param string $token JWT token
     * @return array{access_token: string, token_type: string, expires_in: int}
     */
    public function getTokenResponse(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // Convert minutes to seconds
        ];
    }
}

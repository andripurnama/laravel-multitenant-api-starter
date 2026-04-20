<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Register a new user within a tenant context
     *
     * @param  array  $data  User registration data
     * @param  int  $tenantId  Tenant identifier
     */
    public function register(array $data, int $tenantId): User;

    /**
     * Authenticate user and issue tokens
     *
     * @param  string  $email  User email
     * @param  string  $password  User password
     * @param  int  $tenantId  Tenant identifier
     * @return array Token response with access_token, refresh_token, expires_in
     */
    public function login(string $email, string $password, int $tenantId): array;

    /**
     * Refresh access token using refresh token
     *
     * @param  string  $refreshToken  Refresh token
     * @return array Token response with access_token, refresh_token, expires_in
     */
    public function refreshToken(string $refreshToken): array;

    /**
     * Revoke user's tokens (logout)
     *
     * @param  User  $user  Authenticated user
     */
    public function logout(User $user): bool;

    /**
     * Request password reset token
     *
     * @param  string  $email  User email
     * @param  int  $tenantId  Tenant identifier
     * @return bool Always returns true to prevent user enumeration
     */
    public function requestPasswordReset(string $email, int $tenantId): bool;

    /**
     * Reset password using token
     *
     * @param  string  $token  Password reset token
     * @param  string  $email  User email
     * @param  string  $password  New password
     * @return bool True if password was reset successfully
     */
    public function resetPassword(string $token, string $email, string $password): bool;

    /**
     * Send email verification
     *
     * @param  User  $user  User to send verification email to
     * @return bool True if verification email was sent successfully
     */
    public function sendEmailVerification(User $user): bool;

    /**
     * Verify email with token
     *
     * @param  string  $token  Email verification token
     * @return bool True if email was verified successfully
     */
    public function verifyEmail(string $token): bool;
}

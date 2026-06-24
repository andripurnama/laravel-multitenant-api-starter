<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidResetTokenException;
use App\Exceptions\Auth\UserNotFoundException;
use App\Models\User;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TenantRepositoryInterface $tenantRepository
    ) {}

    /**
     * Register a new user within a tenant context
     *
     * @param  array  $data  User registration data
     * @param  int  $tenantId  Tenant identifier
     */
    public function register(array $data, int $tenantId): User
    {
        // Verify tenant exists
        $tenant = $this->tenantRepository->find($tenantId);

        if (! $tenant) {
            throw new \InvalidArgumentException("Tenant with ID {$tenantId} not found");
        }

        // Hash the password
        $data['password'] = Hash::make($data['password']);
        $data['tenant_id'] = $tenantId;

        // Create the user
        return $this->userRepository->create($data);
    }

    /**
     * Authenticate user and issue token
     *
     * @param  string  $email  User email
     * @param  string  $password  User password
     * @param  int  $tenantId  Tenant identifier
     * @return array Token response with access_token and token_type
     */
    public function login(string $email, string $password, int $tenantId): array
    {
        // Find user by email within tenant context
        // Use findByEmailAndTenant to handle multiple users with same email across tenants
        $user = $this->userRepository->findByEmailAndTenant($email, $tenantId);

        // Verify user exists
        // Generic error message to prevent credential enumeration (Requirement 2.6)
        if (! $user) {
            throw new InvalidCredentialsException;
        }

        // Verify password
        if (! Hash::check($password, $user->password)) {
            // Generic error message to prevent credential enumeration (Requirement 2.6)
            throw new InvalidCredentialsException;
        }

        // Create token using Sanctum
        $token = $user->createToken('auth_token');

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Revoke user's current token (logout)
     *
     * @param  User  $user  Authenticated user
     */
    public function logout(User $user): bool
    {
        // Revoke current access token
        $user->currentAccessToken()->delete();
        
        return true;
    }

    /**
     * Request password reset token
     *
     * @param  string  $email  User email
     * @param  int  $tenantId  Tenant identifier
     * @return bool Always returns true to prevent user enumeration
     */
    public function requestPasswordReset(string $email, int $tenantId): bool
    {
        // Find user by email within tenant context
        $user = $this->userRepository->findByEmail($email);

        // If user doesn't exist or doesn't belong to the tenant, still return true
        // to prevent user enumeration (Requirement 12.5)
        if (! $user || $user->tenant_id !== $tenantId) {
            return true;
        }

        // Generate a secure reset token
        $token = Hash::make(Str::random(60));

        // Store the reset token with expiration timestamp
        DB::table('password_reset_tokens')->updateOrInsert(
            [
                'email' => $email,
                'tenant_id' => $tenantId,
            ],
            [
                'token' => $token,
                'created_at' => now(),
            ]
        );

        // TODO: Send password reset email (Requirement 12.4)
        // This would typically dispatch a notification or event
        // For now, we're just storing the token

        return true;
    }

    /**
     * Reset password using token
     *
     * @param  string  $token  Password reset token
     * @param  string  $email  User email
     * @param  string  $password  New password
     * @return bool True if password was reset successfully
     */
    public function resetPassword(string $token, string $email, string $password): bool
    {
        // Find user by email
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            throw new InvalidResetTokenException;
        }

        // Retrieve the reset token record
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        // Validate token exists
        if (! $resetRecord) {
            throw new InvalidResetTokenException;
        }

        // Check if token has expired (default 60 minutes from config)
        $expirationMinutes = config('auth.passwords.users.expire', 60);
        $tokenCreatedAt = \Carbon\Carbon::parse($resetRecord->created_at);
        $expiresAt = $tokenCreatedAt->copy()->addMinutes($expirationMinutes);
        
        if (now()->isAfter($expiresAt)) {
            throw new InvalidResetTokenException('The password reset token has expired.');
        }

        // Validate token matches
        if (! Hash::check($token, $resetRecord->token)) {
            throw new InvalidResetTokenException;
        }

        // Update the user's password with bcrypt hash
        $this->userRepository->update($user, [
            'password' => Hash::make($password),
        ]);

        // Invalidate the reset token
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('tenant_id', $user->tenant_id)
            ->delete();

        // Revoke all existing tokens for the user (Requirement 13.5)
        $user->tokens()->delete();

        return true;
    }

    /**
     * Send email verification
     *
     * @param  User  $user  User to send verification email to
     * @return bool True if verification email was sent successfully
     */
    public function sendEmailVerification(User $user): bool
    {
        // Generate a secure verification token
        $token = hash('sha256', Str::random(60));

        // Store the verification token
        $this->userRepository->update($user, [
            'email_verification_token' => $token,
        ]);

        // TODO: Send verification email (Requirement 19.2)
        // This would typically dispatch a notification or event
        // For now, we're just storing the token

        return true;
    }

    /**
     * Verify email with token
     *
     * @param  string  $token  Email verification token
     * @return bool True if email was verified successfully
     */
    public function verifyEmail(string $token): bool
    {
        // Find user by verification token
        $user = $this->userRepository->findByEmailVerificationToken($token);

        if (! $user) {
            return false;
        }

        // Mark email as verified and clear the token
        $this->userRepository->update($user, [
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ]);

        return true;
    }
}

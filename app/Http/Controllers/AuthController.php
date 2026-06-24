<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
        private readonly TenantContext $tenantContext
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tenantId = $this->tenantContext->getTenant();
        $user = $this->authService->register($data, $tenantId);

        return $this->created(new UserResource($user));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant();
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password'),
            $tenantId
        );

        return $this->success($result);
    }

    /**
     * Logout
     *
     * Revoke the current user's access token.
     *
     * @authenticated
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(message: 'Logged out successfully');
    }

    public function requestPasswordReset(PasswordResetRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant();
        $this->authService->requestPasswordReset(
            $request->input('email'),
            $tenantId
        );

        return $this->success(message: 'Password reset email sent');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant();
        $this->authService->resetPassword(
            $request->input('email'),
            $request->input('token'),
            $request->input('password'),
            $tenantId
        );

        return $this->success(message: 'Password reset successfully');
    }

    /**
     * Send email verification
     *
     * Send a verification email to the authenticated user.
     *
     * @authenticated
     */
    public function sendVerification(Request $request): JsonResponse
    {
        $this->authService->sendEmailVerification($request->user());

        return $this->success(message: 'Verification email sent');
    }

    public function verifyEmail(Request $request, string $token): JsonResponse
    {
        $this->authService->verifyEmail($token);

        return $this->success(message: 'Email verified successfully');
    }

    /**
     * Get user profile
     *
     * Retrieve the authenticated user's profile information including roles and permissions.
     *
     * @authenticated
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles.permissions');

        return $this->success(new UserResource($user));
    }
}

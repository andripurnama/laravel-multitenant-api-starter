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
        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant();
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password'),
            $tenantId
        );

        return response()->json([
            'access_token' => $result['access_token'],
            'token_type' => 'Bearer',
        ]);
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
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function requestPasswordReset(PasswordResetRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant();
        $this->authService->requestPasswordReset(
            $request->input('email'),
            $tenantId
        );

        return response()->json(['message' => 'Password reset email sent']);
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

        return response()->json(['message' => 'Password reset successfully']);
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
        return response()->json(['message' => 'Verification email sent']);
    }

    public function verifyEmail(Request $request, string $token): JsonResponse
    {
        $this->authService->verifyEmail($token);
        return response()->json(['message' => 'Email verified successfully']);
    }

    /**
     * Get user profile
     * 
     * Retrieve the authenticated user's profile information including roles and permissions.
     * 
     * @authenticated
     */
    public function profile(Request $request): UserResource
    {
        $user = $request->user()->load('roles.permissions');
        return new UserResource($user);
    }
}

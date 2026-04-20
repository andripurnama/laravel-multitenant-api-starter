<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Email Verification', function () {
    test('sendEmailVerification generates token and stores it', function () {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user = User::factory()->unverified()->create([
            'tenant_id' => $tenant->id,
        ]);

        $authService = app(AuthService::class);

        // Act
        $result = $authService->sendEmailVerification($user);

        // Assert
        expect($result)->toBeTrue();
        
        $user->refresh();
        expect($user->email_verification_token)
            ->not->toBeNull()
            ->and($user->email_verification_token)->toHaveLength(64);
    });

    test('verifyEmail marks email as verified with valid token', function () {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user = User::factory()->unverified()->create([
            'tenant_id' => $tenant->id,
        ]);

        $authService = app(AuthService::class);
        
        // Send verification email to generate token
        $authService->sendEmailVerification($user);
        $user->refresh();
        $token = $user->email_verification_token;

        // Act
        $result = $authService->verifyEmail($token);

        // Assert
        expect($result)->toBeTrue();
        
        $user->refresh();
        expect($user->email_verified_at)
            ->not->toBeNull()
            ->and($user->email_verification_token)->toBeNull();
    });

    test('verifyEmail returns false with invalid token', function () {
        // Arrange
        $authService = app(AuthService::class);
        $invalidToken = hash('sha256', 'invalid-token');

        // Act
        $result = $authService->verifyEmail($invalidToken);

        // Assert
        expect($result)->toBeFalse();
    });

    test('user email starts as unverified after registration', function () {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user = User::factory()->unverified()->create([
            'tenant_id' => $tenant->id,
        ]);

        // Assert
        expect($user->email_verified_at)->toBeNull();
    });

    test('verification token is cleared after successful verification', function () {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user = User::factory()->unverified()->create([
            'tenant_id' => $tenant->id,
        ]);

        $authService = app(AuthService::class);
        
        // Send verification email
        $authService->sendEmailVerification($user);
        $user->refresh();
        $token = $user->email_verification_token;

        expect($token)->not->toBeNull();

        // Act
        $authService->verifyEmail($token);

        // Assert
        $user->refresh();
        expect($user->email_verification_token)->toBeNull();
    });

    test('same token cannot be used twice', function () {
        // Arrange
        $tenant = Tenant::factory()->create();
        $user = User::factory()->unverified()->create([
            'tenant_id' => $tenant->id,
        ]);

        $authService = app(AuthService::class);
        
        // Send verification email
        $authService->sendEmailVerification($user);
        $user->refresh();
        $token = $user->email_verification_token;

        // First verification
        $firstResult = $authService->verifyEmail($token);
        expect($firstResult)->toBeTrue();

        // Act - Try to use the same token again
        $secondResult = $authService->verifyEmail($token);

        // Assert
        expect($secondResult)->toBeFalse();
    });
});

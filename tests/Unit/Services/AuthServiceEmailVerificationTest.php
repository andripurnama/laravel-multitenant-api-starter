<?php

declare(strict_types=1);

use App\Models\User;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->tokenRepository = Mockery::mock(TokenRepositoryInterface::class);
    $this->tenantRepository = Mockery::mock(TenantRepositoryInterface::class);
    
    $this->authService = new AuthService(
        $this->userRepository,
        $this->tokenRepository,
        $this->tenantRepository
    );
});

afterEach(function () {
    Mockery::close();
});

describe('sendEmailVerification', function () {
    test('generates and stores email verification token', function () {
        // Arrange
        $user = new User([
            'id' => 1,
            'email' => 'test@example.com',
            'tenant_id' => 1,
        ]);
        $user->id = 1;

        $this->userRepository->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($u) => $u->id === 1),
                Mockery::on(function ($data) {
                    return isset($data['email_verification_token']) 
                        && is_string($data['email_verification_token'])
                        && strlen($data['email_verification_token']) === 64; // SHA256 hash length
                })
            )
            ->andReturn($user);

        // Act
        $result = $this->authService->sendEmailVerification($user);

        // Assert
        expect($result)->toBeTrue();
    });

    test('returns true on successful token generation', function () {
        // Arrange
        $user = new User([
            'id' => 1,
            'email' => 'test@example.com',
            'tenant_id' => 1,
        ]);
        $user->id = 1;

        $this->userRepository->shouldReceive('update')
            ->once()
            ->andReturn($user);

        // Act
        $result = $this->authService->sendEmailVerification($user);

        // Assert
        expect($result)->toBeTrue();
    });
});

describe('verifyEmail', function () {
    test('marks email as verified with valid token', function () {
        // Arrange
        $token = hash('sha256', 'valid-token');
        $user = new User([
            'id' => 1,
            'email' => 'test@example.com',
            'tenant_id' => 1,
            'email_verification_token' => $token,
            'email_verified_at' => null,
        ]);
        $user->id = 1;

        $this->userRepository->shouldReceive('findByEmailVerificationToken')
            ->once()
            ->with($token)
            ->andReturn($user);

        $this->userRepository->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($u) => $u->id === 1),
                Mockery::on(function ($data) {
                    return isset($data['email_verified_at'])
                        && $data['email_verification_token'] === null;
                })
            )
            ->andReturn($user);

        // Act
        $result = $this->authService->verifyEmail($token);

        // Assert
        expect($result)->toBeTrue();
    });

    test('returns false with invalid token', function () {
        // Arrange
        $token = hash('sha256', 'invalid-token');

        $this->userRepository->shouldReceive('findByEmailVerificationToken')
            ->once()
            ->with($token)
            ->andReturn(null);

        // Act
        $result = $this->authService->verifyEmail($token);

        // Assert
        expect($result)->toBeFalse();
    });

    test('returns false when user not found', function () {
        // Arrange
        $token = hash('sha256', 'nonexistent-token');

        $this->userRepository->shouldReceive('findByEmailVerificationToken')
            ->once()
            ->with($token)
            ->andReturn(null);

        // Act
        $result = $this->authService->verifyEmail($token);

        // Assert
        expect($result)->toBeFalse();
    });

    test('clears verification token after successful verification', function () {
        // Arrange
        $token = hash('sha256', 'valid-token');
        $user = new User([
            'id' => 1,
            'email' => 'test@example.com',
            'tenant_id' => 1,
            'email_verification_token' => $token,
            'email_verified_at' => null,
        ]);
        $user->id = 1;

        $this->userRepository->shouldReceive('findByEmailVerificationToken')
            ->once()
            ->with($token)
            ->andReturn($user);

        $this->userRepository->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($u) => $u->id === 1),
                Mockery::on(function ($data) {
                    return $data['email_verification_token'] === null;
                })
            )
            ->andReturn($user);

        // Act
        $result = $this->authService->verifyEmail($token);

        // Assert
        expect($result)->toBeTrue();
    });
});

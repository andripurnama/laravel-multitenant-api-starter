<?php

declare(strict_types=1);

use App\Exceptions\Auth\CrossTenantAccessException;
use App\Exceptions\Auth\EmailNotVerifiedException;
use App\Exceptions\Auth\InsufficientPermissionsException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InvalidResetTokenException;
use App\Exceptions\Auth\RoleNotFoundException;
use App\Exceptions\Auth\TokenExpiredException;
use App\Exceptions\Auth\TokenRevokedException;
use App\Exceptions\Auth\UserNotFoundException;

test('InvalidCredentialsException has correct message and status code', function () {
    $exception = new InvalidCredentialsException;

    expect($exception->getMessage())->toBe('The provided credentials are invalid.')
        ->and($exception->getStatusCode())->toBe(401)
        ->and($exception->getResponseData())->toHaveKey('success')
        ->and($exception->getResponseData())->toHaveKey('message')
        ->and($exception->getResponseData())->toHaveKey('errors');
});

test('InvalidCredentialsException accepts custom message', function () {
    $exception = new InvalidCredentialsException('Custom error message');

    expect($exception->getMessage())->toBe('Custom error message')
        ->and($exception->getStatusCode())->toBe(401);
});

test('UserNotFoundException has correct message and status code', function () {
    $exception = new UserNotFoundException;

    expect($exception->getMessage())->toBe('User not found in the specified tenant.')
        ->and($exception->getStatusCode())->toBe(404);
});

test('UserNotFoundException includes user ID in message when provided', function () {
    $exception = new UserNotFoundException(null, 123);

    expect($exception->getMessage())->toBe('User with ID 123 not found in the specified tenant.')
        ->and($exception->getStatusCode())->toBe(404);
});

test('TokenExpiredException has correct message and status code', function () {
    $exception = new TokenExpiredException;

    expect($exception->getMessage())->toBe('The authentication token has expired.')
        ->and($exception->getStatusCode())->toBe(401);
});

test('TokenRevokedException has correct message and status code', function () {
    $exception = new TokenRevokedException;

    expect($exception->getMessage())->toBe('The authentication token has been revoked.')
        ->and($exception->getStatusCode())->toBe(401);
});

test('EmailNotVerifiedException has correct message and status code', function () {
    $exception = new EmailNotVerifiedException;

    expect($exception->getMessage())->toBe('Email address has not been verified.')
        ->and($exception->getStatusCode())->toBe(403);
});

test('InsufficientPermissionsException has correct message and status code', function () {
    $exception = new InsufficientPermissionsException;

    expect($exception->getMessage())->toBe('You do not have permission to perform this action.')
        ->and($exception->getStatusCode())->toBe(403);
});

test('InsufficientPermissionsException includes permission name when provided', function () {
    $exception = new InsufficientPermissionsException(null, 'edit-posts');

    expect($exception->getMessage())->toBe('You do not have the required permission: edit-posts')
        ->and($exception->getStatusCode())->toBe(403);
});

test('RoleNotFoundException has correct message and status code', function () {
    $exception = new RoleNotFoundException;

    expect($exception->getMessage())->toBe('The specified role does not exist.')
        ->and($exception->getStatusCode())->toBe(404);
});

test('RoleNotFoundException includes role name when provided', function () {
    $exception = new RoleNotFoundException(null, 'admin');

    expect($exception->getMessage())->toBe("Role 'admin' not found in the specified tenant.")
        ->and($exception->getStatusCode())->toBe(404);
});

test('CrossTenantAccessException has correct message and status code', function () {
    $exception = new CrossTenantAccessException;

    expect($exception->getMessage())->toBe('Cross-tenant access is not permitted.')
        ->and($exception->getStatusCode())->toBe(403);
});

test('InvalidResetTokenException has correct message and status code', function () {
    $exception = new InvalidResetTokenException;

    expect($exception->getMessage())->toBe('The password reset token is invalid or expired.')
        ->and($exception->getStatusCode())->toBe(400);
});

test('all auth exceptions return proper response data structure', function () {
    $exceptions = [
        new InvalidCredentialsException,
        new UserNotFoundException,
        new TokenExpiredException,
        new TokenRevokedException,
        new EmailNotVerifiedException,
        new InsufficientPermissionsException,
        new RoleNotFoundException,
        new CrossTenantAccessException,
        new InvalidResetTokenException,
    ];

    foreach ($exceptions as $exception) {
        $responseData = $exception->getResponseData();

        expect($responseData)->toBeArray()
            ->and($responseData)->toHaveKey('success')
            ->and($responseData)->toHaveKey('message')
            ->and($responseData)->toHaveKey('errors')
            ->and($responseData['success'])->toBeFalse()
            ->and($responseData['message'])->toBeString()
            ->and($responseData['errors']['code'])->toBeString();
    }
});

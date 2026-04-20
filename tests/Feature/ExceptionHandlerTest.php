<?php

declare(strict_types=1);

use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\InsufficientPermissionsException;
use App\Exceptions\Auth\UserNotFoundException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Register test routes that throw exceptions
    Route::get('/test/invalid-credentials', function () {
        throw new InvalidCredentialsException();
    });
    
    Route::get('/test/user-not-found', function () {
        throw new UserNotFoundException(null, 123);
    });
    
    Route::get('/test/insufficient-permissions', function () {
        throw new InsufficientPermissionsException(null, 'edit-posts');
    });
});

test('InvalidCredentialsException returns proper JSON response', function () {
    $response = $this->getJson('/test/invalid-credentials');
    
    $response->assertStatus(401)
        ->assertJson([
            'message' => 'The provided credentials are invalid.',
            'error' => 'InvalidCredentialsException',
        ]);
});

test('UserNotFoundException returns proper JSON response', function () {
    $response = $this->getJson('/test/user-not-found');
    
    $response->assertStatus(404)
        ->assertJson([
            'message' => 'User with ID 123 not found in the specified tenant.',
            'error' => 'UserNotFoundException',
        ]);
});

test('InsufficientPermissionsException returns proper JSON response', function () {
    $response = $this->getJson('/test/insufficient-permissions');
    
    $response->assertStatus(403)
        ->assertJson([
            'message' => 'You do not have the required permission: edit-posts',
            'error' => 'InsufficientPermissionsException',
        ]);
});

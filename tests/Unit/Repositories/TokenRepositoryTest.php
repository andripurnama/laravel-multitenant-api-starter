<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

test('user can create personal access token', function () {
    $user = User::factory()->create();
    
    $token = $user->createToken('test-token');

    expect($token)->toBeInstanceOf(\Laravel\Sanctum\NewAccessToken::class)
        ->and($token->plainTextToken)->toBeString()
        ->and($token->accessToken)->toBeInstanceOf(PersonalAccessToken::class);
});

test('user can have multiple tokens', function () {
    $user = User::factory()->create();
    
    $user->createToken('token-1');
    $user->createToken('token-2');
    $user->createToken('token-3');

    expect($user->tokens)->toHaveCount(3);
});

test('user can revoke specific token', function () {
    $user = User::factory()->create();
    
    $token1 = $user->createToken('token-1');
    $token2 = $user->createToken('token-2');

    $user->tokens()->where('id', $token1->accessToken->id)->delete();

    expect($user->fresh()->tokens)->toHaveCount(1)
        ->and($user->fresh()->tokens->first()->id)->toBe($token2->accessToken->id);
});

test('user can revoke all tokens', function () {
    $user = User::factory()->create();
    
    $user->createToken('token-1');
    $user->createToken('token-2');
    $user->createToken('token-3');

    $user->tokens()->delete();

    expect($user->fresh()->tokens)->toHaveCount(0);
});

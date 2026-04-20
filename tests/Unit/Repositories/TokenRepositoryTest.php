<?php

use App\Models\User;
use App\Repositories\Eloquent\EloquentTokenRepository;
use Database\Factories\TokenFactory;
use Laravel\Passport\Token;

test('findByUser returns user tokens', function () {
    $user = User::factory()->create();
    
    TokenFactory::new()->count(3)->create(['user_id' => $user->id]);
    TokenFactory::new()->count(2)->create(['user_id' => User::factory()->create()->id]);

    $repository = new EloquentTokenRepository();
    $tokens = $repository->findByUser($user->id);

    expect($tokens)->toHaveCount(3)
        ->and($tokens->every(fn($token) => $token->user_id === $user->id))->toBeTrue();
});

test('revoke marks token as revoked', function () {
    $token = TokenFactory::new()->create(['revoked' => false]);

    $repository = new EloquentTokenRepository();
    $result = $repository->revoke($token);

    expect($result)->toBeTrue();
    expect($token->fresh()->revoked)->toBeTrue();
});

test('revokeAllForUser revokes all tokens', function () {
    $user = User::factory()->create();
    TokenFactory::new()->count(3)->create(['user_id' => $user->id, 'revoked' => false]);

    $repository = new EloquentTokenRepository();
    $count = $repository->revokeAllForUser($user->id);

    expect($count)->toBe(3);
    expect(Token::where('user_id', $user->id)->where('revoked', false)->count())->toBe(0);
});

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Services\Contracts\TokenServiceInterface;
use Laravel\Sanctum\NewAccessToken;

class TokenService implements TokenServiceInterface
{
    public function createPersonalAccessToken(User $user, string $name, array $abilities = []): NewAccessToken
    {
        return $user->createToken($name, $abilities);
    }

    public function revokeToken(User $user, string $tokenId): bool
    {
        return $user->tokens()->where('id', $tokenId)->delete() > 0;
    }

    public function revokeAllUserTokens(User $user): bool
    {
        return $user->tokens()->delete() > 0;
    }
}

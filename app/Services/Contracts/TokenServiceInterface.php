<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

interface TokenServiceInterface
{
    public function createPersonalAccessToken(User $user, string $name, array $abilities = []): NewAccessToken;
    public function revokeToken(User $user, string $tokenId): bool;
    public function revokeAllUserTokens(User $user): bool;
}

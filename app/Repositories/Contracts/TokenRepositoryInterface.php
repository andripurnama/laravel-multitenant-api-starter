<?php

namespace App\Repositories\Contracts;

use Laravel\Passport\Token;
use Illuminate\Support\Collection;

interface TokenRepositoryInterface
{
    public function findById(string $id): ?Token;
    public function findByUser(int $userId): Collection;
    public function revoke(Token $token): bool;
    public function revokeAllForUser(int $userId): int;
    public function findByRefreshToken(string $refreshToken): ?Token;
}

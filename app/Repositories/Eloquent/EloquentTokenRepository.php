<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\TokenRepositoryInterface;
use Laravel\Passport\Token;
use Illuminate\Support\Collection;

class EloquentTokenRepository implements TokenRepositoryInterface
{
    public function findById(string $id): ?Token
    {
        return Token::find($id);
    }

    public function findByUser(int $userId): Collection
    {
        return Token::where('user_id', $userId)->get();
    }

    public function revoke(Token $token): bool
    {
        $token->revoke();
        return true;
    }

    public function revokeAllForUser(int $userId): int
    {
        return Token::where('user_id', $userId)->update(['revoked' => true]);
    }

    public function findByRefreshToken(string $refreshToken): ?Token
    {
        return Token::whereHas('refreshToken', function ($query) use ($refreshToken) {
            $query->where('id', $refreshToken);
        })->first();
    }
}

<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByEmailAndTenant(string $email, int $tenantId): ?User
    {
        return User::where('email', $email)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function findByEmailVerificationToken(string $token): ?User
    {
        return User::where('email_verification_token', $token)->first();
    }

    public function getAllByTenant(int $tenantId): Collection
    {
        return User::where('tenant_id', $tenantId)->get();
    }
}

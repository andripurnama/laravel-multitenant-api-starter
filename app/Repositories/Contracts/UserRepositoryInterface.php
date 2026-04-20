<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findByEmailAndTenant(string $email, int $tenantId): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): bool;
    public function findByEmailVerificationToken(string $token): ?User;
    public function getAllByTenant(int $tenantId): Collection;
}

<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Passport\Token;

class TokenFactory extends Factory
{
    protected $model = Token::class;

    public function definition(): array
    {
        return [
            'id' => Str::random(80),
            'user_id' => User::factory(),
            'client_id' => Str::uuid()->toString(),
            'name' => null,
            'scopes' => [],
            'revoked' => false,
            'expires_at' => now()->addYear(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => true,
        ]);
    }
}

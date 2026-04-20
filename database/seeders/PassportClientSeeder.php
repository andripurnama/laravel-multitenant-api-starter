<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PassportClientSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::table('oauth_clients')->where('name', 'Personal Access Client')->exists()) {
            DB::table('oauth_clients')->insert([
                'id' => Str::uuid(),
                'name' => 'Personal Access Client',
                'secret' => null,
                'redirect_uris' => json_encode([]),
                'grant_types' => json_encode(['personal_access']),
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

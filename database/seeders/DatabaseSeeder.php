<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test tenant
        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
        ]);

        // Create a test user for the tenant
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'tenant_id' => $tenant->id,
        ]);
    }
}

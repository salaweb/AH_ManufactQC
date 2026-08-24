<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'name' => 'QC Test',
            'email' => 'qc@test.com',
            'role' => UserRole::Qc,
        ]);

        User::factory()->operari()->create([
            'name' => 'Operari Test',
            'username' => 'operari_test',
        ]);
    }
}

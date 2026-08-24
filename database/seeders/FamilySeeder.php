<?php

namespace Database\Seeders;

use App\Models\Family;
use Illuminate\Database\Seeder;

class FamilySeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['D2', 'DB2', 'DB3', 'Talk', 'D2 UC'] as $name) {
            Family::firstOrCreate(['name' => $name]);
        }
    }
}

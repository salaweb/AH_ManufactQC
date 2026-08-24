<?php

namespace Database\Seeders;

use App\Models\DescriptionTag;
use Illuminate\Database\Seeder;

class DescriptionTagSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tags = ['AH17DX2', 'AH15DX2', 'AH22DX2', 'TS', 'USB', 'USBC', 'AHDT', 'CBOSH'];

        foreach ($tags as $name) {
            DescriptionTag::firstOrCreate(['name' => $name]);
        }
    }
}

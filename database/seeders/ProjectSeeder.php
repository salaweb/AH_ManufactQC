<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\Section;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $qualitat = Section::where('name', 'QUALITAT')->first();

        Project::factory()
            ->count(3)
            ->create()
            ->each(function (Project $project) use ($qualitat) {
                if ($qualitat) {
                    $project->sections()->attach($qualitat);
                }

                OrderFabrication::factory()
                    ->count(2)
                    ->for($project)
                    ->create()
                    ->each(function (OrderFabrication $orderFabrication) use ($project) {
                        Equipment::factory()
                            ->count(3)
                            ->create([
                                'project_id' => $project->id,
                                'order_fabrication_id' => $orderFabrication->id,
                            ]);
                    });
            });
    }
}

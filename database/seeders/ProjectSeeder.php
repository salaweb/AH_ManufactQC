<?php

namespace Database\Seeders;

use App\Models\DescriptionTag;
use App\Models\Equipment;
use App\Models\Family;
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
        $families = Family::all();
        $tags = DescriptionTag::all();

        Project::factory()
            ->count(3)
            ->when($families->isNotEmpty(), fn ($factory) => $factory->state(fn () => ['family_id' => $families->random()->id]))
            ->create()
            ->each(function (Project $project) use ($qualitat, $tags) {
                if ($qualitat) {
                    $project->sections()->attach($qualitat);
                }

                if ($tags->isNotEmpty()) {
                    $project->descriptionTags()->attach($tags->random(min(2, $tags->count()))->pluck('id'));
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

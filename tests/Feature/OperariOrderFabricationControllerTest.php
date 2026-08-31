<?php

use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->operari()->create());
});

it('searches order fabrications by partial number, with the project, its family, its sections and the equipment count loaded', function () {
    $project = Project::factory()->create(['number' => '1400C0137.00']);
    $section = Section::factory()->create(['name' => 'AH17DX2']);
    $project->sections()->attach($section, ['order' => 0]);
    $orderFabrication = OrderFabrication::factory()->for($project)->create(['number' => '2026/01/0000123']);
    Equipment::factory()->for($project)->for($orderFabrication)->count(4)->create();
    OrderFabrication::factory()->create(['number' => '2026/01/0000999']);

    $response = $this->getJson('/operari/api/order-fabrications?q=0000123');

    $response->assertOk()->assertJsonCount(1);
    expect($response->json('0.project.number'))->toBe('1400C0137.00')
        ->and($response->json('0.project.family.name'))->toBe($project->family->name)
        ->and($response->json('0.project.sections.0.name'))->toBe('AH17DX2')
        ->and($response->json('0.equipment_count'))->toBe(4);
});

it('lets an admin access the operari order fabrication search directly', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->getJson('/operari/api/order-fabrications');

    $response->assertOk();
});

<?php

use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->operari()->create());
});

it('searches projects by partial number', function () {
    Project::factory()->create(['number' => '1400C0001.00']);
    Project::factory()->create(['number' => '1400C0002.00']);
    Project::factory()->create(['number' => '9999999999.00']);

    $response = $this->getJson('/operari/api/projects?q=1400C');

    $response->assertOk()->assertJsonCount(2);
});

it('lists order fabrications for a project', function () {
    $project = Project::factory()->create();
    OrderFabrication::factory()->for($project)->count(2)->create();

    $response = $this->getJson("/operari/api/projects/{$project->id}/order-fabrications");

    $response->assertOk()->assertJsonCount(2);
});

it('blocks an admin from the operari project search', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->getJson('/operari/api/projects');

    $response->assertForbidden();
});

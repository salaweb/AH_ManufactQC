<?php

use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->operari()->create());
});

it('searches order fabrications by partial number, with the project loaded', function () {
    $project = Project::factory()->create(['number' => '1400C0137.00']);
    OrderFabrication::factory()->for($project)->create(['number' => '2026/01/0000123']);
    OrderFabrication::factory()->create(['number' => '2026/01/0000999']);

    $response = $this->getJson('/operari/api/order-fabrications?q=0000123');

    $response->assertOk()->assertJsonCount(1);
    expect($response->json('0.project.number'))->toBe('1400C0137.00');
});

it('blocks an admin from the operari order fabrication search', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->getJson('/operari/api/order-fabrications');

    $response->assertForbidden();
});

<?php

use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('creates an order fabrication for a project', function () {
    $project = Project::factory()->create();

    $response = $this->postJson('/api/order-fabrications', [
        'project_id' => $project->id,
        'number' => 'OF-1001',
    ]);

    $response->assertCreated();
    expect(OrderFabrication::where('number', 'OF-1001')->exists())->toBeTrue();
});

it('rejects a duplicate OF number within the same project', function () {
    $project = Project::factory()->create();
    OrderFabrication::factory()->for($project)->create(['number' => 'OF-1001']);

    $response = $this->postJson('/api/order-fabrications', [
        'project_id' => $project->id,
        'number' => 'OF-1001',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('number');
});

it('rejects a duplicate OF number even across different projects, since OF numbers are globally unique', function () {
    OrderFabrication::factory()->create(['number' => 'OF-1001']);
    $otherProject = Project::factory()->create();

    $response = $this->postJson('/api/order-fabrications', [
        'project_id' => $otherProject->id,
        'number' => 'OF-1001',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('number');
});

it('lists order fabrications filtered by project', function () {
    $project = Project::factory()->create();
    OrderFabrication::factory()->for($project)->count(2)->create();
    OrderFabrication::factory()->count(3)->create();

    $response = $this->getJson("/api/order-fabrications?project_id={$project->id}");

    $response->assertOk()->assertJsonCount(2);
});

it('includes the project and equipment count when listing order fabrications', function () {
    $project = Project::factory()->create();
    $orderFabrication = OrderFabrication::factory()->for($project)->create();
    Equipment::factory()->for($project)->for($orderFabrication)->count(3)->create();

    $response = $this->getJson('/api/order-fabrications');

    $response->assertOk();
    $data = collect($response->json())->firstWhere('id', $orderFabrication->id);
    expect($data['project']['id'])->toBe($project->id)
        ->and($data['equipment_count'])->toBe(3);
});

it('deletes an order fabrication', function () {
    $orderFabrication = OrderFabrication::factory()->create();

    $response = $this->deleteJson("/api/order-fabrications/{$orderFabrication->id}");

    $response->assertNoContent();
    expect(OrderFabrication::find($orderFabrication->id))->toBeNull();
});

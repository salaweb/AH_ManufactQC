<?php

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

it('allows the same OF number in a different project', function () {
    OrderFabrication::factory()->create(['number' => 'OF-1001']);
    $otherProject = Project::factory()->create();

    $response = $this->postJson('/api/order-fabrications', [
        'project_id' => $otherProject->id,
        'number' => 'OF-1001',
    ]);

    $response->assertCreated();
});

it('lists order fabrications filtered by project', function () {
    $project = Project::factory()->create();
    OrderFabrication::factory()->for($project)->count(2)->create();
    OrderFabrication::factory()->count(3)->create();

    $response = $this->getJson("/api/order-fabrications?project_id={$project->id}");

    $response->assertOk()->assertJsonCount(2);
});

it('deletes an order fabrication', function () {
    $orderFabrication = OrderFabrication::factory()->create();

    $response = $this->deleteJson("/api/order-fabrications/{$orderFabrication->id}");

    $response->assertNoContent();
    expect(OrderFabrication::find($orderFabrication->id))->toBeNull();
});

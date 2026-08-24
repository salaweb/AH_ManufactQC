<?php

use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('creates a valid project', function () {
    $response = $this->postJson('/api/projects', [
        'number' => '1400C0001.00',
        'family' => 'DB2',
        'description' => 'Test project',
    ]);

    $response->assertCreated();
    expect(Project::where('number', '1400C0001.00')->exists())->toBeTrue();
});

it('rejects creating a project without a number', function () {
    $response = $this->postJson('/api/projects', [
        'family' => 'DB2',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('number');
});

it('updates a project', function () {
    $project = Project::factory()->create(['family' => 'DB2']);

    $response = $this->putJson("/api/projects/{$project->id}", [
        'number' => $project->number,
        'family' => 'DB3',
    ]);

    $response->assertOk();
    expect($project->fresh()->family)->toBe('DB3');
});

it('deletes a project and cascades to its order fabrications and equipment', function () {
    $project = Project::factory()->create();
    $orderFabrication = OrderFabrication::factory()->for($project)->create();
    Equipment::factory()->create([
        'project_id' => $project->id,
        'order_fabrication_id' => $orderFabrication->id,
    ]);

    $response = $this->deleteJson("/api/projects/{$project->id}");

    $response->assertNoContent();
    expect(Project::find($project->id))->toBeNull()
        ->and(OrderFabrication::find($orderFabrication->id))->toBeNull();
});

<?php

use App\Enums\EquipmentStatus;
use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('creates an equipment serial number with pending status by default', function () {
    $project = Project::factory()->create();
    $orderFabrication = OrderFabrication::factory()->for($project)->create();

    $response = $this->postJson('/api/equipment', [
        'project_id' => $project->id,
        'order_fabrication_id' => $orderFabrication->id,
        'serie_number' => 'SN-0001',
    ]);

    $response->assertCreated();
    $equipment = Equipment::where('serie_number', 'SN-0001')->firstOrFail();
    expect($equipment->status)->toBe(EquipmentStatus::Pending)
        ->and($equipment->checked_at)->toBeNull();
});

it('rejects an order_fabrication_id that does not belong to the given project', function () {
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $orderFabrication = OrderFabrication::factory()->for($otherProject)->create();

    $response = $this->postJson('/api/equipment', [
        'project_id' => $project->id,
        'order_fabrication_id' => $orderFabrication->id,
        'serie_number' => 'SN-0001',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('order_fabrication_id');
});

it('rejects a duplicate serie_number within the same project', function () {
    $project = Project::factory()->create();
    $orderFabrication = OrderFabrication::factory()->for($project)->create();
    Equipment::factory()->create([
        'project_id' => $project->id,
        'order_fabrication_id' => $orderFabrication->id,
        'serie_number' => 'SN-0001',
    ]);

    $response = $this->postJson('/api/equipment', [
        'project_id' => $project->id,
        'order_fabrication_id' => $orderFabrication->id,
        'serie_number' => 'SN-0001',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('serie_number');
});

it('lists equipment filtered by order fabrication', function () {
    $orderFabrication = OrderFabrication::factory()->create();
    Equipment::factory()->count(2)->create([
        'project_id' => $orderFabrication->project_id,
        'order_fabrication_id' => $orderFabrication->id,
    ]);
    Equipment::factory()->count(3)->create();

    $response = $this->getJson("/api/equipment?order_fabrication_id={$orderFabrication->id}");

    $response->assertOk()->assertJsonCount(2);
});

it('deletes an equipment record', function () {
    $equipment = Equipment::factory()->create();

    $response = $this->deleteJson("/api/equipment/{$equipment->id}");

    $response->assertNoContent();
    expect(Equipment::find($equipment->id))->toBeNull();
});

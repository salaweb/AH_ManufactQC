<?php

use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\Section;
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

it('creates a project with the selected sections attached', function () {
    $sectionA = Section::factory()->create();
    $sectionB = Section::factory()->create();
    Section::factory()->create(); // not selected

    $response = $this->postJson('/api/projects', [
        'number' => '1400C0002.00',
        'family' => 'DB2',
        'section_ids' => [$sectionA->id, $sectionB->id],
    ]);

    $response->assertCreated();
    $project = Project::where('number', '1400C0002.00')->firstOrFail();
    expect($project->sections)->toHaveCount(2);
});

it('updates the sections attached to a project', function () {
    $project = Project::factory()->create();
    $sectionA = Section::factory()->create();
    $sectionB = Section::factory()->create();
    $project->sections()->attach($sectionA);

    $response = $this->putJson("/api/projects/{$project->id}", [
        'number' => $project->number,
        'family' => $project->family,
        'section_ids' => [$sectionB->id],
    ]);

    $response->assertOk();
    expect($project->fresh()->sections->pluck('id')->all())->toBe([$sectionB->id]);
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

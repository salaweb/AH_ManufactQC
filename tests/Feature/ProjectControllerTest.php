<?php

use App\Models\DescriptionTag;
use App\Models\Equipment;
use App\Models\Family;
use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('creates a valid project', function () {
    $family = Family::factory()->create();

    $response = $this->postJson('/api/projects', [
        'number' => '1400C0001.00',
        'family_id' => $family->id,
    ]);

    $response->assertCreated();
    expect(Project::where('number', '1400C0001.00')->exists())->toBeTrue();
});

it('rejects creating a project without a number', function () {
    $family = Family::factory()->create();

    $response = $this->postJson('/api/projects', [
        'family_id' => $family->id,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('number');
});

it('rejects creating a project without a family', function () {
    $response = $this->postJson('/api/projects', [
        'number' => '1400C0001.00',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('family_id');
});

it('creates a project with the selected sections and description tags attached', function () {
    $family = Family::factory()->create();
    $sectionA = Section::factory()->create();
    $sectionB = Section::factory()->create();
    Section::factory()->create(); // not selected
    $tagA = DescriptionTag::factory()->create();
    $tagB = DescriptionTag::factory()->create();

    $response = $this->postJson('/api/projects', [
        'number' => '1400C0002.00',
        'family_id' => $family->id,
        'section_ids' => [$sectionA->id, $sectionB->id],
        'description_tag_ids' => [$tagA->id, $tagB->id],
    ]);

    $response->assertCreated();
    $project = Project::where('number', '1400C0002.00')->firstOrFail();
    expect($project->sections)->toHaveCount(2)
        ->and($project->descriptionTags)->toHaveCount(2);
});

it('preserves the order description tags were given in, not alphabetical order', function () {
    $family = Family::factory()->create();
    $tagUsb = DescriptionTag::factory()->create(['name' => 'USB']);
    $tagAh = DescriptionTag::factory()->create(['name' => 'AH17DX2']);
    $tagTs = DescriptionTag::factory()->create(['name' => 'TS']);

    $response = $this->postJson('/api/projects', [
        'number' => '1400C0003.00',
        'family_id' => $family->id,
        'description_tag_ids' => [$tagAh->id, $tagTs->id, $tagUsb->id],
    ]);

    $response->assertCreated();
    $project = Project::where('number', '1400C0003.00')->firstOrFail();
    expect($project->descriptionTags->pluck('name')->all())->toBe(['AH17DX2', 'TS', 'USB']);

    // re-order on update: USB first now
    $this->putJson("/api/projects/{$project->id}", [
        'number' => $project->number,
        'family_id' => $project->family_id,
        'description_tag_ids' => [$tagUsb->id, $tagAh->id, $tagTs->id],
    ])->assertOk();

    expect($project->fresh()->descriptionTags->pluck('name')->all())->toBe(['USB', 'AH17DX2', 'TS']);
});

it('updates the sections attached to a project', function () {
    $project = Project::factory()->create();
    $sectionA = Section::factory()->create();
    $sectionB = Section::factory()->create();
    $project->sections()->attach($sectionA);

    $response = $this->putJson("/api/projects/{$project->id}", [
        'number' => $project->number,
        'family_id' => $project->family_id,
        'section_ids' => [$sectionB->id],
    ]);

    $response->assertOk();
    expect($project->fresh()->sections->pluck('id')->all())->toBe([$sectionB->id]);
});

it('updates a project', function () {
    $family = Family::factory()->create();
    $newFamily = Family::factory()->create();
    $project = Project::factory()->create(['family_id' => $family->id]);

    $response = $this->putJson("/api/projects/{$project->id}", [
        'number' => $project->number,
        'family_id' => $newFamily->id,
    ]);

    $response->assertOk();
    expect($project->fresh()->family_id)->toBe($newFamily->id);
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

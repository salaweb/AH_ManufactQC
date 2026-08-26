<?php

use App\Enums\AnswerResponse;
use App\Enums\EquipmentStatus;
use App\Models\Answer;
use App\Models\Defect;
use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(User::factory()->operari()->create());
});

it('lists equipment for an order fabrication, alongside the OF, its project, family and sections', function () {
    $orderFabrication = OrderFabrication::factory()->create();
    $section = Section::factory()->create(['name' => 'AH17DX2']);
    $orderFabrication->project->sections()->attach($section, ['order' => 0]);
    Equipment::factory()->count(3)->create([
        'project_id' => $orderFabrication->project_id,
        'order_fabrication_id' => $orderFabrication->id,
    ]);

    $response = $this->getJson("/operari/api/order-fabrications/{$orderFabrication->id}/equipment");

    $response->assertOk()->assertJsonCount(3, 'equipment');
    expect($response->json('order_fabrication.id'))->toBe($orderFabrication->id)
        ->and($response->json('order_fabrication.project.id'))->toBe($orderFabrication->project_id)
        ->and($response->json('order_fabrication.project.family.id'))->toBe($orderFabrication->project->family_id)
        ->and($response->json('order_fabrication.project.sections.0.name'))->toBe('AH17DX2');
});

it('shows equipment with sections, questions and existing answers', function () {
    $equipment = Equipment::factory()->create();
    $section = Section::factory()->create(['order' => 1]);
    $equipment->project->sections()->attach($section);
    $question = Question::factory()->create(['section_id' => $section->id, 'order' => 1]);
    Answer::factory()->create([
        'equipment_id' => $equipment->id,
        'question_id' => $question->id,
        'response' => AnswerResponse::Yes,
    ]);

    $response = $this->getJson("/operari/api/equipment/{$equipment->id}");

    $response->assertOk();
    $payload = $response->json();

    expect($payload['equipment']['id'])->toBe($equipment->id)
        ->and($payload['sections'][0]['questions'][0]['answer']['response'])->toBe('yes');
});

it('only shows sections assigned to the equipment\'s project, not every section in the system', function () {
    $equipment = Equipment::factory()->create();
    $assignedSection = Section::factory()->create(['name' => 'ASSIGNED']);
    $otherSection = Section::factory()->create(['name' => 'NOT_ASSIGNED']);
    $equipment->project->sections()->attach($assignedSection);

    $response = $this->getJson("/operari/api/equipment/{$equipment->id}");

    $sectionNames = collect($response->json('sections'))->pluck('name');

    expect($sectionNames)->toContain('ASSIGNED')
        ->not->toContain('NOT_ASSIGNED');
});

it('saves equipment observations progressively', function () {
    $equipment = Equipment::factory()->create();

    $response = $this->patchJson("/operari/api/equipment/{$equipment->id}", [
        'observations' => 'Tot correcte, sense incidències.',
    ]);

    $response->assertOk();
    expect($equipment->fresh()->observations)->toBe('Tot correcte, sense incidències.');
});

it('finalizes equipment as ok when there are no defects and no observations', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create(['observations' => null]);

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", [
        'photos' => [UploadedFile::fake()->image('photo1.jpg')],
    ]);

    $response->assertOk();
    $equipment->refresh();
    expect($equipment->status)->toBe(EquipmentStatus::Ok)
        ->and($equipment->checked_at)->not->toBeNull()
        ->and($equipment->photos)->toHaveCount(1);
    Storage::disk('photos')->assertExists($equipment->photos->first()->path);
});

it('finalizes equipment as defect when it has defects, even with photos', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create();
    Defect::factory()->create(['equipment_id' => $equipment->id]);

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", []);

    $response->assertOk();
    expect($equipment->fresh()->status)->toBe(EquipmentStatus::Defect);
});

it('finalizes equipment as observation when it has observations but no defects', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create(['observations' => 'Detall a revisar en propera visita.']);

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", []);

    $response->assertOk();
    expect($equipment->fresh()->status)->toBe(EquipmentStatus::Observation);
});

it('allows finishing without any photos, since they are optional', function () {
    Storage::fake('photos');
    $equipment = Equipment::factory()->create();

    $response = $this->postJson("/operari/api/equipment/{$equipment->id}/photos", []);

    $response->assertOk();
    expect($equipment->fresh()->checked_at)->not->toBeNull();
});

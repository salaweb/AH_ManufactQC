<?php

use App\Enums\AnswerResponse;
use App\Models\Answer;
use App\Models\Defect;
use App\Models\Equipment;
use App\Models\Photo;
use App\Models\Project;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('computes stats: totals, checked count and completion percentage', function () {
    $project = Project::factory()->create();
    Equipment::factory()->count(2)->create(['project_id' => $project->id, 'checked_at' => now()]);
    Equipment::factory()->count(2)->create(['project_id' => $project->id, 'checked_at' => null]);

    $response = $this->getJson("/api/dashboard?project_id={$project->id}");

    $response->assertOk();
    $stats = $response->json('stats');

    expect($stats['total_equipment'])->toBe(4)
        ->and($stats['checked_equipment'])->toBe(2)
        ->and($stats['completion_percentage'])->toBe(50);
});

it('groups defects by tipo, only counting defects on checked equipment', function () {
    $checked = Equipment::factory()->create(['checked_at' => now()]);
    $unchecked = Equipment::factory()->create(['checked_at' => null]);
    Defect::factory()->create(['equipment_id' => $checked->id, 'tipo' => 'visual']);
    Defect::factory()->create(['equipment_id' => $checked->id, 'tipo' => 'visual']);
    Defect::factory()->create(['equipment_id' => $checked->id, 'tipo' => 'dimensional']);
    Defect::factory()->create(['equipment_id' => $unchecked->id, 'tipo' => 'visual']);

    $response = $this->getJson('/api/dashboard');

    $response->assertOk();
    $byType = collect($response->json('defects_by_type'))->keyBy('tipo');

    expect($byType['visual']['count'])->toBe(2)
        ->and($byType['dimensional']['count'])->toBe(1);
});

it('groups defects by responsibility', function () {
    $equipment = Equipment::factory()->create(['checked_at' => now()]);
    Defect::factory()->create(['equipment_id' => $equipment->id, 'responsibility' => 'producció']);
    Defect::factory()->create(['equipment_id' => $equipment->id, 'responsibility' => 'producció']);
    Defect::factory()->create(['equipment_id' => $equipment->id, 'responsibility' => 'disseny']);

    $response = $this->getJson('/api/dashboard');

    $byResponsibility = collect($response->json('responsibilities'))->keyBy('responsibility');

    expect($byResponsibility['producció']['count'])->toBe(2)
        ->and($byResponsibility['disseny']['count'])->toBe(1);
});

it('computes defect rate trends per section', function () {
    $section = Section::factory()->create();
    $question = Question::factory()->create(['section_id' => $section->id]);
    $equipmentA = Equipment::factory()->create(['checked_at' => now()]);
    $equipmentB = Equipment::factory()->create(['checked_at' => now()]);
    Answer::factory()->create(['equipment_id' => $equipmentA->id, 'question_id' => $question->id, 'response' => AnswerResponse::Defect]);
    Answer::factory()->create(['equipment_id' => $equipmentB->id, 'question_id' => $question->id, 'response' => AnswerResponse::Yes]);

    $response = $this->getJson('/api/dashboard');

    $trend = collect($response->json('trends'))->firstWhere('section', $section->name);

    expect($trend['total_answers'])->toBe(2)
        ->and($trend['defect_answers'])->toBe(1)
        ->and($trend['defect_rate'])->toBe(50);
});

it('returns recent photos for checked equipment only', function () {
    $checked = Equipment::factory()->create(['checked_at' => now(), 'serie_number' => 'SN-CHECKED']);
    Photo::factory()->create(['equipment_id' => $checked->id]);

    $response = $this->getJson('/api/dashboard');

    $photos = $response->json('recent_photos');

    expect($photos)->toHaveCount(1)
        ->and($photos[0]['serie_number'])->toBe('SN-CHECKED');
});

it('blocks an operari from the dashboard endpoint', function () {
    $operari = User::factory()->operari()->create();

    $response = $this->actingAs($operari)->getJson('/api/dashboard');

    $response->assertForbidden();
});

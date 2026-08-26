<?php

use App\Enums\AnswerResponse;
use App\Enums\EquipmentStatus;
use App\Models\Answer;
use App\Models\Defect;
use App\Models\Equipment;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->operari()->create());
});

it('creates an answer for a question', function () {
    $equipment = Equipment::factory()->create();
    $question = Question::factory()->create();

    $response = $this->postJson('/operari/api/answers', [
        'equipment_id' => $equipment->id,
        'question_id' => $question->id,
        'response' => 'yes',
        'language_chosen' => 'ca',
    ]);

    $response->assertCreated();
    expect(Answer::where('equipment_id', $equipment->id)->where('question_id', $question->id)->first()->response)
        ->toBe(AnswerResponse::Yes);
});

it('updates the existing answer instead of duplicating it when answered again', function () {
    $equipment = Equipment::factory()->create();
    $question = Question::factory()->create();
    Answer::factory()->create([
        'equipment_id' => $equipment->id,
        'question_id' => $question->id,
        'response' => AnswerResponse::No,
    ]);

    $this->postJson('/operari/api/answers', [
        'equipment_id' => $equipment->id,
        'question_id' => $question->id,
        'response' => 'defect',
    ])->assertCreated();

    expect(Answer::where('equipment_id', $equipment->id)->where('question_id', $question->id)->count())->toBe(1)
        ->and(Answer::where('equipment_id', $equipment->id)->where('question_id', $question->id)->first()->response)
        ->toBe(AnswerResponse::Defect);
});

it('deletes an answer, so a question can be left unanswered again after clicking the same option twice', function () {
    $answer = Answer::factory()->create();

    $response = $this->deleteJson("/operari/api/answers/{$answer->id}");

    $response->assertNoContent();
    expect(Answer::find($answer->id))->toBeNull();
});

it('reverts an already finished equipment back to unfinished when one of its answers is changed to defect', function () {
    $equipment = Equipment::factory()->create([
        'status' => EquipmentStatus::Ok,
        'checked_at' => now(),
    ]);
    $section = Section::factory()->create();
    $equipment->project->sections()->attach($section);
    $question = Question::factory()->create(['section_id' => $section->id, 'is_required' => true]);
    Answer::factory()->create([
        'equipment_id' => $equipment->id,
        'question_id' => $question->id,
        'response' => AnswerResponse::Yes,
    ]);

    $response = $this->postJson('/operari/api/answers', [
        'equipment_id' => $equipment->id,
        'question_id' => $question->id,
        'response' => 'defect',
    ]);

    $response->assertCreated();
    expect($equipment->fresh()->checked_at)->toBeNull()
        ->and($equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects);
});

it('reverts an already finished equipment back to unfinished when one of its answers is deleted', function () {
    $equipment = Equipment::factory()->create([
        'status' => EquipmentStatus::OkWithDefects,
        'checked_at' => now(),
    ]);
    $section = Section::factory()->create();
    $equipment->project->sections()->attach($section);
    $question = Question::factory()->create(['section_id' => $section->id, 'is_required' => true]);
    $answer = Answer::factory()->create([
        'equipment_id' => $equipment->id,
        'question_id' => $question->id,
        'response' => AnswerResponse::Defect,
    ]);
    Defect::factory()->create(['equipment_id' => $equipment->id, 'answer_id' => $answer->id]);

    $response = $this->deleteJson("/operari/api/answers/{$answer->id}");

    $response->assertNoContent();
    // No answer is left at all — including the one that said "defect" — so the live,
    // answer-driven rule for the not-yet-finished state reports plain Pending, even
    // though the (now orphaned) Defect row still exists.
    expect($equipment->fresh()->checked_at)->toBeNull()
        ->and($equipment->fresh()->status)->toBe(EquipmentStatus::Pending);
});

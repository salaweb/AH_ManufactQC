<?php

use App\Enums\EquipmentStatus;
use App\Models\Answer;
use App\Models\Equipment;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/**
 * Exhaustive coverage of the asymmetric status model:
 *
 * - While NOT finished, "amb defectes" tracks the LIVE state — only questions
 *   *currently* answered "defect" count, so the badge updates the moment a
 *   question is answered differently (Pendent <-> Pendent amb defectes).
 * - Finalizing is blocked outright while ANY question currently reads "defect"
 *   (vermell), even if that defect is already fully documented — a defect must
 *   be resolved (answered yes/no) before the review can be finished.
 * - Once finished, "amb defectes" is a permanent record of the review — it
 *   depends on whether a Defect row was EVER recorded for this equipment,
 *   regardless of what any answer currently says (Correcte <-> Correcte amb
 *   defectes). Deleting the last Defect row retroactively clears it, since
 *   deleting a defect means "this was recorded by mistake". Marking a question
 *   "defect" again on an already-finished equipment un-finishes it (clears
 *   `checked_at`), reverting to the live rule until it's resolved and
 *   re-finalized.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->operari()->create());

    $this->equipment = Equipment::factory()->create();
    $this->section = Section::factory()->create();
    $this->equipment->project->sections()->attach($this->section);
    $this->questionA = Question::factory()->create(['section_id' => $this->section->id, 'is_required' => true]);
    $this->questionB = Question::factory()->create(['section_id' => $this->section->id, 'is_required' => true]);
});

function postEquipmentAnswer($test, Question $question, string $response): void
{
    $test->postJson('/operari/api/answers', [
        'equipment_id' => $test->equipment->id,
        'question_id' => $question->id,
        'response' => $response,
    ])->assertCreated();
}

function equipmentAnswerId($test, Question $question): int
{
    return Answer::where('equipment_id', $test->equipment->id)
        ->where('question_id', $question->id)
        ->value('id');
}

function postEquipmentDefect($test, Question $question): array
{
    $answerId = equipmentAnswerId($test, $question);

    return $test->postJson('/operari/api/defects', [
        'equipment_id' => $test->equipment->id,
        'answer_id' => $answerId,
        'tipo' => 'visual',
    ])->assertCreated()->json();
}

function finalizeEquipmentReview($test): TestResponse
{
    Storage::fake('photos');

    return $test->postJson("/operari/api/equipment/{$test->equipment->id}/photos", []);
}

// --- While reviewing (not yet finished): live, answer-driven ---

it('stays pending while answering non-defect questions, even once every required question is answered', function () {
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Pending);

    postEquipmentAnswer($this, $this->questionA, 'yes');
    postEquipmentAnswer($this, $this->questionB, 'no');

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Pending)
        ->and($this->equipment->fresh()->checked_at)->toBeNull();
});

it('becomes pending-with-defects the moment a question is answered defect, even before a defect record is saved', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects);
});

it('reverts to pending the moment the defect answer changes to yes, regardless of whether its defect record still exists', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');
    postEquipmentDefect($this, $this->questionA);
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects);

    postEquipmentAnswer($this, $this->questionA, 'yes');

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Pending);
});

it('stays pending-with-defects when the defect record is deleted but its answer is still marked defect', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');
    $defect = postEquipmentDefect($this, $this->questionA);
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects);

    $this->deleteJson("/operari/api/defects/{$defect['id']}")->assertNoContent();

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects);
});

it('keeps pending-with-defects while at least one other question is still answered defect', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');
    postEquipmentDefect($this, $this->questionA);
    postEquipmentAnswer($this, $this->questionB, 'defect');
    postEquipmentDefect($this, $this->questionB);
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects);

    postEquipmentAnswer($this, $this->questionA, 'yes');

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects);

    postEquipmentAnswer($this, $this->questionB, 'no');

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Pending);
});

it('blocks finalizing (and leaves the pending-with-defects badge as is) when a defect answer has no defect recorded', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');
    postEquipmentAnswer($this, $this->questionB, 'yes');
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects);

    $response = finalizeEquipmentReview($this);

    $response->assertUnprocessable();
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects)
        ->and($this->equipment->fresh()->checked_at)->toBeNull();
});

it('blocks finalizing when a required question is unanswered', function () {
    postEquipmentAnswer($this, $this->questionA, 'yes');

    $response = finalizeEquipmentReview($this);

    $response->assertUnprocessable();
    expect($this->equipment->fresh()->checked_at)->toBeNull();
});

it('blocks finalizing when a question is still marked defect, even though it already has a documented defect record', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');
    postEquipmentDefect($this, $this->questionA);
    postEquipmentAnswer($this, $this->questionB, 'yes');
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects);

    $response = finalizeEquipmentReview($this);

    $response->assertUnprocessable();
    expect($this->equipment->fresh()->checked_at)->toBeNull()
        ->and($this->equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects);
});

// --- Finalizing: snapshots the historical defect record ---

it('finalizes as ok when every required question is answered without defects', function () {
    postEquipmentAnswer($this, $this->questionA, 'yes');
    postEquipmentAnswer($this, $this->questionB, 'no');

    finalizeEquipmentReview($this)->assertOk();

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Ok)
        ->and($this->equipment->fresh()->checked_at)->not->toBeNull();
});

it('finalizes as ok-with-defects when a defect was recorded and then resolved before finishing', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');
    postEquipmentDefect($this, $this->questionA);
    postEquipmentAnswer($this, $this->questionA, 'yes'); // resolved before finalizing, as required
    postEquipmentAnswer($this, $this->questionB, 'yes');

    finalizeEquipmentReview($this)->assertOk();

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::OkWithDefects)
        ->and($this->equipment->fresh()->checked_at)->not->toBeNull();
});

it('still finalizes as ok-with-defects even if the defect answer was flipped back to yes before finishing, since the defect record remains', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');
    postEquipmentDefect($this, $this->questionA);
    postEquipmentAnswer($this, $this->questionA, 'yes'); // flipped back to yes, defect record untouched
    postEquipmentAnswer($this, $this->questionB, 'yes');
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Pending); // live badge is clean right now

    finalizeEquipmentReview($this)->assertOk();

    // ...but finalizing still remembers a defect genuinely was found during this review.
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::OkWithDefects);
});

// --- After finishing: historical, defect-record-driven ---

it('reverts an ok equipment back to pending when one of its answers is deleted', function () {
    postEquipmentAnswer($this, $this->questionA, 'yes');
    postEquipmentAnswer($this, $this->questionB, 'no');
    finalizeEquipmentReview($this)->assertOk();
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Ok);

    $answerId = equipmentAnswerId($this, $this->questionA);
    $this->deleteJson("/operari/api/answers/{$answerId}")->assertNoContent();

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Pending)
        ->and($this->equipment->fresh()->checked_at)->toBeNull();
});

it('reverts an ok-with-defects equipment back to pending-with-defects when a non-defect answer is deleted', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');
    postEquipmentDefect($this, $this->questionA);
    postEquipmentAnswer($this, $this->questionA, 'yes'); // resolved before finalizing, as required
    postEquipmentAnswer($this, $this->questionB, 'yes');
    finalizeEquipmentReview($this)->assertOk();
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::OkWithDefects);

    $answerIdB = equipmentAnswerId($this, $this->questionB);
    $this->deleteJson("/operari/api/answers/{$answerIdB}")->assertNoContent();

    // No longer finished (questionB unanswered again), so we're back to the live rule:
    // questionA currently says "yes", so no live defect right now either.
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Pending)
        ->and($this->equipment->fresh()->checked_at)->toBeNull();
});

it('un-finishes an ok-with-defects equipment the moment a question is marked defect again, reverting to the live rule', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');
    postEquipmentDefect($this, $this->questionA);
    postEquipmentAnswer($this, $this->questionA, 'yes'); // resolved before finalizing, as required
    postEquipmentAnswer($this, $this->questionB, 'yes');
    finalizeEquipmentReview($this)->assertOk();
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::OkWithDefects);

    // Editing question A back to "defect" (e.g. the issue resurfaced) re-opens the review.
    postEquipmentAnswer($this, $this->questionA, 'defect');

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::PendingWithDefects)
        ->and($this->equipment->fresh()->checked_at)->toBeNull();
});

it('downgrades ok-with-defects to ok the moment the last defect record is deleted, without needing to re-finalize', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');
    $defect = postEquipmentDefect($this, $this->questionA);
    postEquipmentAnswer($this, $this->questionA, 'yes'); // resolved before finalizing, as required
    postEquipmentAnswer($this, $this->questionB, 'yes');
    finalizeEquipmentReview($this)->assertOk();
    $checkedAt = $this->equipment->fresh()->checked_at;
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::OkWithDefects);

    $this->deleteJson("/operari/api/defects/{$defect['id']}")->assertNoContent();

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Ok)
        ->and($this->equipment->fresh()->checked_at)->not->toBeNull()
        ->and($this->equipment->fresh()->checked_at->eq($checkedAt))->toBeTrue();
});

it('keeps ok-with-defects when only one of several defect records is deleted', function () {
    postEquipmentAnswer($this, $this->questionA, 'defect');
    $defect1 = postEquipmentDefect($this, $this->questionA);
    postEquipmentAnswer($this, $this->questionB, 'defect');
    postEquipmentDefect($this, $this->questionB);
    postEquipmentAnswer($this, $this->questionA, 'yes'); // resolved before finalizing, as required
    postEquipmentAnswer($this, $this->questionB, 'no');

    finalizeEquipmentReview($this)->assertOk();
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::OkWithDefects);

    $this->deleteJson("/operari/api/defects/{$defect1['id']}")->assertNoContent();

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::OkWithDefects);
});

it('changing an answer value after finishing does not affect status by itself, since only defect records matter once finished', function () {
    postEquipmentAnswer($this, $this->questionA, 'yes');
    postEquipmentAnswer($this, $this->questionB, 'no');
    finalizeEquipmentReview($this)->assertOk();
    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Ok);

    postEquipmentAnswer($this, $this->questionB, 'yes'); // still no defect record anywhere

    expect($this->equipment->fresh()->status)->toBe(EquipmentStatus::Ok)
        ->and($this->equipment->fresh()->checked_at)->not->toBeNull();
});

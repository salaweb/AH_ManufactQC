<?php

use App\Enums\QuestionCategory;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('creates a question tied to a section, with a category', function () {
    $section = Section::factory()->create();

    $response = $this->postJson('/api/questions', [
        'section_id' => $section->id,
        'text' => 'Acabat correcte?',
        'category' => QuestionCategory::Estetica->value,
        'order' => 1,
        'is_required' => true,
    ]);

    $response->assertCreated();
    $question = Question::where('section_id', $section->id)->first();
    expect($question)->not->toBeNull()
        ->and($question->category)->toBe(QuestionCategory::Estetica);
});

it('rejects creating a question without a category', function () {
    $section = Section::factory()->create();

    $response = $this->postJson('/api/questions', [
        'section_id' => $section->id,
        'text' => 'Acabat correcte?',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('category');
});

it('rejects creating a question without a valid section_id', function () {
    $response = $this->postJson('/api/questions', [
        'text' => 'Acabat correcte?',
        'section_id' => 9999,
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('section_id');
});

it('returns questions ordered by the order column', function () {
    $section = Section::factory()->create();
    Question::factory()->create(['section_id' => $section->id, 'order' => 2, 'text' => 'Second']);
    Question::factory()->create(['section_id' => $section->id, 'order' => 1, 'text' => 'First']);

    $response = $this->getJson("/api/questions?section_id={$section->id}");

    $response->assertOk();
    expect(collect($response->json())->pluck('text')->all())->toBe(['First', 'Second']);
});

it('reorders questions within a section via drag-and-drop order', function () {
    $section = Section::factory()->create();
    $first = Question::factory()->create(['section_id' => $section->id, 'order' => 0, 'text' => 'First']);
    $second = Question::factory()->create(['section_id' => $section->id, 'order' => 1, 'text' => 'Second']);
    $third = Question::factory()->create(['section_id' => $section->id, 'order' => 2, 'text' => 'Third']);

    $response = $this->postJson("/api/sections/{$section->id}/questions/reorder", [
        'question_ids' => [$third->id, $first->id, $second->id],
    ]);

    $response->assertOk();
    expect(collect($response->json())->pluck('text')->all())->toBe(['Third', 'First', 'Second'])
        ->and($third->fresh()->order)->toBe(0)
        ->and($first->fresh()->order)->toBe(1)
        ->and($second->fresh()->order)->toBe(2);
});

it('rejects reordering with a question id from another section', function () {
    $section = Section::factory()->create();
    $otherSection = Section::factory()->create();
    $question = Question::factory()->create(['section_id' => $section->id]);
    $foreignQuestion = Question::factory()->create(['section_id' => $otherSection->id]);

    $response = $this->postJson("/api/sections/{$section->id}/questions/reorder", [
        'question_ids' => [$question->id, $foreignQuestion->id],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('question_ids.1');
});

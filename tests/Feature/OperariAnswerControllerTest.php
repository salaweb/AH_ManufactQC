<?php

use App\Enums\AnswerResponse;
use App\Models\Answer;
use App\Models\Equipment;
use App\Models\Question;
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

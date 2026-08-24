<?php

use App\Models\Question;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('creates a question tied to a section', function () {
    $section = Section::factory()->create();

    $response = $this->postJson('/api/questions', [
        'section_id' => $section->id,
        'text' => 'Acabat correcte?',
        'order' => 1,
        'is_required' => true,
    ]);

    $response->assertCreated();
    expect(Question::where('section_id', $section->id)->exists())->toBeTrue();
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

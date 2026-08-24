<?php

use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('creates a section', function () {
    $response = $this->postJson('/api/sections', [
        'name' => 'QUALITAT',
        'description' => 'Comprovacions de qualitat',
    ]);

    $response->assertCreated();
    expect(Section::where('name', 'QUALITAT')->exists())->toBeTrue();
});

it('rejects creating a section without a name', function () {
    $response = $this->postJson('/api/sections', []);

    $response->assertUnprocessable()->assertJsonValidationErrors('name');
});

it('lists sections', function () {
    Section::factory()->count(2)->create();

    $response = $this->getJson('/api/sections');

    $response->assertOk()->assertJsonCount(2);
});

it('updates a section', function () {
    $section = Section::factory()->create(['name' => 'OLD']);

    $response = $this->putJson("/api/sections/{$section->id}", ['name' => 'NEW']);

    $response->assertOk();
    expect($section->fresh()->name)->toBe('NEW');
});

it('deletes a section', function () {
    $section = Section::factory()->create();

    $response = $this->deleteJson("/api/sections/{$section->id}");

    $response->assertNoContent();
    expect(Section::find($section->id))->toBeNull();
});

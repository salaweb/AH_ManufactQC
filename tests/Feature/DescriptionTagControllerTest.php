<?php

use App\Models\DescriptionTag;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('creates a description tag', function () {
    $response = $this->postJson('/api/description-tags', ['name' => 'USB']);

    $response->assertCreated();
    expect(DescriptionTag::where('name', 'USB')->exists())->toBeTrue();
});

it('rejects creating a duplicate description tag name', function () {
    DescriptionTag::factory()->create(['name' => 'USB']);

    $response = $this->postJson('/api/description-tags', ['name' => 'USB']);

    $response->assertUnprocessable()->assertJsonValidationErrors('name');
});

it('deletes a description tag and detaches it from any project using it', function () {
    $tag = DescriptionTag::factory()->create();
    $project = Project::factory()->create();
    $project->descriptionTags()->attach($tag);

    $response = $this->deleteJson("/api/description-tags/{$tag->id}");

    $response->assertNoContent();
    expect(DescriptionTag::find($tag->id))->toBeNull()
        ->and($project->fresh()->descriptionTags)->toHaveCount(0);
});

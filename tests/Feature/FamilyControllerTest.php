<?php

use App\Models\Family;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('creates a family', function () {
    $response = $this->postJson('/api/families', ['name' => 'DB2']);

    $response->assertCreated();
    expect(Family::where('name', 'DB2')->exists())->toBeTrue();
});

it('rejects creating a duplicate family name', function () {
    Family::factory()->create(['name' => 'DB2']);

    $response = $this->postJson('/api/families', ['name' => 'DB2']);

    $response->assertUnprocessable()->assertJsonValidationErrors('name');
});

it('lists families ordered by name', function () {
    Family::factory()->create(['name' => 'Talk']);
    Family::factory()->create(['name' => 'D2']);

    $response = $this->getJson('/api/families');

    $response->assertOk();
    expect(collect($response->json())->pluck('name')->all())->toBe(['D2', 'Talk']);
});

it('deletes an unused family', function () {
    $family = Family::factory()->create();

    $response = $this->deleteJson("/api/families/{$family->id}");

    $response->assertNoContent();
    expect(Family::find($family->id))->toBeNull();
});

it('rejects deleting a family that is in use by a project', function () {
    $family = Family::factory()->create();
    Project::factory()->create(['family_id' => $family->id]);

    $response = $this->deleteJson("/api/families/{$family->id}");

    $response->assertUnprocessable();
    expect(Family::find($family->id))->not->toBeNull();
});

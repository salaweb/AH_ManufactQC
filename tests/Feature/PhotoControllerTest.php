<?php

use App\Models\Equipment;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('streams the photo file for an admin', function () {
    Storage::fake('photos');
    Storage::disk('photos')->put('test.jpg', 'fake-image-bytes');
    $photo = Photo::factory()->create([
        'equipment_id' => Equipment::factory()->create()->id,
        'path' => 'test.jpg',
    ]);

    $response = $this->actingAs(User::factory()->admin()->create())->get("/api/photos/{$photo->id}");

    $response->assertOk();
});

it('returns a 404 instead of crashing when the file is missing from disk', function () {
    Storage::fake('photos');
    $photo = Photo::factory()->create([
        'equipment_id' => Equipment::factory()->create()->id,
        'path' => 'missing.jpg',
    ]);

    $response = $this->actingAs(User::factory()->admin()->create())->get("/api/photos/{$photo->id}");

    $response->assertNotFound();
});

it('blocks an operari from fetching a photo file', function () {
    Storage::fake('photos');
    Storage::disk('photos')->put('test.jpg', 'fake-image-bytes');
    $photo = Photo::factory()->create([
        'equipment_id' => Equipment::factory()->create()->id,
        'path' => 'test.jpg',
    ]);

    $response = $this->actingAs(User::factory()->operari()->create())->get("/api/photos/{$photo->id}");

    $response->assertForbidden();
});

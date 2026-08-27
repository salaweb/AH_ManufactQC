<?php

use App\Enums\UserRole;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('creates an admin user with an email', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'Nou Admin',
        'role' => 'admin',
        'email' => 'nou-admin@test.com',
        'password' => 'password123',
    ]);

    $response->assertCreated();
    $user = User::where('email', 'nou-admin@test.com')->firstOrFail();
    expect($user->role)->toBe(UserRole::Admin)
        ->and($user->username)->toBeNull();
});

it('creates an operari user with a username', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'Nou Operari',
        'role' => 'operari',
        'username' => 'nou_operari',
        'password' => 'password123',
    ]);

    $response->assertCreated();
    $user = User::where('username', 'nou_operari')->firstOrFail();
    expect($user->role)->toBe(UserRole::Operari)
        ->and($user->email)->toBeNull();
});

it('rejects creating an operari without a username', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'Sense Username',
        'role' => 'operari',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('username');
});

it('rejects creating an admin without an email', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'Sense Email',
        'role' => 'admin',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('updates a user without changing the password when none is given', function () {
    $user = User::factory()->admin()->create(['email' => 'keep@test.com']);
    $originalHash = $user->password;

    $response = $this->putJson("/api/users/{$user->id}", [
        'name' => 'Updated Name',
        'role' => 'admin',
        'email' => 'keep@test.com',
    ]);

    $response->assertOk();
    expect($user->fresh()->name)->toBe('Updated Name')
        ->and($user->fresh()->password)->toBe($originalHash);
});

it('creates an operari_produccio user with a username', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'Nou Operari Producció',
        'role' => 'operari_produccio',
        'username' => 'nou_operari_produccio',
        'password' => 'password123',
    ]);

    $response->assertCreated();
    $user = User::where('username', 'nou_operari_produccio')->firstOrFail();
    expect($user->role)->toBe(UserRole::OperariProduccio)
        ->and($user->email)->toBeNull();
});

it('rejects creating an operari_produccio without a username', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'Sense Username',
        'role' => 'operari_produccio',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('username');
});

it('deletes a user', function () {
    $user = User::factory()->operari()->create();

    $response = $this->deleteJson("/api/users/{$user->id}");

    $response->assertNoContent();
    expect(User::find($user->id))->toBeNull();
});

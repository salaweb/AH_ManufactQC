<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('logs an admin in with email and password', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($admin->id);
});

it('rejects an admin login with the wrong password', function () {
    User::factory()->admin()->create([
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => 'admin@test.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    expect(Auth::check())->toBeFalse();
});

it('rejects login through the admin form with operari-only credentials', function () {
    User::factory()->operari()->create([
        'username' => 'operari_test',
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => 'operari_test@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    expect(Auth::check())->toBeFalse();
});

it('logs an operari in with username and password', function () {
    $operari = User::factory()->operari()->create([
        'username' => 'operari_test',
        'password' => 'password',
    ]);

    $response = $this->post('/operari/login', [
        'username' => 'operari_test',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('operari.home'));
    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($operari->id);
});

it('rejects an operari login with the wrong password', function () {
    User::factory()->operari()->create([
        'username' => 'operari_test',
        'password' => 'password',
    ]);

    $response = $this->post('/operari/login', [
        'username' => 'operari_test',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('username');
    expect(Auth::check())->toBeFalse();
});

it('logs the user out and destroys the session', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/logout');

    expect(Auth::check())->toBeFalse();
});

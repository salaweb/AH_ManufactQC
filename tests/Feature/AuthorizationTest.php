<?php

use App\Models\User;

it('blocks an operari from the admin dashboard', function () {
    $operari = User::factory()->operari()->create();

    $response = $this->actingAs($operari)->get('/admin/dashboard');

    $response->assertForbidden();
});

it('lets an admin access the operari (Qualitat) area directly, without a separate login', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/operari');

    $response->assertOk();
});

it('lets a QC user access the operari (Qualitat) area directly, without a separate login', function () {
    $qc = User::factory()->qc()->create();

    $response = $this->actingAs($qc)->get('/operari');

    $response->assertOk();
});

it('lets a QC user see the admin dashboard', function () {
    $qc = User::factory()->qc()->create();

    $response = $this->actingAs($qc)->get('/admin/dashboard');

    $response->assertOk();
});

it('lets an operari see their own area', function () {
    $operari = User::factory()->operari()->create();

    $response = $this->actingAs($operari)->get('/operari');

    $response->assertOk();
});

it('redirects a guest to the matching login form for each protected area', function () {
    $this->get('/admin/dashboard')->assertRedirect(route('login'));
    $this->get('/operari')->assertRedirect(route('operari.login'));
});

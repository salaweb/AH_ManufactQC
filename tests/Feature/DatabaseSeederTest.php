<?php

use App\Enums\UserRole;
use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Project;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Schema;

it('creates all expected tables via migrations', function () {
    $tables = [
        'users', 'projects', 'order_fabrications', 'sections',
        'questions', 'equipment', 'answers', 'defects', 'photos',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Expected table [{$table}] to exist");
    }
});

it('seeds the three test users with the correct roles', function () {
    $this->seed(UserSeeder::class);

    $admin = User::where('email', 'admin@test.com')->first();
    $qc = User::where('email', 'qc@test.com')->first();
    $operari = User::where('username', 'operari_test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($qc)->not->toBeNull()
        ->and($qc->role)->toBe(UserRole::Qc)
        ->and($operari)->not->toBeNull()
        ->and($operari->role)->toBe(UserRole::Operari)
        ->and($operari->email)->toBeNull();
});

it('seeds projects with order fabrications and equipment', function () {
    $this->seed(ProjectSeeder::class);

    expect(Project::count())->toBe(3)
        ->and(OrderFabrication::count())->toBe(6)
        ->and(Equipment::count())->toBe(18);

    $project = Project::first();
    expect($project->orderFabrications)->toHaveCount(2);
});

it('seeds the QUALITAT section with its questions', function () {
    $this->seed(SectionSeeder::class);

    $section = Section::where('name', 'QUALITAT')->first();

    expect($section)->not->toBeNull()
        ->and(Question::where('section_id', $section->id)->count())->toBe(4);
});

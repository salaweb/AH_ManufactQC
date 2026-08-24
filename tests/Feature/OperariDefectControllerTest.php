<?php

use App\Models\Answer;
use App\Models\Defect;
use App\Models\Equipment;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->operari()->create());
});

it('creates a defect tied to an equipment', function () {
    $equipment = Equipment::factory()->create();

    $response = $this->postJson('/operari/api/defects', [
        'equipment_id' => $equipment->id,
        'tipo' => 'visual',
        'observation' => 'Ratllada a la carcassa',
        'responsibility' => 'producció',
        'actions' => 'Substituir peça',
    ]);

    $response->assertCreated();
    expect(Defect::where('equipment_id', $equipment->id)->exists())->toBeTrue();
});

it('allows multiple defects on the same equipment', function () {
    $equipment = Equipment::factory()->create();

    $this->postJson('/operari/api/defects', ['equipment_id' => $equipment->id, 'tipo' => 'visual'])->assertCreated();
    $this->postJson('/operari/api/defects', ['equipment_id' => $equipment->id, 'tipo' => 'dimensional'])->assertCreated();

    expect(Defect::where('equipment_id', $equipment->id)->count())->toBe(2);
});

it('links a defect to the answer that triggered it', function () {
    $equipment = Equipment::factory()->create();
    $answer = Answer::factory()->create(['equipment_id' => $equipment->id]);

    $response = $this->postJson('/operari/api/defects', [
        'equipment_id' => $equipment->id,
        'answer_id' => $answer->id,
        'tipo' => 'funcional',
    ]);

    $response->assertCreated();
    expect(Defect::first()->answer_id)->toBe($answer->id);
});

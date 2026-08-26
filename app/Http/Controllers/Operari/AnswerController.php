<?php

namespace App\Http\Controllers\Operari;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnswerRequest;
use App\Models\Answer;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AnswerController extends Controller
{
    public function store(StoreAnswerRequest $request): JsonResponse
    {
        $data = $request->validated();

        $answer = Answer::updateOrCreate(
            ['equipment_id' => $data['equipment_id'], 'question_id' => $data['question_id']],
            ['response' => $data['response'], 'language_chosen' => $data['language_chosen'] ?? null],
        );

        Equipment::find($data['equipment_id'])->refreshStatus();

        return response()->json($answer, 201);
    }

    public function destroy(Answer $answer): Response
    {
        $equipment = $answer->equipment;

        $answer->delete();
        $equipment->refresh()->refreshStatus();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Operari;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnswerRequest;
use App\Models\Answer;
use Illuminate\Http\JsonResponse;

class AnswerController extends Controller
{
    public function store(StoreAnswerRequest $request): JsonResponse
    {
        $data = $request->validated();

        $answer = Answer::updateOrCreate(
            ['equipment_id' => $data['equipment_id'], 'question_id' => $data['question_id']],
            ['response' => $data['response'], 'language_chosen' => $data['language_chosen'] ?? null],
        );

        return response()->json($answer, 201);
    }
}

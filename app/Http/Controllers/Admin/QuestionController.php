<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QuestionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $questions = Question::query()
            ->when($request->filled('section_id'), fn ($query) => $query->where('section_id', $request->integer('section_id')))
            ->orderBy('order')
            ->get();

        return response()->json($questions);
    }

    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $question = Question::create($request->validated());

        return response()->json($question, 201);
    }

    public function show(Question $question): JsonResponse
    {
        return response()->json($question);
    }

    public function update(UpdateQuestionRequest $request, Question $question): JsonResponse
    {
        $question->update($request->validated());

        return response()->json($question);
    }

    public function destroy(Question $question): Response
    {
        $question->delete();

        return response()->noContent();
    }
}

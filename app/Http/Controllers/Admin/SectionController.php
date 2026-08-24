<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SectionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Section::orderBy('order')->get());
    }

    public function store(StoreSectionRequest $request): JsonResponse
    {
        $section = Section::create($request->validated());

        return response()->json($section, 201);
    }

    public function show(Section $section): JsonResponse
    {
        return response()->json($section);
    }

    public function update(UpdateSectionRequest $request, Section $section): JsonResponse
    {
        $section->update($request->validated());

        return response()->json($section);
    }

    public function destroy(Section $section): Response
    {
        $section->delete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Project::with('sections')->orderBy('number')->get());
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $sectionIds = $data['section_ids'] ?? [];
        unset($data['section_ids']);

        $project = Project::create($data);
        $project->sections()->sync($sectionIds);

        return response()->json($project->load('sections'), 201);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json($project->load('sections'));
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $data = $request->validated();
        $sectionIds = $data['section_ids'] ?? [];
        unset($data['section_ids']);

        $project->update($data);
        $project->sections()->sync($sectionIds);

        return response()->json($project->load('sections'));
    }

    public function destroy(Project $project): Response
    {
        $project->delete();

        return response()->noContent();
    }
}

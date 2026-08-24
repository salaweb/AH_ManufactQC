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
        return response()->json(Project::orderBy('number')->get());
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = Project::create($request->validated());

        return response()->json($project, 201);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json($project);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project->update($request->validated());

        return response()->json($project);
    }

    public function destroy(Project $project): Response
    {
        $project->delete();

        return response()->noContent();
    }
}

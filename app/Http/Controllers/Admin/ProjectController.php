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
    private const WITH = ['sections', 'family'];

    public function index(): JsonResponse
    {
        return response()->json(Project::with(self::WITH)->orderBy('number')->get());
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $sectionIds = $data['section_ids'] ?? [];
        unset($data['section_ids']);

        $project = Project::create($data);
        $this->syncSections($project, $sectionIds);

        return response()->json($project->load(self::WITH), 201);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json($project->load(self::WITH));
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $data = $request->validated();
        $sectionIds = $data['section_ids'] ?? [];
        unset($data['section_ids']);

        $project->update($data);
        $this->syncSections($project, $sectionIds);

        return response()->json($project->load(self::WITH));
    }

    public function destroy(Project $project): Response
    {
        $project->delete();

        return response()->noContent();
    }

    /**
     * Sync sections preserving the given array order in the pivot's `order` column —
     * this order also drives the project's combined description text.
     *
     * @param  array<int>  $sectionIds
     */
    private function syncSections(Project $project, array $sectionIds): void
    {
        $pivotData = collect($sectionIds)
            ->values()
            ->mapWithKeys(fn ($id, $index) => [$id => ['order' => $index]])
            ->all();

        $project->sections()->sync($pivotData);
    }
}

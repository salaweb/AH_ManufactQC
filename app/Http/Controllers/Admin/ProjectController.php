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
    private const WITH = ['sections', 'family', 'descriptionTags'];

    public function index(): JsonResponse
    {
        return response()->json(Project::with(self::WITH)->orderBy('number')->get());
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $sectionIds = $data['section_ids'] ?? [];
        $descriptionTagIds = $data['description_tag_ids'] ?? [];
        unset($data['section_ids'], $data['description_tag_ids']);

        $project = Project::create($data);
        $project->sections()->sync($sectionIds);
        $this->syncDescriptionTags($project, $descriptionTagIds);

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
        $descriptionTagIds = $data['description_tag_ids'] ?? [];
        unset($data['section_ids'], $data['description_tag_ids']);

        $project->update($data);
        $project->sections()->sync($sectionIds);
        $this->syncDescriptionTags($project, $descriptionTagIds);

        return response()->json($project->load(self::WITH));
    }

    public function destroy(Project $project): Response
    {
        $project->delete();

        return response()->noContent();
    }

    /**
     * Sync description tags preserving the given array order in the pivot's `order` column.
     *
     * @param  array<int>  $descriptionTagIds
     */
    private function syncDescriptionTags(Project $project, array $descriptionTagIds): void
    {
        $pivotData = collect($descriptionTagIds)
            ->values()
            ->mapWithKeys(fn ($id, $index) => [$id => ['order' => $index]])
            ->all();

        $project->descriptionTags()->sync($pivotData);
    }
}

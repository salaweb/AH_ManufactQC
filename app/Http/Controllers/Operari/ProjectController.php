<?php

namespace App\Http\Controllers\Operari;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = Project::query()
            ->when($request->filled('q'), fn ($query) => $query->where('number', 'like', '%'.$request->string('q').'%'))
            ->orderBy('number')
            ->limit(10)
            ->get();

        return response()->json($projects);
    }

    public function orderFabrications(Project $project): JsonResponse
    {
        return response()->json($project->orderFabrications()->orderBy('number')->get());
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnswerResponse;
use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Defect;
use App\Models\Equipment;
use App\Models\Photo;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $checkedIds = $this->checkedEquipmentQuery($request)->pluck('id');

        return response()->json([
            'stats' => $this->getStats($request, $checkedIds),
            'defects_by_type' => $this->getDefects($checkedIds),
            'responsibilities' => $this->getResponsibilities($checkedIds),
            'trends' => $this->getTrends($checkedIds),
            'recent_photos' => $this->getRecentPhotos($checkedIds),
        ]);
    }

    private function projectScopedEquipmentQuery(Request $request): Builder
    {
        return Equipment::query()
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')));
    }

    private function checkedEquipmentQuery(Request $request): Builder
    {
        return $this->projectScopedEquipmentQuery($request)
            ->whereNotNull('checked_at')
            ->when($request->filled('from'), fn ($query) => $query->whereDate('checked_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('checked_at', '<=', $request->date('to')));
    }

    private function getStats(Request $request, Collection $checkedIds): array
    {
        $total = $this->projectScopedEquipmentQuery($request)->count();
        $checked = $checkedIds->count();

        return [
            'total_equipment' => $total,
            'checked_equipment' => $checked,
            'completion_percentage' => $total > 0 ? (int) round($checked / $total * 100) : 0,
            'total_defects' => Defect::whereIn('equipment_id', $checkedIds)->count(),
        ];
    }

    private function getDefects(Collection $checkedIds): Collection
    {
        return Defect::whereIn('equipment_id', $checkedIds)
            ->selectRaw('tipo, count(*) as count')
            ->groupBy('tipo')
            ->orderByDesc('count')
            ->get();
    }

    private function getResponsibilities(Collection $checkedIds): Collection
    {
        return Defect::whereIn('equipment_id', $checkedIds)
            ->whereNotNull('responsibility')
            ->selectRaw('responsibility, count(*) as count')
            ->groupBy('responsibility')
            ->orderByDesc('count')
            ->get();
    }

    private function getTrends(Collection $checkedIds): Collection
    {
        return Section::query()
            ->orderBy('order')
            ->get()
            ->map(function (Section $section) use ($checkedIds) {
                $questionIds = $section->questions()->pluck('id');

                $totalAnswers = Answer::whereIn('equipment_id', $checkedIds)->whereIn('question_id', $questionIds)->count();
                $defectAnswers = Answer::whereIn('equipment_id', $checkedIds)
                    ->whereIn('question_id', $questionIds)
                    ->where('response', AnswerResponse::Defect)
                    ->count();

                return [
                    'section' => $section->name,
                    'total_answers' => $totalAnswers,
                    'defect_answers' => $defectAnswers,
                    'defect_rate' => $totalAnswers > 0 ? (int) round($defectAnswers / $totalAnswers * 100) : 0,
                ];
            });
    }

    private function getRecentPhotos(Collection $checkedIds): Collection
    {
        return Photo::whereIn('equipment_id', $checkedIds)
            ->with('equipment:id,serie_number')
            ->latest('uploaded_at')
            ->limit(12)
            ->get()
            ->map(fn (Photo $photo) => [
                'id' => $photo->id,
                'serie_number' => $photo->equipment->serie_number,
                'uploaded_at' => $photo->uploaded_at,
            ]);
    }
}

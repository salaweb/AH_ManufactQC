<?php

namespace App\Http\Controllers\Operari;

use App\Enums\EquipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePhotosRequest;
use App\Http\Requests\UpdateEquipmentObservationsRequest;
use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Photo;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Http\JsonResponse;

class EquipmentController extends Controller
{
    public function index(OrderFabrication $orderFabrication): JsonResponse
    {
        return response()->json([
            'order_fabrication' => $orderFabrication->load('project'),
            'equipment' => $orderFabrication->equipment()->withCount('defects')->orderBy('serie_number')->get(),
        ]);
    }

    public function show(Equipment $equipment): JsonResponse
    {
        $equipment->load(['project', 'orderFabrication', 'defects', 'photos']);

        $answers = $equipment->answers()->get()->keyBy('question_id');

        $sections = $equipment->project->sections()
            ->with(['questions' => fn ($query) => $query->orderBy('order')])
            ->orderBy('order')
            ->get()
            ->map(fn (Section $section) => [
                'id' => $section->id,
                'name' => $section->name,
                'description' => $section->description,
                'order' => $section->order,
                'questions' => $section->questions->map(fn (Question $question) => [
                    'id' => $question->id,
                    'text' => $question->text,
                    'order' => $question->order,
                    'is_required' => $question->is_required,
                    'answer' => optional($answers->get($question->id))->only(['response', 'language_chosen']),
                ]),
            ]);

        return response()->json([
            'equipment' => $equipment,
            'sections' => $sections,
        ]);
    }

    public function update(UpdateEquipmentObservationsRequest $request, Equipment $equipment): JsonResponse
    {
        $equipment->update($request->validated());

        return response()->json($equipment);
    }

    public function storePhotos(StorePhotosRequest $request, Equipment $equipment): JsonResponse
    {
        foreach ($request->file('photos', []) as $file) {
            $path = $file->store('', 'photos');

            Photo::create([
                'equipment_id' => $equipment->id,
                'path' => $path,
                'uploaded_at' => now(),
            ]);
        }

        $status = match (true) {
            $equipment->defects()->exists() => EquipmentStatus::Defect,
            filled($equipment->observations) => EquipmentStatus::Observation,
            default => EquipmentStatus::Ok,
        };

        $equipment->update([
            'status' => $status,
            'checked_at' => now(),
        ]);

        return response()->json($equipment->fresh(['photos']));
    }
}

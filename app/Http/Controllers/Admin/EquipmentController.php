<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EquipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $equipment = Equipment::query()
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('order_fabrication_id'), fn ($query) => $query->where('order_fabrication_id', $request->integer('order_fabrication_id')))
            ->orderBy('serie_number')
            ->get();

        return response()->json($equipment);
    }

    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        $equipment = Equipment::create($request->validated());

        return response()->json($equipment, 201);
    }

    public function show(Equipment $equipment): JsonResponse
    {
        return response()->json($equipment);
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): JsonResponse
    {
        $equipment->update($request->validated());

        return response()->json($equipment);
    }

    public function destroy(Equipment $equipment): Response
    {
        $equipment->delete();

        return response()->noContent();
    }
}

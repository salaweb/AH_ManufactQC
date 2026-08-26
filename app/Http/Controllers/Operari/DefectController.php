<?php

namespace App\Http\Controllers\Operari;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDefectRequest;
use App\Http\Requests\UpdateDefectRequest;
use App\Models\Defect;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DefectController extends Controller
{
    public function store(StoreDefectRequest $request): JsonResponse
    {
        $defect = Defect::create($request->validated());

        Equipment::find($defect->equipment_id)->refreshStatus();

        return response()->json($defect, 201);
    }

    public function update(UpdateDefectRequest $request, Defect $defect): JsonResponse
    {
        $defect->update($request->validated());

        return response()->json($defect);
    }

    public function destroy(Defect $defect): Response
    {
        $equipmentId = $defect->equipment_id;

        $defect->delete();

        Equipment::find($equipmentId)->refreshStatus();

        return response()->noContent();
    }
}

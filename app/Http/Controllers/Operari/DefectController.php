<?php

namespace App\Http\Controllers\Operari;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDefectRequest;
use App\Http\Requests\UpdateDefectRequest;
use App\Models\Defect;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;

class DefectController extends Controller
{
    public function store(StoreDefectRequest $request): JsonResponse
    {
        $defect = Defect::create($request->validated());

        $equipment = Equipment::find($defect->equipment_id);
        $equipment->update(['status' => $equipment->statusFor($equipment->checked_at !== null)]);

        return response()->json($defect, 201);
    }

    public function update(UpdateDefectRequest $request, Defect $defect): JsonResponse
    {
        $defect->update($request->validated());

        return response()->json($defect);
    }
}

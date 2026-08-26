<?php

namespace App\Http\Controllers\Operari;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDefectRequest;
use App\Http\Requests\UpdateDefectRequest;
use App\Models\Defect;
use Illuminate\Http\JsonResponse;

class DefectController extends Controller
{
    public function store(StoreDefectRequest $request): JsonResponse
    {
        $defect = Defect::create($request->validated());

        return response()->json($defect, 201);
    }

    public function update(UpdateDefectRequest $request, Defect $defect): JsonResponse
    {
        $defect->update($request->validated());

        return response()->json($defect);
    }
}

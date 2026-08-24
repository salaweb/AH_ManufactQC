<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFamilyRequest;
use App\Models\Family;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class FamilyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Family::orderBy('name')->get());
    }

    public function store(StoreFamilyRequest $request): JsonResponse
    {
        $family = Family::create($request->validated());

        return response()->json($family, 201);
    }

    public function destroy(Family $family): Response
    {
        if ($family->projects()->exists()) {
            throw ValidationException::withMessages([
                'family' => 'Aquesta família està en ús per algun projecte i no es pot eliminar.',
            ]);
        }

        $family->delete();

        return response()->noContent();
    }
}

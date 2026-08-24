<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDescriptionTagRequest;
use App\Models\DescriptionTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DescriptionTagController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(DescriptionTag::orderBy('name')->get());
    }

    public function store(StoreDescriptionTagRequest $request): JsonResponse
    {
        $tag = DescriptionTag::create($request->validated());

        return response()->json($tag, 201);
    }

    public function destroy(DescriptionTag $descriptionTag): Response
    {
        $descriptionTag->delete();

        return response()->noContent();
    }
}

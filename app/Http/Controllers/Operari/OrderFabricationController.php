<?php

namespace App\Http\Controllers\Operari;

use App\Http\Controllers\Controller;
use App\Models\OrderFabrication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderFabricationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orderFabrications = OrderFabrication::query()
            ->with(['project.family', 'project.sections'])
            ->withCount('equipment')
            ->when($request->filled('q'), fn ($query) => $query->where('number', 'like', '%'.$request->string('q').'%'))
            ->orderBy('number')
            ->limit(10)
            ->get();

        return response()->json($orderFabrications);
    }
}

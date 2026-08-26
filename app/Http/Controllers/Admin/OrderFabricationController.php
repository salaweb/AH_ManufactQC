<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderFabricationRequest;
use App\Http\Requests\UpdateOrderFabricationRequest;
use App\Models\OrderFabrication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderFabricationController extends Controller
{
    private const WITH = ['project.family', 'project.sections'];

    public function index(Request $request): JsonResponse
    {
        $orderFabrications = OrderFabrication::query()
            ->with(self::WITH)
            ->withCount('equipment')
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->orderBy('number')
            ->get();

        return response()->json($orderFabrications);
    }

    public function store(StoreOrderFabricationRequest $request): JsonResponse
    {
        $orderFabrication = OrderFabrication::create($request->validated());

        return response()->json($orderFabrication->load(self::WITH), 201);
    }

    public function show(OrderFabrication $orderFabrication): JsonResponse
    {
        return response()->json($orderFabrication->load(self::WITH));
    }

    public function update(UpdateOrderFabricationRequest $request, OrderFabrication $orderFabrication): JsonResponse
    {
        $orderFabrication->update($request->validated());

        return response()->json($orderFabrication->load(self::WITH));
    }

    public function destroy(OrderFabrication $orderFabrication): Response
    {
        $orderFabrication->delete();

        return response()->noContent();
    }
}

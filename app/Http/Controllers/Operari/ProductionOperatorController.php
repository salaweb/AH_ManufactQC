<?php

namespace App\Http\Controllers\Operari;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ProductionOperatorController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            User::where('role', UserRole::OperariProduccio)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }
}

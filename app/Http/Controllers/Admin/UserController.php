<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::orderBy('name')->get());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($data['role'] === UserRole::Operari->value) {
            $data['email'] = null;
        } else {
            $data['username'] = null;
        }

        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($data['role'] === UserRole::Operari->value) {
            $data['email'] = null;
        } else {
            $data['username'] = null;
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }
}

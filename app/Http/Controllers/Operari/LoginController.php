<?php

namespace App\Http\Controllers\Operari;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\OperariLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Operari/Login');
    }

    public function login(OperariLoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('username', 'password'))) {
            throw ValidationException::withMessages([
                'username' => 'Aquestes credencials no coincideixen amb els nostres registres.',
            ]);
        }

        if (Auth::user()->role !== UserRole::Operari) {
            Auth::logout();

            throw ValidationException::withMessages([
                'username' => 'Aquestes credencials no coincideixen amb els nostres registres.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('operari.home');
    }
}

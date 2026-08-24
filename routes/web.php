<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Operari\LoginController as OperariLoginController;
use App\Http\Middleware\Admin\EnsureAdminOrQc;
use App\Http\Middleware\Web\EnsureOperari;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

Route::get('/operari/login', [OperariLoginController::class, 'create'])->middleware('guest')->name('operari.login');
Route::post('/operari/login', [OperariLoginController::class, 'login'])->middleware('guest');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/admin/dashboard', function () {
    return Inertia::render('Admin/Dashboard');
})->middleware(['auth', EnsureAdminOrQc::class])->name('admin.dashboard');

Route::get('/operari', function () {
    return Inertia::render('Operari/ProjectSelector');
})->middleware(['auth', EnsureOperari::class])->name('operari.home');

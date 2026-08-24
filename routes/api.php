<?php

use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('projects', ProjectController::class);
Route::apiResource('sections', SectionController::class);
Route::apiResource('questions', QuestionController::class);
Route::apiResource('users', UserController::class);

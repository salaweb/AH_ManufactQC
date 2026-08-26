<?php

use App\Http\Controllers\Operari\AnswerController;
use App\Http\Controllers\Operari\DefectController;
use App\Http\Controllers\Operari\EquipmentController;
use App\Http\Controllers\Operari\OrderFabricationController;
use Illuminate\Support\Facades\Route;

Route::get('/order-fabrications', [OrderFabricationController::class, 'index']);
Route::get('/order-fabrications/{orderFabrication}/equipment', [EquipmentController::class, 'index']);
Route::get('/equipment/{equipment}', [EquipmentController::class, 'show']);
Route::patch('/equipment/{equipment}', [EquipmentController::class, 'update']);
Route::post('/equipment/{equipment}/photos', [EquipmentController::class, 'storePhotos']);

Route::post('/answers', [AnswerController::class, 'store']);
Route::post('/defects', [DefectController::class, 'store']);
Route::put('/defects/{defect}', [DefectController::class, 'update']);

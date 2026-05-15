<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Pastikan mengimpor ApiController
use App\Http\Controllers\ApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rute API kita
Route::post('/sensor/data', [ApiController::class, 'receiveData']);
Route::get('/sensor/check/{device_id}', [ApiController::class, 'checkNewData']);
Route::post('/sensor/status', [ApiController::class, 'updateStatus']);
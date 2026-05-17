<?php

use App\Http\Controllers\Api\IotController;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Legacy endpoints (device_token di body)
Route::post('/sensor/data', [ApiController::class, 'receiveData']);
Route::get('/sensor/check/{device_id}', [ApiController::class, 'checkNewData']);
Route::post('/sensor/status', [ApiController::class, 'updateStatus']);

// IoT endpoints (ESP32) — header X-Device-Token atau device_token di body
Route::middleware('device.auth')->group(function () {
    Route::post('/sensor-data', [IotController::class, 'sensorData']);
    Route::post('/fall-detected', [IotController::class, 'fallDetected']);
    Route::post('/sos', [IotController::class, 'sos']);
});

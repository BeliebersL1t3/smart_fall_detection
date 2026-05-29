<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IotController;
use App\Http\Controllers\Api\MobileDeviceController;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

// Flutter / mobile caregiver API (Bearer token via Sanctum)
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/device/status', [MobileDeviceController::class, 'status']);
    Route::get('/events/history', [MobileDeviceController::class, 'history']);
    Route::post('/events/{event}/resolve', [MobileDeviceController::class, 'resolveEvent'])
        ->whereNumber('event');
});

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

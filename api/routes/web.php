<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\IotConnectionController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/export-pdf', [DashboardController::class, 'exportPdf'])->name('export.pdf');
    Route::post('/event/{id}/resolve', [DashboardController::class, 'resolveEvent'])->name('event.resolve');
    Route::post('/event/{id}/false-alarm', [DashboardController::class, 'markFalseAlarm'])->name('event.false_alarm');
    Route::post('/event/{id}/auto-confirm', [DashboardController::class, 'autoConfirm'])->name('event.auto_confirm');
    Route::post('/event/{id}/dismiss', [DashboardController::class, 'dismissAlert'])->name('event.dismiss');
    Route::post('/simulate/{type}', [DashboardController::class, 'simulateEvent'])->name('simulate');

    Route::get('/export/excel', [App\Http\Controllers\DashboardController::class, 'exportExcel'])->name('export.excel');

    Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/profile', [App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/device', [App\Http\Controllers\SettingsController::class, 'updateDevice'])->name('settings.device');
    Route::post('/settings/api-base', [App\Http\Controllers\SettingsController::class, 'updateApiBase'])->name('settings.api_base');
    Route::post('/settings/password', [App\Http\Controllers\SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/telegram', [App\Http\Controllers\SettingsController::class, 'updateTelegram'])->name('settings.telegram');

    Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics');

    Route::get('/dashboard/iot/status', [IotConnectionController::class, 'status'])->name('iot.status');
    Route::get('/dashboard/iot/live', [IotConnectionController::class, 'liveStatus'])->name('iot.live');
    Route::post('/dashboard/iot/test', [IotConnectionController::class, 'testConnection'])->name('iot.test');
    Route::post('/dashboard/iot/api-base', [IotConnectionController::class, 'updateApiBase'])->name('iot.api_base');
    Route::post('/sensor/data', [ApiController::class, 'receiveData']);
    Route::get('/sensor/check/{device_id}', [ApiController::class, 'checkNewData']);
});
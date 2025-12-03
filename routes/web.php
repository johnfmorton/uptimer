<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueueDiagnosticsController;
use App\Http\Controllers\QueueTestController;
use Illuminate\Support\Facades\Route;

// Public welcome page
Route::get('/', function () {
    return view('welcome');
});

// Protected dashboard route - requires authentication
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// Protected profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Queue test routes for verifying queue functionality
    Route::post('/queue-test', [QueueTestController::class, 'dispatch'])->name('queue.test');
    Route::get('/queue-status', [QueueTestController::class, 'status'])->name('queue.status');

    // Queue diagnostics routes
    Route::post('/queue/test', [QueueDiagnosticsController::class, 'testQueue'])->name('queue.diagnostics.test');
    Route::get('/api/queue/status', [QueueDiagnosticsController::class, 'status'])->name('queue.diagnostics.status');
});

// Protected notification settings routes
Route::middleware('auth')->group(function () {
    Route::get('/notification-settings', [\App\Http\Controllers\NotificationSettingsController::class, 'edit'])->name('notification-settings.edit');
    Route::patch('/notification-settings', [\App\Http\Controllers\NotificationSettingsController::class, 'update'])->name('notification-settings.update');
    Route::post('/notification-settings/test-email', [\App\Http\Controllers\NotificationSettingsController::class, 'testEmail'])->name('notification-settings.test-email');
    Route::post('/notification-settings/test-pushover', [\App\Http\Controllers\NotificationSettingsController::class, 'testPushover'])->name('notification-settings.test-pushover');
});

// Protected monitor resource routes
Route::middleware('auth')->group(function () {
    Route::resource('monitors', \App\Http\Controllers\MonitorController::class);
    Route::post('/monitors/{monitor}/check', [\App\Http\Controllers\MonitorController::class, 'triggerCheck'])->name('monitors.check');
    Route::get('/api/monitors', [\App\Http\Controllers\MonitorController::class, 'api'])->name('monitors.api');
});

// Authentication routes (login, register, logout, etc.)
require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueueTestController;
use Illuminate\Support\Facades\Route;

// Public welcome page
Route::get('/', function () {
    return view('welcome');
});

// Protected dashboard route - requires authentication
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

// Protected profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Queue test route for verifying queue functionality
    Route::post('/queue-test', [QueueTestController::class, 'dispatch'])->name('queue.test');
});

// Protected notification settings routes
Route::middleware('auth')->group(function () {
    Route::get('/notification-settings', [\App\Http\Controllers\NotificationSettingsController::class, 'edit'])->name('notification-settings.edit');
    Route::patch('/notification-settings', [\App\Http\Controllers\NotificationSettingsController::class, 'update'])->name('notification-settings.update');
});

// Authentication routes (login, register, logout, etc.)
require __DIR__.'/auth.php';

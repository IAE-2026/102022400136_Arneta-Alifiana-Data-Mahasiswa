<?php

use App\Http\Controllers\Api\MahasiswaController;
use App\Http\Controllers\Api\SsoController;
use Illuminate\Support\Facades\Route;

// SSO Routes - tidak perlu API Key
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [SsoController::class, 'login']);
    Route::get('/auth/profile', [SsoController::class, 'profile']);
});

// Mahasiswa Routes - perlu API Key
Route::middleware('api.key')->prefix('v1')->group(function () {
    Route::get('/mahasiswa', [MahasiswaController::class, 'index']);
    Route::get('/mahasiswa/{nim}', [MahasiswaController::class, 'show']);
    Route::post('/mahasiswa', [MahasiswaController::class, 'store']);
});
<?php

use App\Http\Controllers\Api\MahasiswaController;
use App\Http\Controllers\Api\SsoController;
use Illuminate\Support\Facades\Route;

// Mahasiswa Routes - perlu API Key
Route::middleware('api.key')->group(function () {
    Route::get('v1/mahasiswa', [MahasiswaController::class, 'index']);
    Route::get('v1/mahasiswa/{nim}', [MahasiswaController::class, 'show']);
    Route::post('v1/mahasiswa', [MahasiswaController::class, 'store']);
});

// SSO Routes - tidak perlu API Key
Route::post('auth/login', [SsoController::class, 'login']);
Route::get('auth/profile', [SsoController::class, 'profile']);
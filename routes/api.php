<?php

use App\Http\Controllers\Api\MahasiswaController;
use Illuminate\Support\Facades\Route;

// Mahasiswa Routes - perlu API Key
Route::middleware('api.key')->prefix('v1')->group(function () {
    Route::get('mahasiswa', [MahasiswaController::class, 'index']);
    Route::get('mahasiswa/{nim}', [MahasiswaController::class, 'show']);
    Route::post('mahasiswa', [MahasiswaController::class, 'store']);
});
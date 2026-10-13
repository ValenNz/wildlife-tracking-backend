<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AnimalController;
use App\Http\Controllers\Api\GpsReadingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\GeozonesController;
use App\Http\Controllers\Api\ExportController;

// ==================== PUBLIC ROUTES ====================
Route::post('/login', [AuthController::class, 'login']);

// ==================== PROTECTED ROUTES ====================
Route::middleware('auth:sanctum')->group(function () {
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Dashboard (UC#12 - FR-12)
    Route::get('/dashboard', [DashboardController::class, 'summary']);
    
    // Animals (UC#9 - FR-09)
    Route::get('/animals', [AnimalController::class, 'index']);
    Route::get('/animals/{id}', [AnimalController::class, 'show']);
    
    // GPS Readings (UC#1, UC#4, UC#6 - FR-01, FR-02, FR-04)
    Route::get('/animals/{id}/live-position', [GpsReadingController::class, 'livePosition']);
    Route::get('/animals/{id}/history', [GpsReadingController::class, 'history']);
    
    // Devices (UC#2, UC#8 - FR-08, FR-17)
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::get('/devices/alerts', [DeviceController::class, 'alerts']);
    
    // Geozones (UC#10 - FR-10)
    Route::get('/geozones', [GeozonesController::class, 'index']);
    
    // Export (UC#7 - FR-07)
    Route::get('/export/csv', [ExportController::class, 'csv']);
    Route::get('/export/geojson', [ExportController::class, 'geojson']);
});
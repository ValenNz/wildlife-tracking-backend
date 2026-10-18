<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\GeoZonesController;
use App\Http\Controllers\Api\GpsReadingController;
use App\Models\Animal;
use App\Models\IncidentReport;
use App\Models\Notification;
use App\Models\Species;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'summary']);
    Route::get('/export/gps/csv', [ExportController::class, 'csv']);
    Route::get('/export/gps/geojson', [ExportController::class, 'geojson']);

    Route::get('/animals', function () {
        return response()->json(['data' => Animal::with('species')->get()]);
    });

    Route::get('/species', function () {
        return response()->json(['data' => Species::all()]);
    });

    Route::get('/gps-readings', [GpsReadingController::class, 'index']);
    Route::get('/gps-readings/{animal_id}', [GpsReadingController::class, 'byAnimal']);

    Route::get('/devices', [DeviceController::class, 'index']);
    Route::get('/devices/{id}', [DeviceController::class, 'show']);

    Route::get('/geo-zones', [GeoZonesController::class, 'index']);

    Route::get('/incidents', function () {
        return response()->json(['data' => IncidentReport::all()]);
    });

    Route::get('/notifications', function () {
        return response()->json(['data' => Notification::all()]);
    });
});

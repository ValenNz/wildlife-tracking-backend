<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AggregateReportController;
use App\Http\Controllers\Api\AiMediaUploadController;
use App\Http\Controllers\Api\AiPredictionController;
use App\Http\Controllers\Api\AnimalController;
use App\Http\Controllers\Api\AnimalGeoZoneController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GeoZoneController;
use App\Http\Controllers\Api\GpsReadingController;
use App\Http\Controllers\Api\IncidentReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\SpeciesController;
use App\Http\Controllers\Api\TrackingDeviceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WeatherDataController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    Route::post('role-permissions/sync', [RolePermissionController::class, 'sync']);
    Route::apiResource('species', SpeciesController::class);
    Route::apiResource('animals', AnimalController::class);
    Route::apiResource('tracking-devices', TrackingDeviceController::class);
    Route::apiResource('geo-zones', GeoZoneController::class);
    Route::apiResource('gps-readings', GpsReadingController::class);
    Route::apiResource('animal-geo-zones', AnimalGeoZoneController::class);
    Route::apiResource('incident-reports', IncidentReportController::class);
    Route::apiResource('ai-media-uploads', AiMediaUploadController::class);
    Route::apiResource('ai-predictions', AiPredictionController::class);
    Route::apiResource('weather-data', WeatherDataController::class);
    Route::apiResource('notifications', NotificationController::class);
    Route::apiResource('activity-log', ActivityLogController::class);
    Route::apiResource('aggregate-reports', AggregateReportController::class);
});

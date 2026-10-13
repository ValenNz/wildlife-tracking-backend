<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\GeoZonesController;
use App\Http\Controllers\Api\GpsReadingController;
use App\Http\Controllers\Api\ExportController;

// Dashboard - Ringkasan data
Route::get('/dashboard', [DashboardController::class, 'index']);

// Animals - Daftar hewan
Route::get('/animals', function() {
    return response()->json(\App\Models\Animal::with('species')->get());
});

// Species - Daftar spesies
Route::get('/species', function() {
    return response()->json(\App\Models\Species::all());
});

// GPS Readings - Data lokasi
Route::get('/gps-readings', [GpsReadingController::class, 'index']);
Route::get('/gps-readings/{animal_id}', [GpsReadingController::class, 'byAnimal']);

// Tracking Devices - Perangkat pelacak
Route::get('/devices', [DeviceController::class, 'index']);
Route::get('/devices/{id}', [DeviceController::class, 'show']);

// Geo Zones - Zona wilayah
Route::get('/geo-zones', [GeoZonesController::class, 'index']);

// Incident Reports - Laporan kejadian
Route::get('/incidents', function() {
    return response()->json(\App\Models\IncidentReport::all());
});

// Notifications - Notifikasi
Route::get('/notifications', function() {
    return response()->json(\App\Models\Notification::all());
});

// Export - Export data
Route::get('/export/gps', [ExportController::class, 'gps']);
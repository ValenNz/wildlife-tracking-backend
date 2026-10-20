<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Wildlife Tracking API',
        'status' => 'success',
        'version' => '1.0.0'
    ]);
});
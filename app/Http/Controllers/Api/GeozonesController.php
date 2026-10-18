<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeoZone;

class GeozonesController extends Controller
{
    public function index()
    {
        $zones = GeoZone::all();

        return response()->json(['data' => $zones]);
    }
}

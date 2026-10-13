<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Geozones;

class GeozonesController extends Controller
{
    public function index()
    {
        $zones = Geozones::all();
        return response()->json(['data' => $zones]);
    }
}
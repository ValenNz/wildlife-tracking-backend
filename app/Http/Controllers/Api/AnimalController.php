<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function index(Request $request)
    {
        $query = Animal::with('species');

        if ($request->species_id) {
            $query->where('species_id', $request->species_id);
        }
        if ($request->name) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        return response()->json(['data' => $query->get()]);
    }

    public function show($id)
    {
        $animal = Animal::with(['species', 'trackingDevice'])->findOrFail($id);

        return response()->json(['data' => $animal]);
    }
}

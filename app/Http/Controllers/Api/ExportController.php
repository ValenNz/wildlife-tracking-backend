<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GPSReadings;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function csv(Request $request)
    {
        $readings = GPSReadings::with('device.animal.species')->get();

        $csv = "timestamp,latitude,longitude,accuracy,species,animal_id\n";
        foreach ($readings as $r) {
            $csv .= "{$r->recorded_at},{$r->latitude},{$r->longitude},{$r->accuracy_meters},";
            $csv .= "{$r->device->animal->species->scientific_name},{$r->device->animal_id}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="tracking_data.csv"');
    }

    public function geojson(Request $request)
    {
        $readings = GPSReadings::with('device.animal.species')->get();

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $readings->map(fn ($r) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $r->longitude, (float) $r->latitude],
                ],
                'properties' => [
                    'animal_name' => $r->device->animal->name,
                    'species' => $r->device->animal->species->scientific_name,
                    'recorded_at' => $r->recorded_at,
                    'accuracy' => $r->accuracy_meters,
                ],
            ]),
        ];

        return response()->json($geojson)
            ->header('Content-Disposition', 'attachment; filename="tracking_data.geojson"');
    }
}

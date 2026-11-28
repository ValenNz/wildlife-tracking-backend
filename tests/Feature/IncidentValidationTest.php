<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\GeoZone;
use App\Models\IncidentReport;
use App\Models\Species;
use App\Services\GeoService;
use Tests\TestCase;

class IncidentValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'incident_type' => 'Jerat', 'description' => 'Ditemukan jerat kawat', 'timestamp' => now()->subHour()->toDateTimeString(),
            'latitude' => 3.6, 'longitude' => 97.9, 'status' => 'pending',
        ], $override);
    }

    public function test_status_must_be_one_of_the_standard_values(): void
    {
        $h = $this->loginAs('lapangan');
        $this->postJson('/api/incident-reports', $this->payload(['status' => 'beres deh']), $h)->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->postJson('/api/incident-reports', $this->payload(['status' => 'investigating']), $h)->assertCreated();
    }

    public function test_animal_and_species_must_match_and_species_is_filled_from_animal(): void
    {
        $h = $this->loginAs('lapangan');
        $animal = Animal::firstOrFail();
        $wrong = Species::where('species_id', '!=', $animal->species_id)->firstOrFail();

        $this->postJson('/api/incident-reports', $this->payload(['animal_id' => $animal->animal_id, 'species_id' => $wrong->species_id]), $h)
            ->assertUnprocessable()->assertJsonValidationErrors('species_id');
        $this->postJson('/api/incident-reports', $this->payload(['animal_id' => $animal->animal_id]), $h)
            ->assertCreated()->assertJsonPath('data.species_id', $animal->species_id);
    }

    public function test_deleted_zone_cannot_be_chosen_and_zone_is_recomputed_on_move(): void
    {
        $h = $this->loginAs('lapangan');
        $zone = GeoZone::firstOrFail();
        $zone->delete();
        $this->postJson('/api/incident-reports', $this->payload(['zone_id' => $zone->zone_id]), $h)->assertUnprocessable()->assertJsonValidationErrors('zone_id');

        $id = $this->postJson('/api/incident-reports', $this->payload(), $h)->assertCreated()->json('data.report_id');
        IncidentReport::whereKey($id)->update(['zone_id' => null]);
        $this->putJson("/api/incident-reports/{$id}", ['latitude' => 3.61, 'longitude' => 97.91], $h)->assertOk();
        $expected = app(GeoService::class)->resolveZone(3.61, 97.91, 0)?->zone_id;
        $this->assertSame($expected, IncidentReport::find($id)->zone_id);
    }
}

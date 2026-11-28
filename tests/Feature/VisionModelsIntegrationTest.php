<?php

namespace Tests\Feature;

use App\Models\AiPrediction;
use App\Models\Animal;
use App\Models\AnimalEmbedding;
use App\Models\IncidentReport;
use App\Models\TrackingDevice;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisionModelsIntegrationTest extends TestCase
{
    private const SERVICE = 'http://ai-service.test';

    private array $h;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
        Storage::fake('local');
        config([
            'services.groq.key' => '',
            'services.ai_vision.url' => self::SERVICE,
            'services.ai_vision.token' => 'rahasia-uji',
            'services.ai_vision.reid_threshold' => 0.6,
            'services.ai_audio.url' => null,
            'services.ai.queue' => false,
        ]);
        $this->h = $this->loginAs('lapangan');
    }

    private function vector(int $seed, float $noise = 0.0): array
    {
        mt_srand($seed);
        $v = [];
        for ($i = 0; $i < 768; $i++) {
            $v[] = mt_rand(-1000, 1000) / 1000;
        }
        if ($noise > 0) {
            mt_srand($seed + 9999);
            foreach ($v as $i => $x) {
                $v[$i] = $x + $noise * mt_rand(-1000, 1000) / 1000;
            }
        }
        $n = sqrt(array_sum(array_map(fn ($x) => $x * $x, $v)));

        return array_map(fn ($x) => round($x / $n, 6), $v);
    }

    private function taxon(string $common, string $scientific, string $rank, float $score): array
    {
        [$genus] = explode(' ', $scientific);

        return ['common_name' => $common, 'scientific_name' => $scientific, 'rank' => $rank, 'genus' => $genus, 'family' => null, 'score' => $score, 'source' => 'classifier'];
    }

    private function fakeAnalysis(array $detections, ?array $top, array $extra = []): void
    {
        $count = fn ($c) => count(array_filter($detections, fn ($d) => $d['category'] === $c));
        Http::fake([
            self::SERVICE . '/analyze-image' => Http::response([
                'detections' => $detections,
                'animal_count' => $count('animal'),
                'person_count' => $count('person'),
                'vehicle_count' => $count('vehicle'),
                'top_species' => $top,
                'model' => 'speciesnet-v4.0.3a',
                'embedding_model' => 'MegaDescriptor-T-224',
            ]),
            self::SERVICE . '/analyze-video' => Http::response([
                'detections' => $detections, 'animal_count' => $count('animal'), 'person_count' => $count('person'),
                'vehicle_count' => $count('vehicle'), 'top_species' => $top, 'embedding_model' => 'MegaDescriptor-T-224',
                'best_frame_seconds' => 4.0, 'frames' => [['time_seconds' => 0, 'animal_count' => 0], ['time_seconds' => 4.0, 'animal_count' => $count('animal')]],
            ]),
            ...$extra,
        ]);
    }

    private function camera(): TrackingDevice
    {
        return TrackingDevice::where('serial_number', 'CAM-0001')->firstOrFail();
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->image('cam.jpg', 640, 480);
    }

    public function test_camera_capture_uses_trained_detector_and_species_classifier(): void
    {
        $tiger = $this->taxon('tiger', 'Panthera tigris', 'species', 0.97);
        $this->fakeAnalysis([
            ['category' => 'animal', 'confidence' => 0.95, 'bbox' => [0.1, 0.2, 0.4, 0.5], 'species' => $tiger, 'embedding' => $this->vector(1)],
        ], $tiger);

        $res = $this->post("/api/devices/{$this->camera()->device_id}/captures", ['file' => $this->photo()], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.processing_status', 'completed')
            ->assertJsonPath('data.prediction.model_version', 'speciesnet-v4.0.3a')
            ->assertJsonPath('data.prediction.individual_count', 1)
            ->assertJsonPath('data.prediction.predictedSpecies.scientific_name', 'Panthera tigris sumatrae')
            ->assertJsonPath('data.prediction.physical_condition', 'unknown')
            ->assertJsonPath('data.prediction.detected_objects.0.box.xmin', 10)
            ->assertJsonPath('data.prediction.detected_objects.0.box.xmax', 50);

        $this->assertEqualsWithDelta(0.97, (float) $res->json('data.prediction.confidence'), 0.0001);
        $this->assertSame(1, AnimalEmbedding::where('source', 'detection')->count());
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/analyze-image') && $r->hasHeader('X-Api-Key', 'rahasia-uji'));
    }

    public function test_genus_level_result_maps_to_single_species_of_that_genus(): void
    {
        $pongo = $this->taxon('bornean orangutan', 'Pongo pygmaeus', 'species', 0.88);
        $this->fakeAnalysis([['category' => 'animal', 'confidence' => 0.9, 'bbox' => [0, 0, 0.5, 0.5], 'species' => $pongo]], $pongo);

        $this->post("/api/devices/{$this->camera()->device_id}/captures", ['file' => $this->photo()], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.predictedSpecies.scientific_name', 'Pongo abelii');
    }

    public function test_species_outside_master_data_is_kept_as_label_without_false_match(): void
    {
        $chevrotain = ['common_name' => 'mouse-deer family', 'scientific_name' => 'Tragulidae', 'rank' => 'family', 'genus' => null, 'family' => 'Tragulidae', 'score' => 0.9, 'source' => 'classifier'];
        $this->fakeAnalysis([['category' => 'animal', 'confidence' => 0.9, 'bbox' => [0, 0, 0.3, 0.3], 'species' => $chevrotain]], $chevrotain);

        $this->post("/api/devices/{$this->camera()->device_id}/captures", ['file' => $this->photo()], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.processing_status', 'completed')
            ->assertJsonPath('data.prediction.predicted_species_id', null)
            ->assertJsonPath('data.prediction.detected_label', 'Mouse-deer family');
    }

    public function test_reference_photos_enable_appearance_reidentification(): void
    {
        $admin = $this->loginAs('peneliti');
        $orn1 = Animal::where('name_animal', 'ORN-01')->firstOrFail();
        $orn2 = Animal::where('name_animal', 'ORN-02')->firstOrFail();

        $embedResponse = fn (int $seed) => [
            'embedding' => $this->vector($seed), 'embedding_model' => 'MegaDescriptor-T-224',
            'bbox' => [0.1, 0.1, 0.6, 0.7], 'detection_confidence' => 0.93, 'species' => null,
        ];
        Http::fake([self::SERVICE . '/embed' => Http::sequence()->push($embedResponse(11))->push($embedResponse(22))]);
        foreach ([$orn1, $orn2] as $animal) {
            $this->post("/api/animals/{$animal->animal_id}/reference-photos", ['file' => $this->photo()], $admin)
                ->assertCreated()
                ->assertJsonPath('data.animal_detected', true);
        }
        $this->getJson("/api/animals/{$orn2->animal_id}/reference-photos", $admin)->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.vector');

        Http::swap(new \Illuminate\Http\Client\Factory());
        $pongo = $this->taxon('bornean orangutan', 'Pongo pygmaeus', 'species', 0.9);
        $this->fakeAnalysis([['category' => 'animal', 'confidence' => 0.9, 'bbox' => [0, 0, 0.5, 0.5], 'species' => $pongo, 'embedding' => $this->vector(22, 0.15)]], $pongo);
        $this->app['auth']->forgetGuards();

        $res = $this->post("/api/devices/{$this->camera()->device_id}/captures", ['file' => $this->photo()], $this->h)->assertCreated();
        $this->assertSame($orn2->animal_id, $res->json('data.prediction.predicted_animal_id'));
        $this->assertSame('appearance_reid', $res->json('data.prediction.animal_match_method'));

        $candidates = AiPrediction::findOrFail($res->json('data.prediction.prediction_id'))->raw_response['identification_candidates'];
        $this->assertGreaterThan(0.6, $candidates[0]['evidence']['appearance_similarity']);
    }

    public function test_unknown_individual_is_not_forced_to_match(): void
    {
        $orn1 = Animal::where('name_animal', 'ORN-01')->firstOrFail();
        AnimalEmbedding::create(['animal_id' => $orn1->animal_id, 'source' => 'reference', 'model' => 'MegaDescriptor-T-224', 'vector' => $this->vector(11)]);
        AnimalEmbedding::create(['animal_id' => Animal::where('name_animal', 'ORN-02')->value('animal_id'), 'source' => 'reference', 'model' => 'MegaDescriptor-T-224', 'vector' => $this->vector(22)]);

        $pongo = $this->taxon('bornean orangutan', 'Pongo pygmaeus', 'species', 0.9);
        $this->fakeAnalysis([['category' => 'animal', 'confidence' => 0.9, 'bbox' => [0, 0, 0.5, 0.5], 'species' => $pongo, 'embedding' => $this->vector(77)]], $pongo);

        $this->post("/api/devices/{$this->camera()->device_id}/captures", ['file' => $this->photo()], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.predicted_animal_id', null);
    }

    public function test_verification_adds_detection_to_individual_gallery(): void
    {
        $tiger = $this->taxon('tiger', 'Panthera tigris', 'species', 0.97);
        $this->fakeAnalysis([['category' => 'animal', 'confidence' => 0.95, 'bbox' => [0, 0, 0.5, 0.5], 'species' => $tiger, 'embedding' => $this->vector(5)]], $tiger);
        $id = $this->post("/api/devices/{$this->camera()->device_id}/captures", ['file' => $this->photo()], $this->h)->json('data.prediction.prediction_id');
        $hrm = Animal::where('name_animal', 'HRM-01')->firstOrFail();

        $this->postJson("/api/ai-predictions/{$id}/verify", [
            'verification_status' => 'confirmed', 'verified_species_id' => $hrm->species_id, 'verified_animal_id' => $hrm->animal_id,
        ], $this->h)->assertOk();

        $this->assertSame(1, AnimalEmbedding::where('animal_id', $hrm->animal_id)->where('source', 'verified')->count());
        $this->getJson("/api/animals/{$hrm->animal_id}/reference-photos", $this->h)->assertJsonPath('data.0.source', 'verified');
    }

    public function test_person_with_weapon_reported_by_groq_raises_poaching_incident(): void
    {
        config(['services.groq.key' => 'test-key']);
        $this->fakeAnalysis(
            [['category' => 'person', 'confidence' => 0.91, 'bbox' => [0.4, 0.3, 0.2, 0.6]]],
            null,
            ['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
                'animal_detected' => false, 'species_common_name' => 'harimau', 'confidence' => 0.4,
                'human_activity' => ['humans_present' => true, 'weapons' => true, 'traps_or_snares' => false, 'logging' => false, 'vehicles' => false, 'fire_or_smoke' => false],
                'summary' => 'Satu orang membawa senapan.',
            ])]]]])],
        );

        $this->post("/api/devices/{$this->camera()->device_id}/captures", ['file' => $this->photo()], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.threat_level', 'critical')
            ->assertJsonPath('data.prediction.predicted_species_id', null)
            ->assertJsonPath('data.prediction.model_version', 'speciesnet-v4.0.3a+groq:' . config('services.groq.vision_model'));

        $this->assertSame('perburuan', IncidentReport::where('source', 'ai_camera')->value('incident_type'));
    }

    public function test_service_failure_marks_prediction_failed_and_retryable(): void
    {
        Http::fake([self::SERVICE . '/*' => Http::response(['detail' => 'down'], 503)]);

        $res = $this->post('/api/ai/upload', ['file' => $this->photo()], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.processing_status', 'failed');

        Http::swap(new \Illuminate\Http\Client\Factory());
        $tiger = $this->taxon('tiger', 'Panthera tigris', 'species', 0.97);
        $this->fakeAnalysis([['category' => 'animal', 'confidence' => 0.95, 'bbox' => [0, 0, 0.5, 0.5], 'species' => $tiger]], $tiger);
        $this->postJson('/api/ai-predictions/' . $res->json('data.prediction.prediction_id') . '/retry', [], $this->h)
            ->assertOk()
            ->assertJsonPath('data.processing_status', 'completed');
    }

    public function test_unreachable_ai_service_falls_back_to_groq(): void
    {
        config(['services.groq.key' => 'test-key']);
        Http::fake([
            self::SERVICE . '/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
            'api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
                'animal_detected' => true, 'species_common_name' => 'harimau sumatera', 'confidence' => 0.8,
                'individual_count' => 1, 'summary' => 'Satu harimau terlihat.',
            ])]]]]),
        ]);

        $this->post('/api/ai/upload', ['file' => $this->photo()], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.processing_status', 'completed')
            ->assertJsonPath('data.prediction.model_version', 'groq:' . config('services.groq.vision_model'));
    }

    public function test_unreachable_ai_service_without_groq_explains_cause(): void
    {
        Http::fake([self::SERVICE . '/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused')]);

        $summary = $this->post('/api/ai/upload', ['file' => $this->photo()], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.processing_status', 'failed')
            ->json('data.prediction.ai_summary');

        $this->assertStringContainsString('ai-service tidak bisa dihubungi', $summary);
    }

    public function test_video_is_analyzed_over_multiple_frames(): void
    {
        $sambar = $this->taxon('sambar', 'Rusa unicolor', 'species', 0.99);
        $this->fakeAnalysis([['category' => 'animal', 'confidence' => 0.9, 'bbox' => [0, 0, 0.5, 0.5], 'species' => $sambar]], $sambar);
        config(['services.ffmpeg.path' => '/nonexistent/ffmpeg']);

        $video = UploadedFile::fake()->createWithContent('clip.mp4', "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom" . str_repeat("\0", 2048));
        $res = $this->post('/api/ai/upload', ['file' => $video], $this->h)->assertCreated();

        $this->assertSame('completed', $res->json('data.prediction.processing_status'));
        $this->assertSame('Rusa unicolor', $res->json('data.prediction.predictedSpecies.scientific_name'));
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/analyze-video'));
    }

    public function test_health_reports_vision_models(): void
    {
        Http::fake([self::SERVICE . '/health' => Http::response(['status' => 'ok', 'models' => [
            'vision' => ['loaded' => true, 'model' => 'speciesnet-v4.0.3a'], 'reid' => ['loaded' => true, 'model' => 'MegaDescriptor-T-224'],
        ]])]);

        $this->getJson('/api/ai/health', $this->h)
            ->assertOk()
            ->assertJsonPath('data.vision_service.reachable', true)
            ->assertJsonPath('data.vision_service.reidentification.model', 'MegaDescriptor-T-224');
    }

    public function test_unrecognized_file_content_returns_validation_error_not_500(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bad') . '.mp4';
        file_put_contents($path, str_repeat("\0", 2048));
        $fake = new UploadedFile($path, 'clip.mp4', null, null, true);

        $this->post('/api/ai/upload', ['file' => $fake], $this->h)
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }
}

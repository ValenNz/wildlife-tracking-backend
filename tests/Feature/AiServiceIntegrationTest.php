<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiMedia;
use App\Models\AiPrediction;
use App\Models\IncidentReport;
use App\Models\TrackingDevice;
use App\Services\AiPredictionService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\WavFactory;
use Tests\TestCase;

class AiServiceIntegrationTest extends TestCase
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
            'services.ai_audio.url' => self::SERVICE,
            'services.ai_audio.model' => 'yamnet-audioset-v1',
            'services.ai_audio.token' => 'rahasia-uji',
            'services.ai.queue' => false,
        ]);
        $this->h = $this->loginAs('lapangan');
    }

    private function fakeService(string $category, string $label, float $confidence): void
    {
        Http::fake([
            self::SERVICE . '/classify-audio' => Http::response([
                'label' => $label,
                'category' => $category,
                'confidence' => $confidence,
                'species' => null,
                'summary' => "YAMNet mendeteksi {$label}.",
                'detected_at_seconds' => 1.4,
                'predictions' => [['label' => 'Gunshot, gunfire', 'score' => $confidence]],
                'model' => 'yamnet-audioset-v1',
            ]),
            self::SERVICE . '/health' => Http::response(['status' => 'ok', 'model' => 'yamnet-audioset-v1']),
        ]);
    }

    private function recorder(): TrackingDevice
    {
        return TrackingDevice::where('serial_number', 'AUD-0001')->firstOrFail();
    }

    private function wav(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('rec.wav', WavFactory::impulse(2.0));
    }

    public function test_recorder_capture_uses_ai_service_and_raises_poaching_incident(): void
    {
        $this->fakeService('gunshot', 'tembakan / ledakan (Gunshot, gunfire)', 0.82);

        $this->post("/api/devices/{$this->recorder()->device_id}/captures", ['file' => $this->wav()], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.processing_status', 'completed')
            ->assertJsonPath('data.prediction.sound_category', 'gunshot')
            ->assertJsonPath('data.prediction.threat_level', 'critical')
            ->assertJsonPath('data.prediction.model_version', 'service:yamnet-audioset-v1');

        $this->assertSame('perburuan', IncidentReport::where('source', 'ai_audio')->value('incident_type'));
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/classify-audio')
            && $r->hasHeader('X-Api-Key', 'rahasia-uji'));
    }

    public function test_service_down_falls_back_without_failing_upload(): void
    {
        Http::fake([self::SERVICE . '/*' => Http::response('down', 503)]);

        $this->post("/api/devices/{$this->recorder()->device_id}/captures", ['file' => $this->wav()], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.model_version', 'heuristic-audio-v1');
    }

    public function test_health_reports_ai_service_status(): void
    {
        $this->fakeService('environment', 'suara alam', 0.9);

        $this->getJson('/api/ai/health', $this->h)
            ->assertOk()
            ->assertJsonPath('data.audio_service.reachable', true)
            ->assertJsonPath('data.audio_service.model', 'yamnet-audioset-v1')
            ->assertJsonPath('data.queue', 'disabled (analisis langsung)');
    }

    public function test_queue_mode_returns_queued_prediction_then_worker_fills_it(): void
    {
        config(['services.ai.queue' => true]);
        Queue::fake();
        $this->fakeService('chainsaw', 'gergaji mesin (Chainsaw)', 0.77);

        $response = $this->post('/api/ai/upload', ['file' => $this->wav()], $this->h)
            ->assertCreated()
            ->assertJsonPath('data.prediction.processing_status', 'queued');

        $predictionId = $response->json('data.prediction.prediction_id');
        Queue::assertPushedOn('ai', ProcessAiMedia::class, fn ($job) => $job->predictionId === $predictionId);

        (new ProcessAiMedia($response->json('data.media.media_id'), $predictionId))->handle(app(AiPredictionService::class));

        $prediction = AiPrediction::findOrFail($predictionId);
        $this->assertSame('completed', $prediction->processing_status);
        $this->assertSame('chainsaw', $prediction->sound_category);
        $this->assertSame('service:yamnet-audioset-v1', $prediction->model_version);
        $this->assertSame(1, AiPrediction::where('media_id', $prediction->media_id)->count());

        $this->getJson("/api/ai-predictions/{$predictionId}", $this->h)->assertJsonPath('data.processing_status', 'completed');
    }

    public function test_queue_retry_reuses_same_prediction_row(): void
    {
        $this->fakeService('chainsaw', 'gergaji mesin (Chainsaw)', 0.77);
        $first = $this->post('/api/ai/upload', ['file' => $this->wav()], $this->h)->assertCreated();
        $predictionId = $first->json('data.prediction.prediction_id');

        config(['services.ai.queue' => true]);
        Queue::fake();
        $this->postJson("/api/ai-predictions/{$predictionId}/retry", [], $this->h)
            ->assertOk()
            ->assertJsonPath('data.processing_status', 'queued')
            ->assertJsonPath('data.prediction_id', $predictionId);

        (new ProcessAiMedia($first->json('data.media.media_id'), $predictionId))->handle(app(AiPredictionService::class));

        $this->assertSame('completed', AiPrediction::findOrFail($predictionId)->processing_status);
        $this->assertSame(1, AiPrediction::where('media_id', $first->json('data.media.media_id'))->count());
    }

    public function test_failed_job_marks_prediction_failed(): void
    {
        config(['services.ai.queue' => true]);
        Queue::fake();
        $response = $this->post('/api/ai/upload', ['file' => $this->wav()], $this->h)->assertCreated();
        $predictionId = $response->json('data.prediction.prediction_id');

        (new ProcessAiMedia($response->json('data.media.media_id'), $predictionId))->failed(new \RuntimeException('worker mati'));

        $this->assertSame('failed', AiPrediction::findOrFail($predictionId)->processing_status);
    }
}

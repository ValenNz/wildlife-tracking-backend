<?php

namespace Tests\Feature;

use App\Models\AiPrediction;
use App\Models\IncidentReport;
use App\Models\StaffFace;
use App\Models\TrackingDevice;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaceRecognitionAndRecapTest extends TestCase
{
    private const SERVICE = 'http://ai-service.test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
        Storage::fake('local');
        config([
            'services.groq.key' => '',
            'services.ai_vision.url' => self::SERVICE,
            'services.ai_vision.face_threshold' => 0.40,
            'services.ai_vision.face_possible_threshold' => 0.30,
            'services.ai_audio.url' => null,
            'services.ai.queue' => false,
        ]);
    }

    private function face(int $seed, int $mixSeed = 0, float $mix = 0.0): array
    {
        $gen = function (int $s) {
            mt_srand($s);
            $v = [];
            for ($i = 0; $i < 128; $i++) {
                $v[] = mt_rand(-1000, 1000) / 1000;
            }

            return $v;
        };
        $v = $gen($seed);
        if ($mix > 0) {
            $w = $gen($mixSeed);
            foreach ($v as $i => $x) {
                $v[$i] = (1 - $mix) * $x + $mix * $w[$i];
            }
        }
        $n = sqrt(array_sum(array_map(fn ($x) => $x * $x, $v)));

        return array_map(fn ($x) => round($x / $n, 6), $v);
    }

    private function enroll(string $name, int $seed): void
    {
        StaffFace::create(['name' => $name, 'model' => 'yunet+sface', 'vector' => $this->face($seed)]);
    }

    private function fakeImage(array $faces, int $persons = 0, ?array $audio = null, string $endpoint = '/analyze-image'): void
    {
        Http::fake([self::SERVICE . $endpoint => Http::response([
            'detections' => array_fill(0, $persons, ['category' => 'person', 'confidence' => 0.9, 'bbox' => [0.1, 0.1, 0.3, 0.8]]),
            'animal_count' => 0, 'person_count' => $persons, 'vehicle_count' => 0, 'top_species' => null,
            'faces' => $faces, 'audio' => $audio, 'model' => 'speciesnet-v4.0.3a',
        ])]);
    }

    private function capture(array $headers, string $file = 'cam.jpg')
    {
        $camera = TrackingDevice::where('serial_number', 'CAM-0001')->firstOrFail();
        $upload = str_ends_with($file, '.mp4')
            ? UploadedFile::fake()->createWithContent($file, "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom" . str_repeat("\0", 2048))
            : UploadedFile::fake()->image($file, 640, 480);

        return $this->post("/api/devices/{$camera->device_id}/captures", ['file' => $upload], $headers)->assertCreated();
    }

    public function test_registered_staff_is_named_and_stranger_is_labelled_unknown(): void
    {
        $this->enroll('Farrellino', 101);
        $this->enroll('Nuevalen', 202);
        $this->fakeImage([
            ['bbox' => [0.1, 0.1, 0.2, 0.2], 'confidence' => 0.93, 'embedding' => $this->face(101, 999, 0.3)],
            ['bbox' => [0.6, 0.1, 0.2, 0.2], 'confidence' => 0.91, 'embedding' => $this->face(555)],
        ], persons: 2);

        $res = $this->capture($this->loginAs('lapangan'));

        $faces = $res->json('data.prediction.faces');
        $this->assertSame(['staff', 'unknown'], array_column($faces, 'status'));
        $this->assertSame('Farrellino', $faces[0]['label']);
        $this->assertSame('Tidak dikenal / pengunjung', $faces[1]['label']);
        $this->assertArrayNotHasKey('embedding', $faces[0]);
        $res->assertJsonPath('data.prediction.staff_face_count', 1)
            ->assertJsonPath('data.prediction.unknown_face_count', 1)
            ->assertJsonPath('data.prediction.threat_level', 'medium');
        $this->assertStringContainsString('Staf dikenali: Farrellino', $res->json('data.prediction.ai_summary'));
    }

    public function test_only_staff_in_frame_raises_no_human_threat(): void
    {
        $this->enroll('Nuevalen', 202);
        $this->fakeImage([['bbox' => [0.3, 0.2, 0.3, 0.3], 'confidence' => 0.95, 'embedding' => $this->face(202, 7, 0.1)]], persons: 1);

        $res = $this->capture($this->loginAs('lapangan'));

        $res->assertJsonPath('data.prediction.threat_level', 'none')
            ->assertJsonPath('data.prediction.faces.0.name', 'Nuevalen')
            ->assertJsonPath('data.prediction.unknown_face_count', 0);
        $flags = AiPrediction::findOrFail($res->json('data.prediction.prediction_id'))->raw_response['human_activity'];
        $this->assertTrue($flags['staff_present']);
        $this->assertFalse($flags['humans_present']);
    }

    public function test_borderline_similarity_is_flagged_for_verification_not_trusted(): void
    {
        $this->enroll('Farrellino', 101);
        $probe = $this->face(101, 888, 0.66);
        $sim = array_sum(array_map(fn ($a, $b) => $a * $b, $probe, $this->face(101)));
        $this->assertTrue($sim >= 0.30 && $sim < 0.40, "kemiripan uji {$sim}");
        $this->fakeImage([['bbox' => [0.3, 0.2, 0.3, 0.3], 'confidence' => 0.9, 'embedding' => $probe]]);

        $res = $this->capture($this->loginAs('lapangan'));

        $res->assertJsonPath('data.prediction.faces.0.status', 'possible_staff')
            ->assertJsonPath('data.prediction.faces.0.label', 'Kemungkinan Farrellino (perlu verifikasi)')
            ->assertJsonPath('data.prediction.unknown_face_count', 1);
    }

    public function test_face_without_staff_gallery_is_unknown(): void
    {
        $this->fakeImage([['bbox' => [0.3, 0.2, 0.3, 0.3], 'confidence' => 0.9, 'embedding' => $this->face(42)]]);

        $this->capture($this->loginAs('lapangan'))
            ->assertJsonPath('data.prediction.faces.0.status', 'unknown')
            ->assertJsonPath('data.prediction.processing_status', 'completed');
    }

    public function test_video_soundtrack_is_classified_and_raises_incident(): void
    {
        $this->fakeImage([], audio: [
            'label' => 'tembakan / ledakan (Gunshot, gunfire)', 'category' => 'gunshot', 'confidence' => 0.91,
            'summary' => 'Model terlatih mendeteksi tembakan.', 'detected_at_seconds' => 2.4, 'model' => 'yamnet-audioset-v1+wt-heads-v1',
        ], endpoint: '/analyze-video');
        config(['services.ffmpeg.path' => '/nonexistent/ffmpeg']);

        $res = $this->capture($this->loginAs('lapangan'), 'clip.mp4');

        $res->assertJsonPath('data.prediction.sound_category', 'gunshot')
            ->assertJsonPath('data.prediction.threat_level', 'critical');
        $this->assertStringContainsString('+yamnet', $res->json('data.prediction.model_version'));
        $this->assertSame('perburuan', IncidentReport::where('source', 'ai_camera')->value('incident_type'));
    }

    public function test_possible_burst_gunshot_in_video_notifies_without_auto_incident(): void
    {
        $this->fakeImage([], audio: [
            'label' => 'suara manusia / kemungkinan tembakan beruntun (Speech)', 'category' => 'human_voice',
            'confidence' => 0.95, 'possible_gunshot' => true,
        ], endpoint: '/analyze-video');
        config(['services.ffmpeg.path' => '/nonexistent/ffmpeg']);

        $this->capture($this->loginAs('lapangan'), 'clip.mp4')
            ->assertJsonPath('data.prediction.sound_category', 'human_voice')
            ->assertJsonPath('data.prediction.threat_level', 'medium');

        $this->assertSame(0, IncidentReport::where('source', 'ai_camera')->count());
        $this->assertTrue(\App\Models\Notification::where('message', 'like', '%kemungkinan tembakan beruntun%')->exists());
    }

    public function test_admin_manages_staff_faces_and_others_cannot(): void
    {
        Http::fake([self::SERVICE . '/embed-face' => Http::response([
            'embedding' => $this->face(101), 'bbox' => [0.2, 0.2, 0.4, 0.4], 'confidence' => 0.94, 'face_count' => 1, 'face_model' => 'yunet-2023mar+sface-2021dec',
        ])]);
        $admin = $this->loginAs('admin');

        $id = $this->post('/api/staff-faces', ['file' => UploadedFile::fake()->image('f.jpg'), 'name' => 'Farrellino'], $admin)
            ->assertCreated()->assertJsonMissingPath('data.vector')->json('data.staff_face_id');
        $this->getJson('/api/staff-faces', $admin)->assertOk()
            ->assertJsonPath('data.0.name', 'Farrellino')->assertJsonPath('data.0.photo_count', 1)
            ->assertJsonMissingPath('data.0.faces.0.vector');
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/staff-faces', $this->loginAs('peneliti'))->assertStatus(403);
        $this->app['auth']->forgetGuards();

        $this->deleteJson("/api/staff-faces/{$id}", [], $this->loginAs('admin'))->assertOk();
        $this->assertSame(0, StaffFace::count());
    }

    public function test_photo_without_face_is_rejected(): void
    {
        Http::fake([self::SERVICE . '/embed-face' => Http::response(['detail' => 'Tidak ada wajah terdeteksi.'], 422)]);

        $this->post('/api/staff-faces', ['file' => UploadedFile::fake()->image('f.jpg'), 'name' => 'X'], $this->loginAs('admin'))
            ->assertStatus(422);
        $this->assertSame(0, StaffFace::count());
    }

    public function test_import_command_registers_each_staff_folder(): void
    {
        $root = sys_get_temp_dir() . '/staf_' . uniqid();
        foreach (['face_farrellino' => 2, 'face_nuevalen' => 1] as $dir => $n) {
            mkdir("{$root}/{$dir}", 0777, true);
            for ($i = 0; $i < $n; $i++) {
                imagejpeg(imagecreatetruecolor(32, 32), "{$root}/{$dir}/{$i}.jpg");
            }
        }
        file_put_contents("{$root}/README.md", 'x');
        Http::fake([self::SERVICE . '/embed-face' => Http::response(['embedding' => $this->face(5), 'bbox' => [0, 0, 1, 1], 'confidence' => 0.9, 'face_model' => 'sface'])]);

        $this->artisan('staff:import-faces', ['path' => $root])->assertSuccessful();

        $this->assertSame(['Farrellino' => 2, 'Nuevalen' => 1], StaffFace::query()->get()->groupBy('name')->map->count()->sortKeys()->all());

        $this->artisan('staff:import-faces', ['path' => $root, '--replace' => true])->assertSuccessful();
        $this->assertSame(3, StaffFace::count());
    }

    public function test_recap_reports_species_faces_sounds_and_daily_series(): void
    {
        $this->enroll('Farrellino', 101);
        $headers = $this->loginAs('lapangan');
        $this->fakeImage([
            ['bbox' => [0.1, 0.1, 0.2, 0.2], 'confidence' => 0.93, 'embedding' => $this->face(101)],
            ['bbox' => [0.6, 0.1, 0.2, 0.2], 'confidence' => 0.91, 'embedding' => $this->face(9)],
        ], persons: 2);
        $this->capture($headers);
        $this->app['auth']->forgetGuards();

        app()->instance(Factory::class, new Factory());
        $this->fakeImage([], audio: ['label' => 'gergaji mesin (Chainsaw)', 'category' => 'chainsaw', 'confidence' => 0.8], endpoint: '/analyze-video');
        config(['services.ffmpeg.path' => '/nonexistent/ffmpeg']);
        $this->capture($headers, 'clip.mp4');
        $this->app['auth']->forgetGuards();

        $recap = $this->getJson('/api/ai/recap?start_date=' . now()->subDay()->toDateString() . '&end_date=' . now()->toDateString(), $headers)
            ->assertOk()->json('data');

        $this->assertSame('Farrellino', $recap['faces']['staff'][0]['name']);
        $this->assertSame(1, $recap['faces']['unknown_faces']);
        $this->assertSame(['category' => 'chainsaw', 'label' => 'Gergaji mesin', 'count' => 1, 'from_video' => 1], $recap['sounds'][0]);
        $this->assertSame(2, $recap['totals']['threat_detections']);
        $this->assertCount(2, $recap['daily']);

        $this->getJson('/api/ai/summary-report', $headers)->assertOk()
            ->assertJsonPath('data.ai_recap.faces.unknown_faces', 1)
            ->assertJsonPath('data.recommendations.1.title', 'Orang Tidak Dikenal');
    }
}

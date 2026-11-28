<?php

namespace App\Services;

use App\Jobs\ProcessAiMedia;
use App\Models\AiMediaUpload;
use App\Models\AnimalEmbedding;
use App\Models\AiPrediction;
use App\Models\TrackingDevice;
use App\Services\Ai\AnalysisVocabulary;
use App\Services\Ai\AnimalIdentifier;
use App\Services\Ai\AudioAnalyzer;
use App\Services\Ai\FaceMatcher;
use App\Services\Ai\ThreatResponder;
use App\Services\Ai\VisionAnalyzer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AiPredictionService
{
    public const DISK = 'local';

    public function __construct(
        private VisionAnalyzer $vision,
        private AudioAnalyzer $audio,
        private AnimalIdentifier $identifier,
        private ThreatResponder $responder,
        private GeoService $geo,
        private FaceMatcher $faces,
    ) {}

    public static function mediaTypeFromMime(?string $mime): ?string
    {
        return match (true) {
            in_array($mime, ['application/mp4', 'application/x-matroska'], true) => 'video',
            in_array($mime, ['application/ogg', 'application/x-flac'], true) => 'audio',
            str_starts_with((string) $mime, 'image/') => 'image',
            str_starts_with((string) $mime, 'video/') => 'video',
            str_starts_with((string) $mime, 'audio/') => 'audio',
            default => null,
        };
    }

    public function storeMedia(UploadedFile $file, array $attrs): AiMediaUpload
    {
        $mime = $file->getMimeType();
        $type = self::mediaTypeFromMime($mime)
            ?? throw \Illuminate\Validation\ValidationException::withMessages(['file' => ["Isi berkas tidak dikenali sebagai gambar, video, atau audio ({$mime})."]]);

        $device = isset($attrs['device_id']) ? TrackingDevice::find($attrs['device_id']) : null;
        $lat = $attrs['latitude'] ?? $device?->latitude;
        $lon = $attrs['longitude'] ?? $device?->longitude;
        $zoneId = $attrs['zone_id'] ?? $device?->zone_id
            ?? $this->geo->resolveZone($lat !== null ? (float) $lat : null, $lon !== null ? (float) $lon : null)?->zone_id;

        $path = $file->store("ai-media/{$type}/" . now()->format('Y/m'), self::DISK);

        return AiMediaUpload::create([
            'uploaded_by' => $attrs['uploaded_by'] ?? null,
            'report_id' => $attrs['report_id'] ?? null,
            'device_id' => $device?->device_id,
            'zone_id' => $zoneId,
            'media_type' => $type,
            'file_path' => $path,
            'mime_type' => $mime,
            'file_size' => $file->getSize(),
            'latitude' => $lat,
            'longitude' => $lon,
            'captured_at' => $attrs['captured_at'] ?? now(),
            'uploaded_at' => now(),
        ]);
    }

    public function deleteMedia(AiMediaUpload $media): void
    {
        DB::transaction(function () use ($media) {
            $path = $media->file_path;
            $media->predictions()->delete();
            $media->delete();
            Storage::disk(self::DISK)->delete($path);
        });
    }

    public static function queueEnabled(): bool
    {
        return (bool) config('services.ai.queue', false);
    }

    public function analyze(AiMediaUpload $media, ?AiPrediction $existing = null): AiPrediction
    {
        if (! self::queueEnabled()) {
            return $this->process($media, $existing);
        }

        $analysisType = $media->media_type === 'audio' ? 'audio' : 'image';
        if ($existing) {
            $existing->update(['processing_status' => 'queued', 'analysis_type' => $analysisType]);
            $prediction = $existing;
        } else {
            $prediction = AiPrediction::create([
                'media_id' => $media->media_id,
                'analysis_type' => $analysisType,
                'model_version' => 'queued-' . Str::lower((string) Str::ulid()),
                'processing_status' => 'queued',
                'threat_level' => 'none',
                'verification_status' => 'pending',
                'ai_summary' => 'Menunggu antrean analisis AI.',
            ]);
        }

        ProcessAiMedia::dispatch($media->media_id, $prediction->prediction_id);

        return $prediction->fresh(['media', 'predictedSpecies', 'predictedAnimal', 'verifiedSpecies', 'verifier']);
    }

    public function process(AiMediaUpload $media, ?AiPrediction $existing = null): AiPrediction
    {
        $media->loadMissing(['zone', 'device']);
        $analysisType = $media->media_type === 'audio' ? 'audio' : 'image';
        $path = Storage::disk(self::DISK)->path($media->file_path);
        $context = [
            'zone_name' => $media->zone?->zone_name,
            'zone_type' => $media->zone?->zone_type,
            'captured_at' => optional($media->captured_at)->toDateTimeString(),
            'device' => $media->device ? "{$media->device->device_type} {$media->device->model}" : null,
        ];

        $tempFrame = null;
        try {
            if ($media->media_type === 'video') {
                try {
                    $tempFrame = $this->extractVideoFrame($path);
                    $path = $tempFrame;
                } catch (RuntimeException $e) {
                    if (! app(\App\Services\Ai\WildlifeVisionClient::class)->isConfigured()) {
                        throw $e;
                    }
                    $path = '';
                }
            }

            $videoPath = $media->media_type === 'video' ? Storage::disk(self::DISK)->path($media->file_path) : null;
            $attributes = $analysisType === 'audio'
                ? $this->fromAudio($this->audio->analyze($path, $context), $media)
                : $this->fromVision($this->vision->analyze($path, $context, $videoPath), $media);
        } catch (\Throwable $e) {
            Log::error('Analisis AI gagal', ['media_id' => $media->media_id, 'error' => $e->getMessage()]);
            $attributes = [
                'model_version' => $analysisType === 'audio' ? 'audio-pipeline' : $this->vision->modelVersion(),
                'processing_status' => 'failed',
                'ai_summary' => 'Analisis gagal: ' . mb_substr($e->getMessage(), 0, 400),
                'threat_level' => 'none',
            ];
        } finally {
            if ($tempFrame && is_file($tempFrame)) {
                @unlink($tempFrame);
            }
        }

        $attributes += ['analysis_type' => $analysisType, 'processed_at' => now()];

        $embedding = $attributes['_embedding'] ?? null;
        unset($attributes['_embedding']);

        $prediction = $this->save($media, $attributes, $existing);
        $this->storeEmbedding($prediction, $media, $embedding);

        if ($prediction->processing_status === 'completed') {
            try {
                $this->responder->respond($prediction, $media->fresh(['zone', 'device']));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $prediction->fresh(['media', 'predictedSpecies', 'predictedAnimal', 'verifiedSpecies', 'verifier']);
    }

    private function fromVision(array $r, AiMediaUpload $media): array
    {
        $identity = $this->identifier->identify(
            $r['species'],
            $media->latitude, $media->longitude, $media->zone_id,
            $media->captured_at ?? now(),
            $r['distinctive_features'],
            $r['embedding'] ?? null,
        );

        $flags = $r['human_activity'];

        $faces = $this->faces->match($r['faces'] ?? []);
        $staffNames = collect($faces)->where('status', 'staff')->pluck('name')->unique()->values();
        $unknownFaces = collect($faces)->where('status', '!=', 'staff')
            ->groupBy(fn ($f) => (string) ($f['time_seconds'] ?? 'img'))->map->count()->max() ?? 0;
        $personCount = (int) ($r['raw']['counts']['person'] ?? ($flags['humans_present'] ? 1 : 0));
        $flags['staff_present'] = $staffNames->isNotEmpty();
        $flags['humans_present'] = $unknownFaces > 0 || $personCount > $staffNames->count();

        $audio = is_array($r['audio'] ?? null) ? $r['audio'] : null;
        $soundCategory = $audio ? AnalysisVocabulary::soundCategory($audio['category'] ?? $audio['label'] ?? null) : null;
        $soundThreat = $soundCategory ? AnalysisVocabulary::maxThreat(
            AnalysisVocabulary::SOUND_CATEGORIES[$soundCategory]['threat'],
            ($audio['possible_gunshot'] ?? false) ? 'medium' : 'none',
        ) : 'none';

        $threat = AnalysisVocabulary::maxThreat(
            $soundThreat,
            ($flags['weapons'] || $flags['traps_or_snares']) ? 'critical' : 'none',
            ($flags['logging'] || $flags['fire_or_smoke']) ? 'high' : 'none',
            ($flags['humans_present'] && in_array($media->zone?->zone_type, ['koridor_ekologis', null], true)) ? 'medium' : 'none',
            $flags['vehicles'] ? 'low' : 'none',
            $r['physical_condition'] === 'injured' ? 'medium' : 'none',
        );

        $status = match (true) {
            $r['animal_detected'] && ($r['species'] !== null || ($r['species_common_name'] ?? null) !== null) => 'completed',
            ! $r['animal_detected'] && AiPrediction::threatRank($threat) > 0 => 'completed',
            ! $r['animal_detected'] && $faces !== [] => 'completed',
            default => 'unrecognized',
        };

        return [
            'model_version' => mb_substr(($r['model_version'] ?? $this->vision->modelVersion()) . ($audio ? '+yamnet' : ''), 0, 100),
            'processing_status' => $status,
            'predicted_species_id' => $r['species']?->species_id,
            'confidence' => $r['confidence'],
            'detected_label' => mb_substr((string) ($r['species_common_name'] ?? $r['species_scientific_name'] ?? ($r['animal_detected'] ? 'unknown' : 'no_animal')), 0, 100),
            'individual_count' => $r['individual_count'],
            'predicted_animal_id' => $identity['animal']?->animal_id,
            'animal_match_confidence' => $identity['confidence'],
            'animal_match_method' => $identity['method'],
            'physical_condition' => $r['physical_condition'],
            'condition_notes' => $r['condition_notes'],
            'behavior' => $r['behavior'],
            'behavior_notes' => $r['behavior_notes'],
            'threat_level' => $threat,
            'detected_objects' => $r['objects'],
            'faces' => $faces,
            'staff_face_count' => $staffNames->count(),
            'unknown_face_count' => $unknownFaces,
            'sound_category' => $soundCategory,
            'sound_label' => $audio ? mb_substr((string) ($audio['label'] ?? ''), 0, 100) : null,
            'ai_summary' => trim(($r['summary'] ?? '') . ' ' . $this->faceSummary($staffNames->all(), $unknownFaces)
                . ($audio && $soundCategory !== 'environment' ? ' Suara video: ' . ($audio['label'] ?? $soundCategory) . '.' : '')),
            'raw_response' => [
                'human_activity' => $flags,
                'audio_track' => $audio ? array_diff_key($audio, ['predictions' => 1]) : null,
                'distinctive_features' => $r['distinctive_features'],
                'species_label' => [$r['species_common_name'], $r['species_scientific_name']],
                'identification_candidates' => $identity['candidates'],
                'model_output' => $r['raw'],
            ],
            '_embedding' => isset($r['embedding']) ? [
                'vector' => $r['embedding'], 'bbox' => $r['embedding_bbox'] ?? null, 'model' => $r['embedding_model'] ?? 'MegaDescriptor-T-224',
                'species_id' => $r['species']?->species_id,
            ] : null,
        ];
    }

    private function faceSummary(array $staff, int $unknown): string
    {
        $parts = [];
        if ($staff !== []) {
            $parts[] = 'Staf dikenali: ' . implode(', ', $staff) . '.';
        }
        if ($unknown > 0) {
            $parts[] = "{$unknown} wajah tidak dikenal / pengunjung.";
        }

        return implode(' ', $parts);
    }

    private function storeEmbedding(AiPrediction $prediction, AiMediaUpload $media, ?array $embedding): void
    {
        AnimalEmbedding::where('prediction_id', $prediction->prediction_id)->where('source', 'detection')->delete();
        if (! $embedding || empty($embedding['vector'])) {
            return;
        }

        AnimalEmbedding::create([
            'prediction_id' => $prediction->prediction_id,
            'media_id' => $media->media_id,
            'species_id' => $embedding['species_id'],
            'source' => 'detection',
            'model' => mb_substr((string) $embedding['model'], 0, 100),
            'vector' => array_map('floatval', $embedding['vector']),
            'bbox' => $embedding['bbox'],
        ]);
    }

    private function fromAudio(array $r, AiMediaUpload $media): array
    {
        $identity = $r['species']
            ? $this->identifier->identify($r['species'], $media->latitude, $media->longitude, $media->zone_id, $media->captured_at ?? now())
            : ['animal' => null, 'confidence' => null, 'method' => null, 'candidates' => []];

        return [
            'model_version' => $r['model_version'],
            'processing_status' => $r['sound_category'] === 'unknown' ? 'unrecognized' : 'completed',
            'predicted_species_id' => $r['species']?->species_id,
            'confidence' => $r['confidence'],
            'detected_label' => $r['sound_label'],
            'predicted_animal_id' => $identity['animal']?->animal_id,
            'animal_match_confidence' => $identity['confidence'],
            'animal_match_method' => $identity['method'],
            'sound_category' => $r['sound_category'],
            'sound_label' => $r['sound_label'],
            'threat_level' => $r['threat_level'],
            'ai_summary' => $r['summary'],
            'raw_response' => [
                'acoustic_features' => $r['features'],
                'identification_candidates' => $identity['candidates'],
                'engine_errors' => $r['engine_errors'] ?? [],
                'model_output' => $r['raw'],
            ],
        ];
    }

    private function save(AiMediaUpload $media, array $attributes, ?AiPrediction $existing): AiPrediction
    {
        $existing ??= AiPrediction::query()
            ->where('media_id', $media->media_id)
            ->where('model_version', $attributes['model_version'])
            ->first();

        if ($existing && $existing->model_version !== $attributes['model_version']) {
            $duplicate = AiPrediction::query()
                ->where('media_id', $media->media_id)
                ->where('model_version', $attributes['model_version'])
                ->whereKeyNot($existing->getKey())
                ->first();

            if ($duplicate && $duplicate->verification_status === 'pending') {
                $existing->delete();
                $existing = $duplicate;
            } elseif ($duplicate) {
                $attributes['model_version'] = mb_substr($attributes['model_version'] . '#' . $existing->getKey(), 0, 100);
            }
        }

        $reset = [
            'verification_status' => 'pending', 'verified_species_id' => null,
            'verified_animal_id' => null, 'verified_by' => null, 'verified_at' => null,
        ];

        if ($existing) {
            $existing->fill(array_merge(array_fill_keys([
                'predicted_species_id', 'confidence', 'detected_label', 'individual_count', 'predicted_animal_id',
                'animal_match_confidence', 'animal_match_method', 'physical_condition', 'condition_notes',
                'behavior', 'behavior_notes', 'sound_category', 'sound_label', 'detected_objects', 'faces', 'ai_summary', 'raw_response',
            ], null), $reset, $attributes))->save();

            return $existing;
        }

        return AiPrediction::create(array_merge(['media_id' => $media->media_id], $reset, $attributes));
    }

    private function extractVideoFrame(string $videoPath): string
    {
        $ffmpeg = (string) config('services.ffmpeg.path', 'ffmpeg');
        $target = tempnam(sys_get_temp_dir(), 'frame_') . '.jpg';
        $cmd = sprintf('%s -y -loglevel error -ss 00:00:01 -i %s -frames:v 1 %s 2>&1',
            escapeshellcmd($ffmpeg), escapeshellarg($videoPath), escapeshellarg($target));

        exec($cmd, $output, $code);

        if ($code !== 0 || ! is_file($target) || filesize($target) === 0) {
            throw new RuntimeException('Tidak dapat mengambil frame video (pastikan ffmpeg terpasang).');
        }

        return $target;
    }
}

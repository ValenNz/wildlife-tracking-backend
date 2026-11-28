<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class VisionAnalyzer
{
    public function __construct(
        private GroqClient $groq,
        private SpeciesMatcher $matcher,
        private WildlifeVisionClient $models,
    ) {}

    public function modelVersion(): string
    {
        if ($this->models->isConfigured()) {
            return 'speciesnet-v4.0.3a' . ($this->groq->isConfigured() ? '+groq:' . $this->groq->visionModel() : '');
        }

        return 'groq:' . $this->groq->visionModel();
    }

    public function analyze(string $imagePath, array $context = [], ?string $videoPath = null): array
    {
        if ($this->models->isConfigured() && ($videoPath || is_file($imagePath))) {
            try {
                return $this->analyzeWithModels($imagePath, $context, $videoPath);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                if (! $this->groq->isConfigured() || ! is_file($imagePath)) {
                    throw new RuntimeException('ai-service tidak bisa dihubungi (' . config('services.ai_vision.url') . '). Jalankan uvicorn di port 8001.', 0, $e);
                }
                Log::warning('ai-service tidak terjangkau; fallback ke Groq', ['error' => $e->getMessage()]);
            }
        }

        if (! is_file($imagePath)) {
            throw new RuntimeException('File gambar tidak ditemukan.');
        }

        $result = $this->groq->chatJson(
            [$this->groq->imageMessage($this->prompt($context), $imagePath)],
            $this->groq->visionModel(),
            1500,
        );

        return $this->normalize($result['json'], $result['raw']) + ['model_version' => 'groq:' . $this->groq->visionModel()];
    }

    private function analyzeWithModels(string $imagePath, array $context, ?string $videoPath): array
    {
        $det = $videoPath ? $this->models->analyzeVideo($videoPath) : $this->models->analyzeImage($imagePath);
        $detections = is_array($det['detections'] ?? null) ? $det['detections'] : [];
        $top = is_array($det['top_species'] ?? null) ? $det['top_species'] : null;
        $animals = (int) ($det['animal_count'] ?? 0);

        $species = $top ? $this->matcher->matchTaxon($top['scientific_name'] ?? null, $top['rank'] ?? null, $top['genus'] ?? null) : null;
        $withEmbedding = collect($detections)->filter(fn ($d) => ($d['category'] ?? null) === 'animal' && ! empty($d['embedding']));
        $primary = $withEmbedding->sortByDesc('confidence')->first();

        $groq = null;
        $groqError = null;
        if ($this->groq->isConfigured() && is_file($imagePath)) {
            try {
                $result = $this->groq->chatJson(
                    [$this->groq->imageMessage($this->prompt($context, $this->detectionContext($det)), $imagePath)],
                    $this->groq->visionModel(),
                    1500,
                );
                $groq = $this->normalize($result['json'], $result['raw']);
            } catch (\Throwable $e) {
                $groqError = $e->getMessage();
                Log::warning('Groq vision (kondisi/perilaku) gagal; hasil model terlatih tetap dipakai', ['error' => $e->getMessage()]);
            }
        }

        $flags = $groq['human_activity'] ?? array_fill_keys(['weapons', 'traps_or_snares', 'logging', 'fire_or_smoke'], false) + ['notes' => null];
        $flags['humans_present'] = (int) ($det['person_count'] ?? 0) > 0;
        $flags['vehicles'] = (int) ($det['vehicle_count'] ?? 0) > 0;

        $animalDetected = $animals > 0;
        $commonName = $top['common_name'] ?? null;

        return [
            'animal_detected' => $animalDetected,
            'species_common_name' => $commonName ? ucfirst($commonName) : null,
            'species_scientific_name' => $top['scientific_name'] ?? null,
            'species' => $species,
            'confidence' => round((float) ($top['score'] ?? ($detections[0]['confidence'] ?? 0)), 4),
            'individual_count' => $animals,
            'physical_condition' => $animalDetected ? ($groq['physical_condition'] ?? 'unknown') : null,
            'condition_notes' => $groq['condition_notes'] ?? null,
            'behavior' => $animalDetected ? ($groq['behavior'] ?? 'unknown') : null,
            'behavior_notes' => $groq['behavior_notes'] ?? null,
            'distinctive_features' => $groq['distinctive_features'] ?? null,
            'human_activity' => $flags,
            'objects' => $this->objectsFromDetections($detections),
            'summary' => $groq['summary'] ?? $this->summaryFromDetections($det, $commonName),
            'faces' => is_array($det['faces'] ?? null) ? $det['faces'] : [],
            'audio' => is_array($det['audio'] ?? null) ? $det['audio'] : null,
            'embedding' => $primary['embedding'] ?? null,
            'embedding_bbox' => $primary['bbox'] ?? null,
            'embedding_model' => $det['embedding_model'] ?? null,
            'raw' => [
                'engine' => 'speciesnet+megadetector' . ($groq ? '+groq' : ''),
                'detector' => collect($detections)->map(fn ($d) => array_diff_key($d, ['embedding' => 1]))->values()->all(),
                'top_species' => $top,
                'counts' => ['animal' => $animals, 'person' => (int) ($det['person_count'] ?? 0), 'vehicle' => (int) ($det['vehicle_count'] ?? 0)],
                'video_frames' => $det['frames'] ?? null,
                'groq' => $groq['raw'] ?? null,
                'groq_error' => $groqError,
            ],
        ];
    }

    private function detectionContext(array $det): string
    {
        $top = $det['top_species'] ?? null;
        $species = $top ? sprintf('%s (%s, skor %.0f%%)', $top['common_name'] ?? '?', $top['scientific_name'] ?? '?', ($top['score'] ?? 0) * 100) : 'tidak teridentifikasi';

        return sprintf(
            'Model deteksi terlatih (MegaDetector + SpeciesNet + detektor wajah) sudah menemukan: %d satwa, %d manusia, %d kendaraan, %d wajah. Spesies: %s. '
            . 'Gunakan ini sebagai fakta untuk jumlah dan spesies; fokuskan analisis pada kondisi fisik, perilaku, ciri pembeda individu, '
            . 'serta tanda senjata, jerat, penebangan, atau api/asap.',
            (int) ($det['animal_count'] ?? 0), (int) ($det['person_count'] ?? 0), (int) ($det['vehicle_count'] ?? 0), count($det['faces'] ?? []), $species,
        );
    }

    private function objectsFromDetections(array $detections): array
    {
        $label = fn ($d) => match ($d['category'] ?? null) {
            'person' => 'manusia',
            'vehicle' => 'kendaraan',
            default => $d['species']['common_name'] ?? 'satwa',
        };

        return collect($detections)->take(30)->map(fn ($d) => [
            'label' => $label($d),
            'score' => round((float) ($d['confidence'] ?? 0), 4),
            'box' => isset($d['bbox'][3]) ? [
                'xmin' => round($d['bbox'][0] * 100, 2),
                'ymin' => round($d['bbox'][1] * 100, 2),
                'xmax' => round(($d['bbox'][0] + $d['bbox'][2]) * 100, 2),
                'ymax' => round(($d['bbox'][1] + $d['bbox'][3]) * 100, 2),
            ] : null,
        ])->values()->all();
    }

    private function summaryFromDetections(array $det, ?string $common): string
    {
        $parts = [];
        if (($n = (int) ($det['animal_count'] ?? 0)) > 0) {
            $parts[] = $n . ' satwa' . ($common ? " ({$common})" : '');
        }
        if (($n = (int) ($det['person_count'] ?? 0)) > 0) {
            $parts[] = "{$n} manusia";
        }
        if (($n = (int) ($det['vehicle_count'] ?? 0)) > 0) {
            $parts[] = "{$n} kendaraan";
        }

        return $parts
            ? 'Model terlatih mendeteksi ' . implode(', ', $parts) . '. Kondisi fisik dan perilaku belum dinilai (Groq tidak dikonfigurasi).'
            : 'Model terlatih tidak mendeteksi satwa, manusia, maupun kendaraan pada gambar ini.';
    }

    private function prompt(array $context, ?string $detections = null): string
    {
        $species = $this->matcher->promptList() ?: '- (belum ada data spesies)';
        $where = trim(sprintf(
            '%s %s',
            $context['zone_name'] ?? '',
            isset($context['zone_type']) ? "(tipe zona: {$context['zone_type']})" : '',
        ));
        $when = $context['captured_at'] ?? 'tidak diketahui';
        $device = $context['device'] ?? 'kamera lapangan';

        return <<<PROMPT
Anda adalah ahli ekologi satwa liar Sumatra yang menganalisis foto kamera jebak.
Lokasi: {$where}. Waktu pengambilan: {$when}. Sumber: {$device}.

Spesies yang dipantau di sistem (prioritaskan jika cocok):
{$species}
{$detections}

Analisis gambar dan balas HANYA satu objek JSON valid dengan kunci berikut:
{
  "animal_detected": true|false,
  "species_common_name": "nama umum spesies (bahasa Indonesia) atau null",
  "species_scientific_name": "nama ilmiah atau null",
  "confidence": 0.0-1.0,
  "individual_count": bilangan bulat,
  "physical_condition": "healthy|injured|sick|emaciated|unknown",
  "condition_notes": "penjelasan singkat kondisi fisik (luka, bekas jerat, kurus, bulu rontok, dll)",
  "behavior": "foraging|resting|moving|running|drinking|social|parenting|mating|alert|aggressive|unknown",
  "behavior_notes": "penjelasan singkat perilaku",
  "distinctive_features": "ciri fisik pembeda individu (bekas luka, pola belang, telinga sobek, ukuran cula, dll) atau null",
  "human_activity": {
    "humans_present": true|false,
    "weapons": true|false,
    "traps_or_snares": true|false,
    "logging": true|false,
    "vehicles": true|false,
    "fire_or_smoke": true|false,
    "notes": "keterangan singkat"
  },
  "objects": [{"label": "nama objek", "score": 0.0-1.0, "box": {"xmin": 0-100, "ymin": 0-100, "xmax": 0-100, "ymax": 0-100}}],
  "summary": "ringkasan 1-2 kalimat dalam bahasa Indonesia"
}
Koordinat box dalam persen lebar/tinggi gambar. Jika ragu, gunakan "unknown" dan confidence rendah. Jangan mengarang.
PROMPT;
    }

    private function normalize(array $json, string $raw): array
    {
        $human = is_array($json['human_activity'] ?? null) ? $json['human_activity'] : [];
        $flags = [
            'humans_present' => (bool) ($human['humans_present'] ?? false),
            'weapons' => (bool) ($human['weapons'] ?? false),
            'traps_or_snares' => (bool) ($human['traps_or_snares'] ?? $human['traps_snares'] ?? false),
            'logging' => (bool) ($human['logging'] ?? false),
            'vehicles' => (bool) ($human['vehicles'] ?? false),
            'fire_or_smoke' => (bool) ($human['fire_or_smoke'] ?? $human['fire'] ?? false),
            'notes' => isset($human['notes']) ? (string) $human['notes'] : null,
        ];

        $objects = collect(is_array($json['objects'] ?? null) ? $json['objects'] : [])
            ->filter(fn ($o) => is_array($o) && isset($o['label']))
            ->map(fn ($o) => [
                'label' => (string) $o['label'],
                'score' => isset($o['score']) ? round(min(1, max(0, (float) $o['score'])), 4) : null,
                'box' => is_array($o['box'] ?? null) ? array_map('floatval', array_intersect_key(
                    $o['box'], array_flip(['xmin', 'ymin', 'xmax', 'ymax'])
                )) : null,
            ])
            ->take(30)->values()->all();

        $common = $this->str($json['species_common_name'] ?? $json['label'] ?? null);
        $scientific = $this->str($json['species_scientific_name'] ?? null);
        $animalDetected = (bool) ($json['animal_detected'] ?? ($common !== null));

        return [
            'animal_detected' => $animalDetected,
            'species_common_name' => $common,
            'species_scientific_name' => $scientific,
            'species' => $animalDetected ? $this->matcher->match($scientific, $common) : null,
            'confidence' => round(min(1, max(0, (float) ($json['confidence'] ?? $json['score'] ?? 0))), 4),
            'individual_count' => max(0, (int) ($json['individual_count'] ?? ($animalDetected ? 1 : 0))),
            'physical_condition' => $animalDetected ? AnalysisVocabulary::condition($json['physical_condition'] ?? null) : null,
            'condition_notes' => $this->str($json['condition_notes'] ?? null),
            'behavior' => $animalDetected ? AnalysisVocabulary::behavior($json['behavior'] ?? null) : null,
            'behavior_notes' => $this->str($json['behavior_notes'] ?? null),
            'distinctive_features' => $this->str($json['distinctive_features'] ?? null),
            'human_activity' => $flags,
            'objects' => $objects,
            'summary' => $this->str($json['summary'] ?? null),
            'raw' => mb_substr($raw, 0, 4000),
        ];
    }

    private function str(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' || in_array(mb_strtolower($value), ['null', 'none', 'unknown', '-'], true) ? null : $value;
    }
}

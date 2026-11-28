<?php

namespace App\Http\Controllers\Api;

use App\Models\AiPrediction;
use App\Services\Ai\AnalysisVocabulary;
use App\Services\Ai\GroqClient;
use App\Services\Ai\VisionAnalyzer;
use App\Services\AiSummaryService;
use Illuminate\Http\Request;

class AiInsightController extends ApiController
{
    public function summaryReport(Request $request, AiSummaryService $summary)
    {
        $data = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        return $this->success($summary->generateSummary(
            $data['start_date'] ?? now()->subDays(7)->toDateString(),
            $data['end_date'] ?? now()->toDateString(),
        ), 'Ringkasan AI berhasil dibuat');
    }

    public function recap(Request $request, \App\Services\AiRecapService $recap)
    {
        $data = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        return $this->success($recap->build(
            $data['start_date'] ?? now()->subDays(7)->toDateString(),
            $data['end_date'] ?? now()->toDateString(),
        ), 'Rekap data AI');
    }

    public function health(GroqClient $groq)
    {
        return $this->success([
            'success' => $groq->isConfigured(),
            'ai_service' => $groq->healthCheck(),
            'audio_service' => $this->audioServiceHealth(),
            'vision_service' => $this->visionServiceHealth(),
            'queue' => config('services.ai.queue') ? 'enabled (butuh queue:work)' : 'disabled (analisis langsung)',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function detectObjects(Request $request, VisionAnalyzer $vision)
    {
        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp|max:10240']);

        try {
            $result = $vision->analyze($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return $this->error('Gagal menganalisis gambar: ' . $e->getMessage(), 502);
        }

        return $this->success([
            'success' => true,
            'model' => $vision->modelVersion(),
            'objects' => $result['objects'],
            'object_count' => count($result['objects']),
            'species' => $result['species'],
            'physical_condition' => $result['physical_condition'],
            'behavior' => $result['behavior'],
            'human_activity' => $result['human_activity'],
            'summary' => $result['summary'],
        ], 'Deteksi objek berhasil');
    }

    public function threats(Request $request)
    {
        $minLevel = $request->input('min_level', 'medium');
        $levels = array_slice(AiPrediction::THREAT_LEVELS, max(1, AiPrediction::threatRank($minLevel)));

        $query = AiPrediction::query()
            ->with(['media.zone', 'media.device', 'media.report', 'predictedSpecies'])
            ->whereIn('threat_level', $levels)
            ->where('verification_status', '!=', 'rejected');

        if ($request->filled('analysis_type')) {
            $query->where('analysis_type', $request->analysis_type);
        }
        if ($request->filled('start_date')) {
            $query->where('processed_at', '>=', $request->date('start_date'));
        }

        return $this->list($query->orderByDesc('processed_at'));
    }

    public function vocabulary()
    {
        return $this->success([
            'physical_conditions' => AnalysisVocabulary::PHYSICAL_CONDITIONS,
            'behaviors' => AnalysisVocabulary::BEHAVIORS,
            'sound_categories' => collect(AnalysisVocabulary::SOUND_CATEGORIES)->map(fn ($v) => $v['label']),
            'threat_levels' => AnalysisVocabulary::THREAT_LEVELS,
            'device_types' => ['gps' => 'Kalung GPS', 'camera' => 'Kamera jebak', 'audio' => 'Perekam audio'],
        ]);
    }

    private function audioServiceHealth(): array|string
    {
        $url = (string) config('services.ai_audio.url');
        if ($url === '') {
            return 'not_configured (memakai Groq/heuristik)';
        }

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(3)->get(rtrim($url, '/') . '/health');

            return ['url' => $url, 'reachable' => $res->successful(), 'status' => $res->json('status'), 'model' => $res->json('model')];
        } catch (\Throwable) {
            return ['url' => $url, 'reachable' => false, 'status' => 'unreachable', 'model' => null];
        }
    }

    private function visionServiceHealth(): array|string
    {
        $url = (string) config('services.ai_vision.url');
        if ($url === '') {
            return 'not_configured (memakai Groq vision)';
        }

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(3)->get(rtrim($url, '/') . '/health');

            return [
                'url' => $url,
                'reachable' => $res->successful(),
                'detector_species' => $res->json('models.vision'),
                'reidentification' => $res->json('models.reid'),
            ];
        } catch (\Throwable) {
            return ['url' => $url, 'reachable' => false, 'detector_species' => null, 'reidentification' => null];
        }
    }
}

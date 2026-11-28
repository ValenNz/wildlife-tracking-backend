<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WildlifeVisionClient
{
    public function isConfigured(): bool
    {
        return (string) config('services.ai_vision.url') !== '';
    }

    public function analyzeImage(string $path, bool $embed = true): array
    {
        return $this->post('/analyze-image', $path, ['embed' => $embed ? '1' : '0']);
    }

    public function analyzeVideo(string $path, bool $embed = true): array
    {
        return $this->post('/analyze-video', $path, ['embed' => $embed ? '1' : '0']);
    }

    public function embed(string $path): array
    {
        return $this->post('/embed', $path);
    }

    public function embedFace(string $path): array
    {
        return $this->post('/embed-face', $path);
    }

    private function post(string $endpoint, string $path, array $fields = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('AI_VISION_SERVICE_URL belum dikonfigurasi.');
        }
        if (! is_file($path)) {
            throw new RuntimeException('Berkas tidak ditemukan.');
        }

        $response = $this->request()
            ->attach('file', fopen($path, 'r'), basename($path))
            ->post(rtrim((string) config('services.ai_vision.url'), '/') . $endpoint, $fields + ['country' => config('services.ai_vision.country', 'IDN')]);

        if (! $response->successful()) {
            throw new RuntimeException("ai-service {$endpoint} HTTP {$response->status()}: " . mb_substr((string) $response->json('detail', $response->body()), 0, 200));
        }

        return (array) $response->json();
    }

    private function request(): PendingRequest
    {
        return Http::timeout((int) config('services.ai_vision.timeout', 180))
            ->withHeaders(array_filter(['X-Api-Key' => config('services.ai_vision.token')]));
    }
}

<?php

namespace App\Jobs;

use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Services\AiPredictionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAiMedia implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $mediaId, public int $predictionId)
    {
        $this->onQueue('ai');
    }

    public function handle(AiPredictionService $ai): void
    {
        $media = AiMediaUpload::find($this->mediaId);
        $prediction = AiPrediction::find($this->predictionId);

        if (! $media || ! $prediction || $prediction->verification_status !== 'pending') {
            return;
        }

        $prediction->update(['processing_status' => 'processing']);
        $ai->process($media, $prediction);
    }

    public function failed(\Throwable $e): void
    {
        AiPrediction::whereKey($this->predictionId)
            ->whereIn('processing_status', ['queued', 'processing'])
            ->update([
                'processing_status' => 'failed',
                'ai_summary' => 'Analisis di antrean gagal: ' . mb_substr($e->getMessage(), 0, 300),
                'processed_at' => now(),
            ]);
    }
}

<?php

namespace App\Services;

use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Services\Ai\AnalysisVocabulary;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AiRecapService
{
    public function build(string $start, string $end): array
    {
        $from = Carbon::parse($start)->startOfDay();
        $to = Carbon::parse($end)->endOfDay();

        $media = AiMediaUpload::query()->whereBetween('uploaded_at', [$from, $to])->get(['media_id', 'media_type']);
        $preds = AiPrediction::query()
            ->with(['predictedSpecies:species_id,common_name,scientific_name', 'predictedAnimal:animal_id,name_animal', 'media:media_id,media_type,zone_id,captured_at,uploaded_at', 'media.zone:zone_id,zone_name'])
            ->whereBetween('processed_at', [$from, $to])
            ->get();
        $done = $preds->whereIn('processing_status', ['completed', 'unrecognized']);

        $when = fn (AiPrediction $p) => ($p->media?->captured_at ?? $p->processed_at);

        return [
            'period' => ['start' => $from->toDateString(), 'end' => $to->toDateString()],
            'totals' => [
                'media_uploaded' => $media->count(),
                'media_by_type' => $media->groupBy('media_type')->map->count()->all(),
                'predictions' => $preds->count(),
                'by_status' => $preds->groupBy('processing_status')->map->count()->all(),
                'by_verification' => $preds->groupBy('verification_status')->map->count()->all(),
                'animals_detected' => (int) $done->sum('individual_count'),
                'threat_detections' => $done->filter(fn ($p) => AiPrediction::threatRank($p->threat_level) >= 2)->count(),
                'staff_appearances' => (int) $done->sum('staff_face_count'),
                'unknown_faces' => (int) $done->sum('unknown_face_count'),
            ],
            'species' => $done->whereNotNull('predicted_species_id')->groupBy('predicted_species_id')
                ->map(fn (Collection $g) => [
                    'species_id' => $g->first()->predicted_species_id,
                    'common_name' => $g->first()->predictedSpecies?->common_name,
                    'scientific_name' => $g->first()->predictedSpecies?->scientific_name,
                    'detections' => $g->count(),
                    'individuals_counted' => (int) $g->sum('individual_count'),
                    'average_confidence' => round((float) $g->avg('confidence'), 4),
                    'last_seen' => optional($g->map($when)->max())->toIso8601String(),
                ])->sortByDesc('detections')->values()->all(),
            'other_labels' => $done->whereNull('predicted_species_id')->whereNotNull('detected_label')
                ->whereNotIn('detected_label', ['no_animal', 'unknown'])->groupBy('detected_label')->map->count()
                ->sortDesc()->take(15)->all(),
            'individuals' => $done->whereNotNull('predicted_animal_id')->groupBy('predicted_animal_id')
                ->map(fn (Collection $g) => [
                    'animal_id' => $g->first()->predicted_animal_id,
                    'name_animal' => $g->first()->predictedAnimal?->name_animal,
                    'sightings' => $g->count(),
                    'methods' => $g->groupBy('animal_match_method')->map->count()->all(),
                    'last_seen' => optional($g->map($when)->max())->toIso8601String(),
                ])->sortByDesc('sightings')->values()->all(),
            'faces' => $this->faces($done, $when),
            'sounds' => $done->whereNotNull('sound_category')->groupBy('sound_category')
                ->map(fn (Collection $g, $c) => [
                    'category' => $c,
                    'label' => AnalysisVocabulary::SOUND_CATEGORIES[$c]['label'] ?? $c,
                    'count' => $g->count(),
                    'from_video' => $g->filter(fn ($p) => $p->media?->media_type === 'video')->count(),
                ])->sortByDesc('count')->values()->all(),
            'threats' => [
                'by_level' => $done->groupBy('threat_level')->map->count()->all(),
                'recent' => $done->filter(fn ($p) => AiPrediction::threatRank($p->threat_level) >= 2)
                    ->sortByDesc(fn ($p) => $when($p))->take(20)
                    ->map(fn (AiPrediction $p) => [
                        'prediction_id' => $p->prediction_id,
                        'time' => optional($when($p))->toIso8601String(),
                        'zone' => $p->media?->zone?->zone_name,
                        'media_type' => $p->media?->media_type,
                        'threat_level' => $p->threat_level,
                        'label' => $p->sound_label ?: ($p->unknown_face_count ? "{$p->unknown_face_count} orang tidak dikenal" : $p->detected_label),
                        'verification_status' => $p->verification_status,
                    ])->values()->all(),
            ],
            'physical_conditions' => $done->whereNotNull('physical_condition')->groupBy('physical_condition')->map->count()->all(),
            'behaviors' => $done->whereNotNull('behavior')->groupBy('behavior')->map->count()->all(),
            'by_zone' => $done->groupBy(fn ($p) => $p->media?->zone?->zone_name ?? 'Tanpa zona')
                ->map(fn (Collection $g, $zone) => [
                    'zone' => $zone,
                    'detections' => $g->count(),
                    'animals' => (int) $g->sum('individual_count'),
                    'threats' => $g->filter(fn ($p) => AiPrediction::threatRank($p->threat_level) >= 2)->count(),
                    'unknown_faces' => (int) $g->sum('unknown_face_count'),
                ])->sortByDesc('detections')->values()->all(),
            'daily' => $this->daily($done, $from, $to, $when),
            'models' => $done->pluck('model_version')->filter()->unique()->values()->all(),
        ];
    }

    private function faces(Collection $done, callable $when): array
    {
        $staff = [];
        $possible = 0;
        foreach ($done as $p) {
            $faces = collect((array) $p->faces);
            foreach ($faces->where('status', 'staff')->pluck('name')->filter()->unique() as $name) {
                $staff[$name]['appearances'] = ($staff[$name]['appearances'] ?? 0) + 1;
                $staff[$name]['last_seen'] = max($staff[$name]['last_seen'] ?? null, $when($p));
            }
            $possible += $faces->where('status', 'possible_staff')->count();
        }

        return [
            'staff' => collect($staff)->map(fn ($v, $name) => [
                'name' => $name,
                'appearances' => $v['appearances'],
                'last_seen' => optional($v['last_seen'])->toIso8601String(),
            ])->sortByDesc('appearances')->values()->all(),
            'unknown_faces' => (int) $done->sum('unknown_face_count'),
            'media_with_unknown_faces' => $done->where('unknown_face_count', '>', 0)->count(),
            'possible_staff_needing_verification' => $possible,
        ];
    }

    private function daily(Collection $done, Carbon $from, Carbon $to, callable $when): array
    {
        $byDay = $done->groupBy(fn ($p) => optional($when($p))->toDateString());
        $days = [];
        for ($d = $from->copy(); $d->lte($to) && count($days) < 92; $d->addDay()) {
            $g = $byDay->get($d->toDateString(), collect());
            $days[] = [
                'date' => $d->toDateString(),
                'analyzed' => $g->count(),
                'animals' => (int) $g->sum('individual_count'),
                'threats' => $g->filter(fn ($p) => AiPrediction::threatRank($p->threat_level) >= 2)->count(),
                'unknown_faces' => (int) $g->sum('unknown_face_count'),
                'staff_appearances' => (int) $g->sum('staff_face_count'),
            ];
        }

        return $days;
    }
}

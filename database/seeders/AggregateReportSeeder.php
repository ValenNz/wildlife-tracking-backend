<?php

namespace Database\Seeders;

use App\Models\AggregateReport;
use App\Models\Animal;
use App\Models\Species;
use App\Models\TrackingDevice;
use App\Models\User;
use Illuminate\Database\Seeder;

class AggregateReportSeeder extends Seeder
{
    public function run(): void
    {
        $admins = User::query()->where('role', 'pengambil_kebijakan')->get();
        $fallback = User::query()->firstOrFail();

        $reportTypes = [
            'Rekapitulasi Bulanan Satwa',
            'Rekapitulasi Triwulan Perangkat',
            'Laporan Tahunan Konservasi',
            'Laporan Zona Berisiko',
            'Ringkasan Modul AI',
        ];

        foreach ($reportTypes as $index => $reportType) {
            $generator = $admins->get($index % max($admins->count(), 1)) ?? $fallback;

            $periodStart = now()->subMonths($index + 1)->startOfMonth();
            $periodEnd = now()->subMonths($index)->endOfMonth();

            AggregateReport::query()->updateOrCreate(
                ['report_type' => $reportType],
                [
                    'generated_by' => $generator->user_id,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'data_summary' => [
                        'total_animals' => Animal::query()->count(),
                        'active_animals' => Animal::query()->where('is_active', true)->count(),
                        'total_species' => Species::query()->count(),
                        'total_devices' => TrackingDevice::query()->count(),
                        'active_devices' => TrackingDevice::query()->where('status', 'Active')->count(),
                    ],
                    'generated_at' => $periodEnd->copy()->setTime(8, 0, 0),
                ],
            );
        }
    }
}

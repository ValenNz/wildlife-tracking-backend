<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AggregateReportSeeder extends Seeder
{
    public function run(): void
    {
        $reports = [
            [1, 1, 'daily', '2026-10-19', '2026-10-19', '2026-10-19 18:00:00', 'reports/daily_20261019.pdf'],
            [2, 2, 'weekly', '2026-10-13', '2026-10-19', '2026-10-19 18:30:00', 'reports/weekly_20261019.pdf'],
            [3, 1, 'monthly', '2026-10-01', '2026-10-31', '2026-10-19 19:00:00', 'reports/monthly_202610.pdf'],
            [4, 3, 'daily', '2026-10-18', '2026-10-18', '2026-10-18 18:00:00', 'reports/daily_20261018.pdf'],
            [5, 2, 'weekly', '2026-10-06', '2026-10-12', '2026-10-12 18:30:00', 'reports/weekly_20261012.pdf'],
        ];
        foreach ($reports as $r) {
            DB::table('aggregate_reports')->updateOrInsert(
                ['report_id' => $r[0]],
                ['generated_by' => $r[1], 'report_type' => $r[2], 'period_start' => $r[3], 'period_end' => $r[4], 'generated_at' => $r[5], 'file_path' => $r[6]]
            );
        }
    }
}

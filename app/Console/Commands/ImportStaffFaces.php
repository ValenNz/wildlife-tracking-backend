<?php

namespace App\Console\Commands;

use App\Models\StaffFace;
use App\Services\Ai\WildlifeVisionClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportStaffFaces extends Command
{
    protected $signature = 'staff:import-faces {path : folder dataset (subfolder per staf)} {--replace : hapus foto lama staf yang sama sebelum impor}';

    protected $description = 'Daftarkan wajah staf dari folder dataset ke galeri pengenalan wajah';

    public function handle(WildlifeVisionClient $models): int
    {
        if (! $models->isConfigured()) {
            $this->error('AI_VISION_SERVICE_URL belum diisi dan ai-service harus berjalan.');

            return self::FAILURE;
        }

        $root = rtrim((string) $this->argument('path'), '/\\');
        $dirs = glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
        if ($dirs === []) {
            $this->error("Tidak ada subfolder staf di {$root}");

            return self::FAILURE;
        }

        $total = 0;
        foreach ($dirs as $dir) {
            $name = Str::of(basename($dir))->replaceStart('face_', '')->replace(['_', '-'], ' ')->title()->toString();
            $files = array_values(array_filter(glob($dir . DIRECTORY_SEPARATOR . '*') ?: [],
                fn ($f) => preg_match('/\.(jpe?g|png|webp)$/i', $f)));

            if ($this->option('replace')) {
                StaffFace::where('name', $name)->get()->each(function (StaffFace $f) {
                    $f->file_path && Storage::disk('local')->delete($f->file_path);
                    $f->delete();
                });
            }

            $ok = 0;
            foreach ($files as $file) {
                try {
                    $face = $models->embedFace($file);
                } catch (\Throwable $e) {
                    $this->warn("  lewati " . basename($file) . ': ' . mb_substr($e->getMessage(), 0, 120));

                    continue;
                }
                $stored = 'staff-faces/' . Str::uuid() . '.' . strtolower(pathinfo($file, PATHINFO_EXTENSION));
                Storage::disk('local')->put($stored, file_get_contents($file));
                StaffFace::create([
                    'name' => $name,
                    'model' => mb_substr((string) ($face['face_model'] ?? 'sface'), 0, 100),
                    'vector' => array_map('floatval', $face['embedding'] ?? []),
                    'bbox' => $face['bbox'] ?? null,
                    'detection_confidence' => $face['confidence'] ?? null,
                    'file_path' => $stored,
                ]);
                $ok++;
            }
            $total += $ok;
            $this->info("{$name}: {$ok}/" . count($files) . ' foto terdaftar');
        }

        $this->info("Selesai: {$total} foto wajah staf di galeri.");

        return self::SUCCESS;
    }
}

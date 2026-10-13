<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $sqlPath = database_path('wildlife_tracking.sql');

        if (!File::exists($sqlPath)) {
            $this->command->error('File wildlife_tracking.sql tidak ditemukan di folder database/');
            return;
        }

        $sql = File::get($sqlPath);

        // Hapus DELIMITER karena tidak didukung PDO
        $sql = preg_replace('/DELIMITER\s+\$\$/', '', $sql);
        $sql = preg_replace('/DELIMITER\s+;/', '', $sql);
        $sql = str_replace('$$', ';', $sql);

        DB::unprepared($sql);

        $this->command->info('✅ Migration berhasil! Semua tabel wildlife tracking telah diimpor.');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        $tables = [
            'weatherdata', 'notifications', 'aggregatereports',
            'activitylog', 'aipredictions', 'aimediauploads',
            'animalgeozones', 'gpsreadings', 'trackingdevices',
            'incidentreports', 'geozones', 'animals',
            'species', 'users',
            'animals_demo', 'geozones_demo',
            'gpsreadings_100_simulated', 'species_reference'
        ];

        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('✅ Semua tabel wildlife tracking berhasil dihapus.');
    }
};
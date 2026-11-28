<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('species', function (Blueprint $table) {
            $table->string('local_names', 200)->nullable()->after('scientific_name')->comment('nama daerah, mis. "Mawas, Maweh"');
            $table->string('taxon_class', 60)->nullable()->after('local_names');
            $table->string('taxon_order', 60)->nullable()->after('taxon_class');
            $table->string('taxon_family', 60)->nullable()->after('taxon_order');
            $table->text('description')->nullable();
            $table->json('characteristics')->nullable()->comment('ciri-ciri fisik (daftar)');
            $table->string('size_weight', 255)->nullable();
            $table->string('lifespan', 100)->nullable();
            $table->string('activity_pattern', 100)->nullable()->comment('diurnal / nokturnal / krepuskular');
            $table->text('diet')->nullable();
            $table->text('behavior')->nullable()->comment('perilaku & kebiasaan');
            $table->text('habitat')->nullable();
            $table->text('distribution')->nullable()->comment('sebaran (tingkat wilayah, bukan lokasi individu)');
            $table->string('population_estimate', 255)->nullable();
            $table->json('threats')->nullable();
            $table->json('fun_facts')->nullable();
            $table->string('legal_protection', 255)->nullable()->comment('status perlindungan nasional & CITES');
            $table->string('model_path', 500)->nullable()->comment('GLB di storage privat');
            $table->enum('model_source', ['illustrative', 'scan', 'licensed'])->nullable()
                ->comment('illustrative = model bergaya buatan sistem; scan/licensed = model asli');
            $table->string('model_credit', 255)->nullable()->comment('pembuat & lisensi model');
            $table->json('model_hotspots')->nullable()->comment('[{label, description, position, normal}] koordinat model-viewer');
            $table->boolean('is_public')->default(true)->comment('tampil di ensiklopedia untuk pengunjung');
        });
    }

    public function down(): void
    {
        Schema::table('species', function (Blueprint $table) {
            $table->dropColumn([
                'local_names', 'taxon_class', 'taxon_order', 'taxon_family', 'description', 'characteristics', 'size_weight',
                'lifespan', 'activity_pattern', 'diet', 'behavior', 'habitat', 'distribution', 'population_estimate', 'threats',
                'fun_facts', 'legal_protection', 'model_path', 'model_source', 'model_credit', 'model_hotspots', 'is_public',
            ]);
        });
    }
};

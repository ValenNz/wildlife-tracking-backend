<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Species extends Model
{
    use SoftDeletes;

    public static $snakeAttributes = false;

    protected $table = 'species';

    protected $primaryKey = 'species_id';

    protected $fillable = [
        'common_name', 'scientific_name', 'conservation_status', 'local_names', 'taxon_class', 'taxon_order', 'taxon_family',
        'description', 'characteristics', 'size_weight', 'lifespan', 'activity_pattern', 'diet', 'behavior', 'habitat',
        'distribution', 'population_estimate', 'threats', 'fun_facts', 'legal_protection', 'model_credit', 'model_hotspots', 'is_public',
    ];

    protected $hidden = ['model_path'];

    protected $appends = ['has_model'];

    protected function casts(): array
    {
        return [
            'characteristics' => 'array',
            'threats' => 'array',
            'fun_facts' => 'array',
            'model_hotspots' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function getHasModelAttribute(): bool
    {
        return $this->model_path !== null;
    }

    public function animals()
    {
        return $this->hasMany(Animal::class, 'species_id', 'species_id');
    }

    public function predictedIn()
    {
        return $this->hasMany(AiPrediction::class, 'predicted_species_id', 'species_id');
    }

    public function verifiedIn()
    {
        return $this->hasMany(AiPrediction::class, 'verified_species_id', 'species_id');
    }
}

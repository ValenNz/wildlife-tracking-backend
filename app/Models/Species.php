<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Species extends Model
{
    protected $table = 'species';
    protected $primaryKey = 'species_id';
    public $timestamps = false;

    protected $fillable = [
        'common_name',
        'scientific_name',
        'conservation_status',
    ];

    public function animals()
    {
        return $this->hasMany(Animal::class, 'species_id', 'species_id');
    }

    public function predictedByAi()
    {
        return $this->hasMany(AiPrediction::class, 'predicted_species_id', 'species_id');
    }

    public function verifiedByAi()
    {
        return $this->hasMany(AiPrediction::class, 'verified_species_id', 'species_id');
    }
}

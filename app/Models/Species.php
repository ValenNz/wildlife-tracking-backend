<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Species extends Model
{
    use HasFactory;

    protected $table = 'species';

    protected $primaryKey = 'species_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'common_name',
        'scientific_name',
        'conservation_status',
    ];

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'species_id', 'species_id');
    }

    public function aiPredictions(): HasMany
    {
        return $this->hasMany(AIPrediction::class, 'predicted_species_id', 'species_id');
    }

    public function aiPredictionsVerified(): HasMany
    {
        return $this->hasMany(AIPrediction::class, 'verified_species_id', 'species_id');
    }
}

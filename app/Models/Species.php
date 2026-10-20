<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Species extends Model
{
    protected $primaryKey = 'species_id';
<<<<<<< HEAD

    public $incrementing = true;

    protected $keyType = 'int';

    public const UPDATED_AT = null;
    public const CREATED_AT = null;

=======
    public $incrementing = false;
    protected $keyType = 'integer';
    
>>>>>>> 3de367708937d5e810dcfc4b431e1ca54441daf1
    protected $fillable = [
        'species_id',
        'common_name',
        'scientific_name',
        'conservation_status',
    ];

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'species_id', 'species_id');
    }
<<<<<<< HEAD

    public function aiPredictions(): HasMany
    {
        return $this->hasMany(AIPrediction::class, 'predicted_species_id', 'species_id');
    }

    public function aiPredictionsVerified(): HasMany
    {
        return $this->hasMany(AIPrediction::class, 'verified_species_id', 'species_id');
    }
=======
>>>>>>> 3de367708937d5e810dcfc4b431e1ca54441daf1
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Species extends Model
{
    protected $primaryKey = 'species_id';
    public $incrementing = false;
    protected $keyType = 'integer';
    
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
}
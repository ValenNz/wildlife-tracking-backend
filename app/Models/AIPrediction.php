<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIPrediction extends Model
{
    use HasFactory;

    protected $table = 'aipredictions';

    protected $primaryKey = 'prediction_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'media_id',
        'predicted_species_id',
        'confidence_score',
        'predicted_at',
        'verified_by',
        'verified_species_id',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'float',
            'predicted_at' => 'datetime',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(AIMediaUpload::class, 'media_id', 'media_id');
    }

    public function predictedSpecies(): BelongsTo
    {
        return $this->belongsTo(Species::class, 'predicted_species_id', 'species_id');
    }

    public function verifiedSpecies(): BelongsTo
    {
        return $this->belongsTo(Species::class, 'verified_species_id', 'species_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }
}

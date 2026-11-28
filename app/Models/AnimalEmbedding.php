<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalEmbedding extends Model
{
    public const UPDATED_AT = null;

    public static $snakeAttributes = false;

    protected $table = 'animal_embeddings';

    protected $primaryKey = 'embedding_id';

    protected $fillable = [
        'animal_id', 'species_id', 'media_id', 'prediction_id', 'source', 'model', 'vector', 'bbox', 'file_path', 'created_by',
    ];

    protected $hidden = ['vector'];

    protected function casts(): array
    {
        return [
            'vector' => 'array',
            'bbox' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }

    public static function similarity(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        $dot = $na = $nb = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }

        return $na > 0 && $nb > 0 ? $dot / (sqrt($na) * sqrt($nb)) : 0.0;
    }
}

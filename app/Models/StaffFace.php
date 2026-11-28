<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffFace extends Model
{
    public const UPDATED_AT = null;

    public static $snakeAttributes = false;

    protected $table = 'staff_faces';

    protected $primaryKey = 'staff_face_id';

    protected $fillable = ['name', 'user_id', 'model', 'vector', 'bbox', 'detection_confidence', 'file_path', 'created_by'];

    protected $hidden = ['vector'];

    protected function casts(): array
    {
        return [
            'vector' => 'array',
            'bbox' => 'array',
            'detection_confidence' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}

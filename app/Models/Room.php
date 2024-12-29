<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'dungeon_id',
        'name',
        'description',
        'x',
        'y',
        'width',
        'height',
        'type',
        'is_explored',
    ];

    public function dungeon()
    {
        return $this->belongsTo(Dungeon::class);
    }
}

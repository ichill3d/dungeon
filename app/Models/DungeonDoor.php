<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DungeonDoor extends Model
{
    use HasFactory;

    // Specify the table name (optional if it's the default plural form)
    protected $table = 'dungeon_doors';

    // Specify the columns that are mass assignable
    protected $fillable = ['dungeon_id', 'x', 'y'];

    /**
     * Get the dungeon that owns the door.
     */
    public function dungeon()
    {
        return $this->belongsTo(Dungeon::class);
    }
}

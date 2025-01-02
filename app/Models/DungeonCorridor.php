<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DungeonCorridor extends Model
{
    use HasFactory;

    // Define the table name if it doesn't follow Laravel's convention
    protected $table = 'dungeon_corridors';

    // Specify the columns that are mass assignable
    protected $fillable = [
        'dungeon_id', // Foreign key referencing the dungeon
        'cells',      // JSON column that stores the corridor cells
        'trap_triggered'
    ];

    // The 'cells' column will store the JSON data, so we'll cast it to an array automatically
    protected $casts = [
        'cells' => 'array',  // Convert the 'cells' column to an array when retrieved
    ];

    /**
     * Get the dungeon that owns the corridor.
     */
    public function dungeon()
    {
        return $this->belongsTo(Dungeon::class);
    }
}

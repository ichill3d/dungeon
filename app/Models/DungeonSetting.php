<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DungeonSetting extends Model
{
    use HasFactory;

    // If needed, you can specify the table name
    // protected $table = 'settings';

    // Allow mass assignment for key and value
    protected $fillable = ['slug', 'name', 'inspiration'];

    public function dungeons()
    {
        return $this->hasMany(Dungeon::class, 'dungeon_setting_id');  // Use the foreign key column
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DungeonType extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'inspiration', 'description'];

    public function dungeonSetting()
    {
        $this->belongsTo(DungeonSetting::class);
    }
    public function dungeons()
    {
        return $this->hasMany(Dungeon::class, 'dungeon_type_id');  // Use the foreign key column
    }
}

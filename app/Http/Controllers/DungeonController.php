<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Models\DungeonDoor;
use App\Models\Room;
use App\Models\DungeonCorridor;
use Illuminate\Http\Request;

class DungeonController extends Controller
{
    public function showDungeon($id)
    {
        return view('dungeons.show', compact('id'));
    }
}

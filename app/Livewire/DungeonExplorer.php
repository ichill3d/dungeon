<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Dungeon;
use App\Models\Room;
use App\Models\DungeonCorridor;
use App\Models\DungeonDoor;

class DungeonExplorer extends Component
{
    public $dungeon;
    public $rooms = [];
    public $corridors = [];
    public $doors = [];
    public $exploredRooms = [];

    protected $listeners = ['revealRoom', 'revealCorridor', 'revealDoor', 'openDoor'];

    public function mount($dungeonId)
    {
        // Fetch the dungeon and its rooms, corridors, and doors
        $this->dungeon = Dungeon::find($dungeonId);
        $this->rooms = Room::where('dungeon_id', $dungeonId)->get();
        $this->corridors = DungeonCorridor::where('dungeon_id', $dungeonId)->get();
        $this->doors = DungeonDoor::where('dungeon_id', $dungeonId)->get();

    }
    public function openDoor($doorId) {
        $door = DungeonDoor::find($doorId);
        if ($door) {
            $door->is_open = 1;
            $door->save();
        }
    }

    public function revealRoom($roomId) {
        logger('Revealing room: ' . $roomId);
        $room = Room::find($roomId);
        if ($room) {
            $room->is_explored = 1;
            $room->save();
        }
        logger('Room is now explored: ' . $room->is_explored);
    }

    public function revealCorridor($corridorId) {
        logger('Revealing corridor: ' . $corridorId);
        $corridor = DungeonCorridor::find($corridorId);
        if ($corridor) {
            $corridor->is_explored = 1;
            $corridor->save();
        }
        logger('Corridor is now explored: ' . $corridor->is_explored);
    }
    public function revealDoor($doorId) {

        logger('Revealing door: ' . $doorId);
        $door = DungeonDoor::find($doorId);
        if ($door) {
            $door->is_explored = 1;
            $door->save();
        }
        logger('Door is now explored: ' . $door->is_explored);
    }

    public function render()
    {
        $startRoomId = Room::where('type', 'start')->where('dungeon_id', $this->dungeon->id)->pluck('id')->first();
        $bossRoomId = Room::where('type', 'boss')->where('dungeon_id', $this->dungeon->id)->pluck('id')->first();

        return view('livewire.dungeon-explorer', [
            'dungeon' => $this->dungeon,
            'rooms' => $this->rooms,
            'corridors' => $this->corridors,
            'doors' => $this->doors,
            'startRoomId' => $startRoomId,
            'bossRoomId' => $bossRoomId
        ]);
    }
}

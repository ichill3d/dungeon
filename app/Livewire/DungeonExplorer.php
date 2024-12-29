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

    public function mount($dungeonId)
    {
        // Fetch the dungeon and its rooms, corridors, and doors
        $this->dungeon = Dungeon::find($dungeonId);
        $this->rooms = Room::where('dungeon_id', $dungeonId)->get();
        $this->corridors = DungeonCorridor::where('dungeon_id', $dungeonId)->get();
        $this->doors = DungeonDoor::where('dungeon_id', $dungeonId)->get();

    }

    public function getKeyRooms($dungeonId) {
        // Get the room with the minimum X + Y sum
        $lowestRoom = Room::where('dungeon_id', $dungeonId)
            ->selectRaw('id, x + y as x_plus_y')  // Calculate X + Y
            ->orderByRaw('x + y ASC')  // Sort by X + Y in ascending order
            ->first();  // Get the first result (minimum)

        // Get the room with the maximum X + Y sum
        $highestRoom = Room::where('dungeon_id', $dungeonId)
            ->selectRaw('id, x + y as x_plus_y')  // Calculate X + Y
            ->orderByRaw('x + y DESC')  // Sort by X + Y in descending order
            ->first();  // Get the first result (maximum)

        // Get the room with the highest Y and lowest X
        $highestYLowestXRoom = Room::where('dungeon_id', $dungeonId)
            ->orderBy('y', 'desc')  // Highest Y first
            ->orderBy('x', 'asc')   // Lowest X first
            ->first();  // Get the first result

        // Get the room with the lowest Y and highest X
        $lowestYHighestXRoom = Room::where('dungeon_id', $dungeonId)
            ->orderBy('y', 'asc')   // Lowest Y first
            ->orderBy('x', 'desc')  // Highest X first
            ->first();  // Get the first result

        return [
            'lowest' => $lowestRoom,
            'highest' => $highestRoom,
            'highestYLowestX' => $highestYLowestXRoom,
            'lowestYHighestX' => $lowestYHighestXRoom,
        ];
    }


    public function render()
    {
        $keyRooms = $this->getKeyRooms($this->dungeon->id);
        $startRoom = array_shift($keyRooms);

        shuffle($keyRooms);
        $bossRoom = array_shift($keyRooms);
        return view('livewire.dungeon-explorer', [
            'dungeon' => $this->dungeon,
            'rooms' => $this->rooms,
            'corridors' => $this->corridors,
            'doors' => $this->doors,
            'startRoomId' => $startRoom->id,
            'bossRoomId' => $bossRoom->id
        ]);
    }
}

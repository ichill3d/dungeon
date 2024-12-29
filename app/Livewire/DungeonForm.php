<?php

namespace App\Livewire;

use App\Models\Room;
use Livewire\Component;
use App\Models\Dungeon;
use App\Models\DungeonSetting;
use App\Models\DungeonType;
use Illuminate\Support\Facades\Auth;

class DungeonForm extends Component
{
    public $name = '';
    public $description = '';
    public $dungeonTypes = []; // Store types dynamically
    public $selectedDungeonTypeId;
    public $dungeonSettingId = 1;
    public $withMonsters;
    public $withEvents;
    public $withLoot;
    public $withNPCs;
    public $size = 'medium';

    protected $rules = [
        'dungeonSettingId' => 'required|exists:dungeon_settings,id',
        'size' => 'required|in:tiny,small,medium,large,enormous',
    ];

    // Update dungeon types based on selected setting
    public function updatedSettingId()
    {
        $this->dungeonTypes = DungeonType::where('dungeon_setting_id', $this->dungeonSettingId)->get();
        $this->selectedDungeonTypeId = null; // Reset the selected type when the setting changes
    }

    // Save the dungeon after validation
    public function save()
    {
        $this->validate();

        $dimensions = $this->getDimensions($this->size);

        if (empty($this->selectedDungeonTypeId)) {
            $randomDungeonType = DungeonType::where('dungeon_setting_id', $this->dungeonSettingId)->inRandomOrder()->first();
            $this->selectedDungeonTypeId = $randomDungeonType->id;
        }

        // Step 1: Create the Dungeon entry
        $dungeon = Dungeon::create([
            'name' => $this->name,
            'description' => $this->description,
            'dungeont_setting_id' => $this->dungeonSettingId,
            'size' => $this->size,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'user_id' => Auth::id(),
            'session_id' => session('guest_session_id'),
            'dungeon_type_id' => $this->selectedDungeonTypeId,
        ]);

        // Step 2: Generate the Dungeon Grid
        $grid = $dungeon->generateDungeon();



        // Step 3: Save the Grid to the Dungeon
        $dungeon->update([
            'grid' => json_encode($grid),
        ]);

        $keyRooms = $this->getKeyRooms($dungeon->id);
        $startRoomId = $keyRooms['startRoom'];
        $bossRoomId = $keyRooms['bossRoom'];

        $startRoom = Room::find($startRoomId);
        if ($startRoom) {
            $startRoom->type = 'start'; // Assign 'start' type
            $startRoom->save();
        }

        // Update the room type for the boss room
        $bossRoom = Room::find($bossRoomId);
        if ($bossRoom) {
            $bossRoom->type = 'boss'; // Assign 'boss' type
            $bossRoom->save();
        }

        // Step 4: Reset the form fields
        $this->reset(['name', 'description', 'size']);
        session()->flash('success', 'Dungeon created successfully with rooms!');

        // Step 5: Redirect to the Dungeon Grid view
        return redirect()->route('dungeons.show', ['id' => $dungeon->id]);
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

        $keyRooms = [$lowestRoom, $highestRoom, $highestYLowestXRoom, $lowestYHighestXRoom];
        $startRoom = array_shift($keyRooms);
        shuffle($keyRooms);
        $bossRoom = array_shift($keyRooms);

        return [
            'startRoom' => $startRoom->id,
            'bossRoom' => $bossRoom->id,
        ];
    }

    // Get dimensions based on size
    private function getDimensions($size)
    {
        return match ($size) {
            'tiny' => ['width' => 15, 'height' => 15],
            'small' => ['width' => 30, 'height' => 30],
            'medium' => ['width' => 50, 'height' => 50],
            'large' => ['width' => 80, 'height' => 80],
            'enormous' => ['width' => 150, 'height' => 150],
            default => ['width' => 50, 'height' => 50],
        };
    }

    // Render the component with settings and types
    public function render()
    {
        $settings = DungeonSetting::all();
        $types = empty($this->dungeonTypes) ?
            DungeonType::where('dungeon_setting_id', $this->dungeonSettingId)->get() :
            $this->dungeonTypes;
        return view('livewire.dungeon-form', compact('settings', 'types'));
    }
}

<?php

namespace App\Livewire;

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

        // Step 4: Reset the form fields
        $this->reset(['name', 'description', 'size']);
        session()->flash('success', 'Dungeon created successfully with rooms!');

        // Step 5: Redirect to the Dungeon Grid view
        return redirect()->route('dungeons.show', ['id' => $dungeon->id]);
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

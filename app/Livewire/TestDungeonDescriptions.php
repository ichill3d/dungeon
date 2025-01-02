<?php

namespace App\Livewire;

use App\Models\Dungeon;
use App\Models\DungeonSetting;
use App\Models\DungeonType;
use Livewire\Component;
use App\Services\DungeonService;

class TestDungeonDescriptions extends Component
{
    public string $description = '';

    public Dungeon $dungeon;
    public $testHolderContent;
    public $dungeonSettings;
    public $dungeonTypes;
    public int $selectedDungeonSetting = 1;
    public int $selectedDungeonType = 1;

    public ?int $selectedRoomId = 0;

    public $userInspiration = '';
    public $dungeonName = '';
    public $dungeonDescription = '';

    public $generatedRooms = '';



    protected DungeonService $dungeonService;

    public function boot(DungeonService $dungeonService)
    {
        $this->dungeonService = $dungeonService;
    }

    public function mount()
    {
        $this->dungeonSettings = $this->getDungeonSettings();
        $this->dungeonTypes = $this->dungeonService->getDungeonTypes($this->selectedDungeonSetting);
        $this->dungeon = Dungeon::with('rooms', 'setting', 'type', 'corridors')->find(1);
    }

    public function getDungeonSettings()
    {
        return $this->dungeonService->getDungeonSettings();
    }

    public function updatedUserInspiration($value)
    {
        $this->dungeon->user_inspiration = $value;
        $this->dungeon->save();
    }
    public function updatedSelectedDungeonSetting($dungeonSettingId)
    {
        $this->dungeonTypes = $this->dungeonService->getDungeonTypes($dungeonSettingId);
    }

    public function generateDungeonDescription()
    {

        $dungeonSetting = DungeonSetting::find($this->selectedDungeonSetting);
        $dungeonType = DungeonType::find($this->selectedDungeonType);
        if ($dungeonSetting && $dungeonType) {
            $this->dungeon->description = $this->dungeonService->generateDungeonDescription(
                $dungeonSetting,
                $dungeonType,
                $this->dungeon->user_inspiration
            );
            $this->testHolderContent =  $this->dungeon->description;
        }
    }
    public function generateDungeonName()
    {
        $dungeonSetting = DungeonSetting::find($this->selectedDungeonSetting);
        $dungeonType = DungeonType::find($this->selectedDungeonType);
        if ($dungeonSetting && $dungeonType) {
            $this->dungeon->name = $this->dungeonService->generateDungeonName(
                $dungeonSetting,
                $dungeonType,
                $this->dungeon->user_inspiration
            );
            $this->dungeon->save();
            $this->testHolderContent =  $this->dungeonName;
        }
    }

    public function generateDungeonRooms() {
        $selectedRoomId = $this->selectedRoomId;
        $roomIdsArray = $selectedRoomId === 0 ? [] : [$selectedRoomId];
        $this->generatedRooms =
            $this->dungeonService->generateDungeonRooms(
                $this->dungeon,
                $roomIdsArray);

        // Capture dump as string
        ob_start();
        dump($this->generatedRooms);
        $this->testHolderContent = ob_get_clean();
    }

    public function render()
    {
        $this->fill([
            'selectedDungeonType' => $this->dungeon->dungeon_type_id ?? 1,
            'selectedDungeonSetting' => $this->dungeon->dungeon_setting_id ?? 1,
            'userInspiration' => $this->dungeon->user_inspiration ?? '',
            'dungeonName' => $this->dungeon->name ?? '',
            'dungeonDescription' => $this->dungeon->description ?? '',
        ]);


        return view('livewire.test-dungeon-descriptions', [
            'dungeon' => $this->dungeon,
            'dungeonSettings' => $this->dungeonSettings,
            'dungeonTypes' => $this->dungeonTypes,
            'testHolderContent' => $this->testHolderContent,
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Models\DungeonCorridor;
use App\Models\Room;
use Livewire\Component;
use App\Models\Dungeon;
use App\Models\DungeonSetting;
use App\Models\DungeonType;
use Illuminate\Support\Facades\Auth;
use MongoDB\Driver\ReadPreference;

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

        // Assign Room Types
        $this->determineRoomTypes($dungeon->id);

        //Assign Corridor Traps
        $this->determineCorridorTrapped($dungeon->id);

        //getObjectDescription
        $dungeon->getObjectDescriptions($dungeon);

        // Step 4: Reset the form fields
        $this->reset(['name', 'description', 'size']);
        session()->flash('success', 'Dungeon created successfully with rooms!');

        // Step 5: Redirect to the Dungeon Grid view
        return redirect()->route('dungeons.show', ['id' => $dungeon->id]);
    }

    public function getObjectDescriptions($dungeon){

            $openAIService = new OpenAIService(); // Instantiate OpenAI service

            $party = 4;
            $rank = 'novice';

            $prompt = "Let's generate rooms for a dungeon. I will give you a basic information for each room, and you will return the description. You need to generate a description for each room. Await first room. The dungeon is a " . $dungeon->size . " dungeon in the " . $dungeon->setting->name ." setting. And on type: " . $dungeon->type->name . ". If the room is of type monster, supply monster stats for Savage Worlds SWADE RPG rules. All stats should be in the Savage Worlds SWADE RPG rules. The adventurer are from rank: " . $rank . ".";

            $openAIService->generateChatResponse([
                ['role' => 'system', 'content' => $prompt]
            ],
            50);

            $startRoom = $dungeon->rooms->where('type', 'start')->first();
            $bossRoom = $dungeon->rooms->where('type', 'boss')->first();

    $prompt = "Generate details and description for staring room. no combat encounter here. ";
    $startRoomdescription = $openAIService->generateChatResponse([
        ['role' => 'user', 'content' => $prompt]
    ],
        50);
$startRoom->description = $startRoomdescription;
$startRoom->save();

            $rooms = $dungeon->rooms;
            $corridors = $dungeon->corridors;



            foreach ($rooms as $room) {
                switch ($room->type) {
                    // types: 'empty', 'monster', 'trap', 'loot', 'start', 'boss', 'event', 'puzzle', 'exploration', 'other'
                    case 'monster':
                        $randomValue = mt_rand(1, 100);
                        if ($randomValue <= 33) {
                            $difficulty = 'easy';
                        } elseif ($randomValue <= 66) {
                            $difficulty = 'medium';
                        } else {
                            $difficulty = 'hard';
                        }
                        $difficultyText = match ($difficulty) {
                            'easy' => "There are " . $party + rand(-1, 3) . " novice monster(s). ",
                            'medium' => "There are " . $party + rand(-2, 5) . " average monster(s). ",
                            'hard' => "There are " . $party + rand(-2, 2) . " novice monster(s). And there are " . $party + rand(-3, 0) . " hard monster(s).",
                        };
                        $roomTypeContent = "This is a monster room. There is a combat encounter. Difficulty is : " . $difficultyText . ".  ";
                        break;
                    case 'event':
                        $roomTypeContent = "This is an event room. Something good or bad happens here. There could be  a quest here, but not mandatory. Perhaps some NPC encounter, or some dungeon feature. We can be creative. Let's try to include the potential quest resolution in one of the following rooms. No Combat encounter here.";
                        break;
                    case 'puzzle':
                        $roomTypeContent = "This is a puzzle room. The Players should solve a simple or more complex (but solvable) puzzle to continue exploring the dungeon, completing a quest or finding loot.  No Combat encounter here.";
                        break;
                    case 'exploration':
                        $roomTypeContent = "This is an exploration room. The Players should search the room, completing a quest or finding loot.  No Combat encounter here.";
                        break;
                    case 'other':
                        $roomTypeContent = "This is an other room. The Players should search the room, completing a quest or finding loot.  No Combat encounter here.";
                }
                $prompt = "Generate details and description for room of type " . $room->type . ". ";
                $description = $openAIService->generateChatResponse([
                    ['role' => 'user', 'content' => $prompt]
                ],
                    50);
                $room->description = $description;
                $room->save();
            }

    foreach ($corridors as $corridor) {
        $description = $openAIService->generateChatResponse([
            ['role' => 'user', 'content' => $prompt]
        ],
            50);
        $corridor->description = $description;
        $corridor->save();
    }

        $prompt = "Generate details and description for the boss room. no combat encounter here. ";
        $startRoomdescription = $openAIService->generateChatResponse([
            ['role' => 'user', 'content' => $prompt]
        ],
            50);
        $startRoom->description = $startRoomdescription;
        $startRoom->save();



            // Get the response from OpenAI


    }

    public function determineRoomTypes($dungeonId)
    {
        // Get all rooms except 'start' and 'boss'
        $rooms = Room::where('dungeon_id', $dungeonId)
            ->whereNotIn('type', ['start', 'boss'])
            ->get();

        // Calculate the number of rooms for each type based on percentages
        $totalRooms = $rooms->count();
        $monsterRoomsCount = floor($totalRooms * 0.50);  // 50% monster rooms
        $eventRoomsCount = floor($totalRooms * 0.10);    // 10% event rooms
        $puzzleRoomsCount = floor($totalRooms * 0.10);   // 10% puzzle rooms
        $explorationRoomsCount = floor($totalRooms * 0.20); // 20% exploration rooms
        $otherRoomsCount = $totalRooms - $monsterRoomsCount - $eventRoomsCount - $puzzleRoomsCount - $explorationRoomsCount; // Remaining rooms

        // Create an array of room types to be assigned
        $roomTypes = array_merge(
            array_fill(0, $monsterRoomsCount, 'monster'),
            array_fill(0, $eventRoomsCount, 'event'),
            array_fill(0, $puzzleRoomsCount, 'puzzle'),
            array_fill(0, $explorationRoomsCount, 'exploration'),
            array_fill(0, $otherRoomsCount, 'other')
        );

        // Shuffle the room types array to randomize the assignment
        shuffle($roomTypes);

        // Assign random room types to the rooms
        $rooms->each(function ($room, $index) use ($roomTypes) {
            // Assign the room type from the shuffled array
            $room->type = $roomTypes[$index];

            // Save the room type to the database
            $room->save();
        });

        // Return the rooms with updated types
        return $rooms;
    }


    public function determineCorridorTrapped($dungeonId)
    {
        // Get all corridors (rooms of type 'corridor') for the specified dungeon
        $corridors = DungeonCorridor::where('dungeon_id', $dungeonId)
            ->get();

        // Loop through each corridor to assign traps based on a 30% chance
        $corridors->each(function ($corridor) {
            // Determine if the corridor will be trapped (30% chance)
            $isTrapped = mt_rand(1, 100) <= 30 ? 1 : 0;

            // Update the is_trapped column for the corridor
            $corridor->is_trapped = $isTrapped;
            $corridor->save();
        });

        return $corridors;
    }

    public function getKeyRooms($dungeonId) {
        // Get the room with the minimum X + Y sum (Start room)
        $startRoom = Room::where('dungeon_id', $dungeonId)
            ->selectRaw('id, x + y as x_plus_y')  // Calculate X + Y
            ->orderByRaw('x + y ASC')  // Sort by X + Y in ascending order
            ->first();  // Get the room with the minimum X + Y (start room)

        // Get the room with the maximum X + Y sum (Boss room)
        $bossRoom = Room::where('dungeon_id', $dungeonId)
            ->selectRaw('id, x + y as x_plus_y')  // Calculate X + Y
            ->orderByRaw('x + y DESC')  // Sort by X + Y in descending order
            ->first();  // Get the room with the maximum X + Y (boss room)

        // Ensure that the start room and boss room have different IDs
        if ($startRoom->id === $bossRoom->id) {
            // If they are the same, we need to find a different boss room
            // Get the room with the second highest X + Y sum (if any)
            $bossRoom = Room::where('dungeon_id', $dungeonId)
                ->where('id', '!=', $startRoom->id)  // Exclude the start room
                ->selectRaw('id, x + y as x_plus_y')
                ->orderByRaw('x + y DESC')  // Sort by X + Y in descending order
                ->first();
        }

        // Get the room with the highest Y and lowest X (for variety)
        $highestYLowestXRoom = Room::where('dungeon_id', $dungeonId)
            ->orderBy('y', 'desc')  // Highest Y first
            ->orderBy('x', 'asc')   // Lowest X first
            ->first();  // Get the first result

        // Get the room with the lowest Y and highest X (for variety)
        $lowestYHighestXRoom = Room::where('dungeon_id', $dungeonId)
            ->orderBy('y', 'asc')   // Lowest Y first
            ->orderBy('x', 'desc')  // Highest X first
            ->first();  // Get the first result

        // Create an array to hold the key rooms
        $keyRooms = [$startRoom, $bossRoom, $highestYLowestXRoom, $lowestYHighestXRoom];

        // Shuffle the remaining rooms
        shuffle($keyRooms);

        // Assign the start and boss rooms from the shuffled array
        $keyRooms = array_merge([$startRoom], $keyRooms);  // Ensure start room is always first
        $keyRooms = array_merge([$bossRoom], $keyRooms);   // Ensure boss room is always second

        return [
            'startRoom' => $startRoom->id,
            'bossRoom' => $bossRoom->id,
        ];
    }

    // Get dimensions based on size
    private function getDimensions($size)
    {
        return match ($size) {
            'tiny' => ['width' => 25, 'height' => 25],
            'small' => ['width' => 50, 'height' => 50],
            'medium' => ['width' => 100, 'height' => 100],
            'large' => ['width' => 200, 'height' => 200],
            'enormous' => ['width' => 300, 'height' => 300],
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

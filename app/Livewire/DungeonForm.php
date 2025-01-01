<?php
namespace App\Livewire;

use App\Models\Dungeon;
use App\Models\DungeonCorridor;
use App\Models\DungeonSetting;
use App\Models\DungeonType;
use App\Models\Room;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

ini_set('max_execution_time', 300);  // 5 minutes
ini_set('max_input_time', 300);      // 5 minutes
ini_set('memory_limit', '512M');     // 512MB memory

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
    public function updatedDungeonSettingId()
    {
        $this->dungeonTypes = DungeonType::where('dungeon_setting_id', $this->dungeonSettingId)->get();
        $this->selectedDungeonTypeId = null;
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
            'dungeon_setting_id' => $this->dungeonSettingId,
            'dungeon_type_id' => $this->selectedDungeonTypeId,
            'size' => $this->size,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'user_id' => Auth::id(),
            'session_id' => session('guest_session_id'),

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
        $this->getObjectDescriptions($dungeon->id);

        // Step 4: Reset the form fields
        $this->reset(['name', 'description', 'size']);
        session()->flash('success', 'Dungeon created successfully with rooms!');

        // Step 5: Redirect to the Dungeon Grid view
        return redirect()->route('dungeons.show', ['id' => $dungeon->id]);
    }

    public function getObjectDescriptions($dungeonId){

            $dungeon = Dungeon::with('setting', 'type', 'rooms', 'corridors')->find($dungeonId);


            if (!$dungeon) {
                throw new \Exception("Dungeon with ID {$dungeonId} not found.");
            }

            $party = 4;
            $rank = 'novice';



        $openAIService = new OpenAIService(); // Instantiate OpenAI service

        $initialContext = [
            ['role' => 'system',
                'content' => "You will be assisting a player, a group of players or a game master to play a dungeon crawl session. You will resonse only with json objects as prescribred. Lets generate rooms for a dungeon.The dungeon is a " . $dungeon->size . ".The setting is: " . $dungeon->setting->name . ".The dungeon type is: " . $dungeon->type->name . ".The adventurers are from rank: " . $rank . ". No Quest details or explanations to the players. Give any stat related data in the format of the Savage Worlds SWADE RPG rules. Give only one room or corridor at a time. Dont be repetitive with room and corridor descriptions.  Remember, JSON only—no extra text."]
        ];

        $jsonInstructions = [
            'initalInstructions' => 'Always include short summary of the description (1-2 sentences) important: Respond only in valid JSON  with the following structure  Remember, JSON only—no extra text.:',
            'room' => [
                'prompt' => 'Generate short details and description 3-5 sentences for room of type "{type}". Room shape is rectangular, size:{height}m x {width}m . dont mention doors (but there are some).  Remember, JSON only—no extra text.',
                'other' => '
                    {
                      "room_name":"",
                      "room_description":""
                      "room_summary":""
                    }
                ',
                'monster' => '
                    {
                        "room_name":"",
                        "room_description":"",
                        "room_summary":"",
                        "monsters": {
                        "amount": "",
                          "name":"",
                          "description":"",
                          "stats": {
                              "Attributes" : {
                                  "Agility" : "",
                                  "Smarts" : "",
                                  "Spirit" : "",
                                  "Strength" : "",
                                  "Vigor" : ""
                              },
                              "Skills" : {
                                  "Fighting" : "",
                                  "Shooting" : "",
                                  "Notice" : ""
                              },
                              "Pace": "",
                              "Parry" : "",
                              "Toughness": "",
                              "Gear" : {
                                "item":
                                  {
                                      "name" : "",
                                      "description" : ""
                                  }
                              }
                              "SpecialAbilities" : {
                                "ability":
                                  {
                                      "name" : "",
                                      "description" : ""
                                  }
                              }
                            }
                        }

                    }',
                'event' => '
                    {
                        "room_name":"",
                        "room_description":"",
                        "room_summary":"",
                        "event":{
                            "positive_or_negative": "",
                            "description": "",
                            "consequences": ""

                        }
                    }',
                'puzzle' => '
                    {
                        "room_name":"",
                        "room_description":"",
                        "room_summary":"",
                        "puzzle":{
                            "description": "",
                            "solution": "",
                            "reward": ""
                        }
                    }',
                'exploration' => '
                    {
                        "room_name":"",
                        "room_description":"",
                        "room_summary":"",
                        "exploration":{
                            "what_to_explore": "",
                            "how_to_explore": "",
                            "reward": ""
                        }
                    }',
                'start' => '
                    {
                    "room_name":"",
                    "room_description":"",
                    "room_summary":""}
                ',
                'boss' => '
                {
                "room_name":"",
                "room_description":"",
                "room_summary":"",
                "boss_monster":{

                          "name":"",
                          "description":"",
                          "stats": {
                              "Attributes" : {
                                  "Agility" : "",
                                  "Smarts" : "",
                                  "Spirit" : "",
                                  "Strength" : "",
                                  "Vigor" : ""
                              },
                              "Skills" : {
                                  "Fighting" : "",
                                  "Shooting" : "",
                                  "Notice" : ""
                              },
                              "Pace": "",
                              "Parry" : "",
                              "Toughness": "",
                              "Gear" : {
                                "item":
                                  {
                                      "name" : "",
                                      "description" : ""
                                  }
                              },
                              "SpecialAbilities" : {
                                "ability":
                                  {
                                      "name" : "",
                                      "description" : ""
                                  }
                              }
                          }
                }
                        ,
                        "monster": {
                            "amount": "",
                          "name":"",
                          "description":"",
                          "stats": {
                              "Attributes" : {
                                  "Agility" : "",
                                  "Smarts" : "",
                                  "Spirit" : "",
                                  "Strength" : "",
                                  "Vigor" : ""
                              },
                              "Skills" : {
                                  "Fighting" : "",
                                  "Shooting" : "",
                                  "Notice" : ""
                              },
                              "Pace": "",
                              "Parry" : "",
                              "Toughness": "",
                              "Gear" : {
                                "item":
                                  {
                                      "name" : "",
                                      "description" : ""
                                  }
                              },
                              "SpecialAbilities" : {
                                "ability":
                                  {
                                      "name" : "",
                                      "description" : ""
                                  }
                              }

                          }
                        }
}'

            ],
            'corridor' => [
                'prompt' => 'Generate short and quick details and description for corridor. no more than 2-3 sentences. dont mention doors (but there are some).  Remember, JSON only—no extra text.',
                'description' => '{"description":"", "corridor_summary":""}',
                'trap' => '{
                "description":"",
                "effects": ""}',
            ]
        ];



        $messages = $initialContext;


        if($dungeon->name === '' || $dungeon->description === '') {

            $promptDescriptionAddition = $dungeon->description === '' ?
                "a short description of the dungeon. The description should be descriptive and unique аnd thematically related to the setting. " :
                "";
            $promptNameAddition = $dungeon->name === '' ?
                "a name for the dungeon. The name should be creative and unique and thematically related to the setting. " :
                "";
            $prompt = "Generate "
                . $promptNameAddition
                . $promptDescriptionAddition
                . 'The output should be a valid just and only JSON object with the following structure: {"dungeon_name":"", "dungeon_description":""}';
            $input = ['role' => 'user', 'content' => $prompt];

            $nextPrompt  = $messages;
            $nextPrompt[] = $input;
            $dungeonDescriptionResponse = $openAIService->generateChatResponse($nextPrompt, 3000);
            $dungeonData = json_decode($dungeonDescriptionResponse, true);
            if($dungeon->name !== '') {
                $dungeonData['dungeon_name'] = $dungeon->name;
            } else {
                $dungeon->name = $dungeonData['dungeon_name'];
                $dungeon->save();
            }
            if($dungeon->description !== '') {
                $dungeonData['dungeon_description'] = $dungeon->description;
            } else {
                $dungeon->description = $dungeonData['dungeon_description'];
                $dungeon->save();
            }
            $summary = ['role' => 'assistant',
                'content' => 'Dungeon Name: ' . $dungeonData['dungeon_name'] . '. dungeon description: ' . $dungeonData['dungeon_description']
            ];
            $messages[] = $summary;


            sleep(10);
        }









            $startRoom = $dungeon->rooms->where('type', 'start')->first();
            $bossRoom = $dungeon->rooms->where('type', 'boss')->first();

        if($bossRoom->type == 'boss') {
            $prompt =
                "Generate details and description for the boss room. Boss Monster encounter here. Additional " . $party . " " . $rank . " monster(s) here."
                . str_replace(
                    ['{type}', '{height}', '{width}'],
                    ['boss room', $bossRoom->height, $bossRoom->width],
                    $jsonInstructions['room']['prompt']
                )
                . $jsonInstructions['initalInstructions']
                . $jsonInstructions['room']['boss'];
            $input = ['role' => 'user', 'content' => $prompt];

            $nextPrompt  = $messages;
            $nextPrompt[] = $input;


            $bossRoomDescription = $openAIService->generateChatResponse($nextPrompt, 3000);
            sleep(10);


            $bossRoom->description = $bossRoomDescription;
            $bossRoom->save();
            $bossRoomData = json_decode($bossRoomDescription, true);
            $summary = ['role' => 'assistant',
                'content' => 'Room Name: ' . $bossRoomData['room_name'] . '. Room Summary: ' . $bossRoomData['room_summary']
            ];
            $messages[] = $summary;

            $bossRoom->save();
        }


            $prompt = str_replace(
                    ['{type}', '{height}', '{width}'],
                    ['starting room', $startRoom->height, $startRoom->width],
                    $jsonInstructions['room']['prompt']
                )
                . $jsonInstructions['initalInstructions']
                . $jsonInstructions['room']['start']
            ;
            $input = ['role' => 'user', 'content' => $prompt];
            $nextPrompt  = $messages;
            $nextPrompt[] = $input;
            $startRoomdescription = $openAIService->generateChatResponse($nextPrompt ,2000);
             sleep(10);
            $startRoom->description = $startRoomdescription;
            $startRoomData = json_decode($startRoomdescription, true);
            logger('start room data', $startRoomData);
            $summary = ['role' => 'assistant',
                'content' => 'Room Name: '.$startRoomData['room_name'] . '. Room Summary: ' . $startRoomData['room_summary']
                ];
            $messages[] = $summary;

            $startRoom->save();



            $rooms = $dungeon->rooms->whereNotIn('type', ['start', 'boss']);



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
                            'hard' => "There are " . $party + rand(-2, 2) . " novice monster(s). And there are " . $party + rand(-3, 0) . " hard monster(s). ",
                        };
                        $roomTypeContent = "This is a monster room. There is a combat encounter. Give the monster stats for the Savage Worlds RPG SWADE system. essential stats and gear stats with range and damage dice. Difficulty is : " .$difficulty. ". " . $difficultyText . ".  ";
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
                    default:
                        $roomTypeContent = "This is an other room. The Players should search the room, completing a quest or finding loot.  No Combat encounter here.";
                }
                // Merge initial context and room type content for description generation
                $prompt =
                    str_replace(
                        ['{type}', '{height}', '{width}'],
                        [$room->type, $room->height, $room->width],
                        $jsonInstructions['room']['prompt']
                    )
                    .$roomTypeContent
                    . $jsonInstructions['initalInstructions']
                    . $jsonInstructions['room'][$room->type]
                ;
                $input = ['role' => 'user', 'content' => $prompt];
                $nextPrompt  = $messages;
                $nextPrompt[] = $input;
                // Up to 3 attempts
                $maxAttempts = 3;
                $attempt = 0;
                $roomData = null;

                while ($attempt < $maxAttempts) {
                    $attempt++;

                    // Call the API
                    $roomDescription = $openAIService->generateChatResponse($nextPrompt, 3000);
                    sleep(10);
                    logger("Attempt #{$attempt} response: " . $roomDescription);

                    // Try to parse as JSON
                    $roomData = json_decode($roomDescription, true);

                    // Check if it's valid JSON and has expected keys
                    if (
                        json_last_error() === JSON_ERROR_NONE
                        && is_array($roomData)
                        && isset($roomData['room_name'], $roomData['room_summary'])
                    ) {
                        // Great, we have valid JSON with the fields we need
                        break;
                    }

                    // If we get here, the JSON was invalid or missing keys.
                    // We can add an extra message to the conversation to instruct the model to correct itself:
                    $messages[] = [
                        'role' => 'system',
                        'content' => "Your last response was invalid JSON or missing required keys. " .
                            "Please output ONLY valid JSON with 'room_name' and 'room_summary'. No extra explanation."
                    ];
                }

// After the loop, check if we have valid data
                if (
                    json_last_error() !== JSON_ERROR_NONE
                    || !is_array($roomData)
                    || !isset($roomData['room_name'], $roomData['room_summary'])
                ) {
                    // Handle the failure case: model never returned valid JSON
                    logger('Failed to get a valid JSON structure for room data after multiple attempts.');
                    // Decide what to do here: throw an exception, revert to a fallback, etc.
                } else {
                    // We have valid data
                    logger("Final room data: ", $roomData);

                    // Save room description if desired
                    $room->description = $roomDescription;
                    $room->save();

                    // Log or proceed
                    $summary = [
                        'role'    => 'assistant',
                        'content' => 'Room Name: ' . $roomData['room_name']
                            . '. Room Summary: ' . $roomData['room_summary']
                    ];
                    $messages[] = $summary;
                }


            }

        $corridors = $dungeon->corridors;

        $messages = $initialContext;


            if(!empty($corridors)) {
                foreach ($corridors as $corridor) {

                    $prompt =
                        $jsonInstructions['corridor']['prompt']
                    . $jsonInstructions['initalInstructions']
                    . $jsonInstructions['corridor']['description']
                    ;
                    $input = ['role' => 'user', 'content' => $prompt];
                    $nextPrompt  = $messages;
                    $nextPrompt[] = $input;
                    $corridorDescription = $openAIService->generateChatResponse($nextPrompt ,3000);
                    sleep(10);
                    logger( $corridorDescription);

                    $corridor->description = $corridorDescription;
                    $corridorData = json_decode($corridorDescription, true);
                    logger($corridorDescription);
                    $summary = ['role' => 'assistant',
                        'content' => '. Corridor Summary: ' ,  $corridorData['corridor_summary']
                    ];
                    $messages[] = $summary;

                    if ($corridor->is_trapped == 1) {
                        $prompt = "This is a trapped corridor. generate a short description of the trap with stats and effects. Savage Worlds SWADE RPG rules."
                        . $jsonInstructions['initalInstructions']
                        . $jsonInstructions['corridor']['trap']
                        ;

                        $input = ['role' => 'user', 'content' => $prompt];
                        $nextPrompt  = $messages;
                        $nextPrompt[] = $input;
                        $trapDescription = $openAIService->generateChatResponse($nextPrompt ,3000);
                        sleep(10);
                        $corridor->trap_description = $trapDescription;

                    }

                    $corridor->save();
                }
            }






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

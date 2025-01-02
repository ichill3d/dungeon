<?php

namespace App\Services;

use App\Models\DungeonSetting;
use App\Models\DungeonType;
use App\Services\OpenAIService;

class DungeonService
{
    public function getDungeonSettings()
    {
        return DungeonSetting::all();
    }
    public function getDungeonTypes($dungeonSettingId) {
        return DungeonType::where("dungeon_setting_id", $dungeonSettingId)->get();
    }

    public function generateDungeonDescription($dungeonSetting, $dungeonType, $dungeonUserInspiration) {
        $openAIService = new OpenAIService();
        $initialContext = "Generate a short, unique and creative and simple dungeon description for a $dungeonType->name in the  $dungeonSetting->name RPG setting. Maximum 3 sentences. Don't include dungeon name. Consider this as inspiration: $dungeonType->inspiration"
            ."please include this as base for the description: $dungeonUserInspiration ."
            ."don't reveal any specific details about the dungeon. just a quick inspirational description."
        ;
        $message =[
           ['role' => 'user', 'content' => $initialContext, 'type' => 'text']
        ];
        return $openAIService->generateChatResponse($message, 3000);
    }
    public function generateDungeonName($dungeonSetting, $dungeonType, $dungeonUserInspiration) {
        $openAIService = new OpenAIService();
        $initialContext = "Generate a short, unique and creative and simple dungeon name for a $dungeonType->name in the  $dungeonSetting->name RPG setting. Don't include dungeon name. Consider this as inspiration: $dungeonType->inspiration"
            ."please include this as base for the name: $dungeonUserInspiration ."
            ."don't reveal any specific details about the dungeon. just a quick inspirational name."
        ;
        $message =[
           ['role' => 'user', 'content' => $initialContext, 'type' => 'text']
        ];
        return $openAIService->generateChatResponse($message, 3000);
    }

    public function generateDungeonRooms(
        $dungeon,
        $roomIdsArray = []
        ) {

        ini_set('max_execution_time', 300);  // 5 minutes
        ini_set('max_input_time', 300);      // 5 minutes
        ini_set('memory_limit', '512M');     // 512MB memory

        $openAIService = new OpenAIService();



        if(true) {
            $monsters_template = '{
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
                                  "[skill name]" : "",
                                  ...
                              },
                              "Pace": "",
                              "Parry" : "",
                              "Toughness": "",
                              "Gear" : {
                                "item":
                                  {
                                      "name" : "",
                                      "description_and_damage" : ""
                                  },
                                  ...
                              }
                              "SpecialAbilities" : { // if necessary
                                "ability":
                                  {
                                      "name" : "",
                                      "description" : ""
                                  }
                              }
                            }
                        }';
        } //monster template
        $jsonInstructions = [
            'initalInstructions' => 'Always include short summary of the description (1-2 sentences) important: Remember, JSON only—no extra text.  Respond only in valid JSON with the following structure:',
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
                        "monsters": ' .$monsters_template . '

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
                    "boss_monster":" ' .$monsters_template . ',
                    "monsters": ' .$monsters_template . '
                }
}'

            ],
            'corridor' => [
                'prompt' => 'Generate short and quick details and description for corridor. no more than 2-3 sentences. dont mention doors (but there are some).  Remember, JSON only—no extra text.',
                'description' => '{"description":"", "corridor_summary":""}',
                'trap' => '{
                "description":"",
                "effects": ""}',
            ],
            'trap' => '{
            "description":"",
            "effects": ""}'
        ];


        $initialContext = "You will be assisting a player, a group of players or a game master to play a dungeon crawl RPG session. You will resonse only with json objects as prescribred. Lets generate rooms for a dungeon.The dungeon is a {$dungeon->size}.The setting is: {$dungeon->setting->name}.The dungeon type is: {$dungeon->type->name}.The adventurers are from rank: {$dungeon->party_rank}. No Quest details or explanations to the players. Give any stats related data in the format of the Savage Worlds SWADE RPG rules. Give only one room or corridor at a time. Dont be repetitive with room and corridor descriptions.  Consider this as inspiration: {$dungeon->type->inspiration} please include this as base for the rooms: {{$dungeon->user_inspiration}. Dungeon Name is: {$dungeon->name}.  Remember, JSON only - no extra text.";


        $messages =[
           ['role' => 'system', 'content' => $initialContext]
        ];

        logger("Generating rooms for dungeon: ", $roomIdsArray);
        if(!empty($roomIdsArray)) {
            $rooms = $dungeon->rooms->whereIn('id', $roomIdsArray);
        } else {
            $rooms = $dungeon->rooms;
        }
        $result = [];


        foreach ($rooms as $room) {
                switch ($room->type) {
                    case 'start':
                        $roomTypeContent = "This is the starting room.  No Combat encounter here.";
                        break;
                    case 'boss':
                        $roomTypeContent = "This is the boss room. The boss combat will take place here. Let it be unique and interesting.  Give the enemies stats for the Savage Worlds RPG SWADE system. essential stats and gear stats with range and damage dice. the enemies should include the skill 'Fighting' if equipped with melee weapon. 'Shooting' if equipped with a ranged weapon. 'Arcane' if equipped with a magical weapon or have magical special ability. These are not the only skills an enemy can have.";
                        break;
                    case 'monster':
                        $difficultyText = match ($room->combat_difficulty) {
                            'easy' => "There are " . $dungeon->party_size + rand(-1, 3) . " novice enemies(s). ",
                            'medium' => "There are " . $dungeon->party_size + rand(-2, 5) . " average enemies(s). ",
                            'hard' => "There are " . $dungeon->party_size + rand(-2, 2) . " novice enemies(s). And there are " . $dungeon->party_size + rand(-3, 0) . " hard enemies(s). ",
                        };
                        $roomTypeContent = "There is a combat encounter in this room. This is not the boss room. Give the enemies stats for the Savage Worlds RPG SWADE system. essential stats and gear stats with range and damage dice. the enemies should include the skill 'Fighting' if equipped with melee weapon. 'Shooting' if equipped with a ranged weapon. 'Arcane' if equipped with a magical weapon or have magical special ability. These are not the only skills an enemy can have. Enemies Difficulty is : " . $room->combat_difficulty . ". " . $difficultyText . ".  ";

                        break;
                    case 'event':
                        $roomTypeContent = "This is an event room. Something good or bad happens here. There could be  a quest here, but not mandatory. Perhaps some NPC encounter, or some dungeon feature. We can be creative. Let's try to include the potential quest resolution in one of the following rooms. No Combat encounter here.";
                        break;
                    case 'puzzle':
                        $roomTypeContent = "This is a puzzle room. The Players should solve a puzzle to continue exploring the dungeon, completing a quest or finding loot. Respond with an actual puzzle with what the players are seeing. Thr players should be able to figure out the solution only by reading the description. If the puzzle is a riddle, include the riddle. If the puzzle is logic, include the hints of how it should be solved. Please make absolutely sire the puzzle can be solved with the information given to the players. don't give out the solution in the description. the solution should be in it's own value in the json onject.  No Combat encounter here.";
                        break;
                    case 'exploration':
                        $roomTypeContent = "This is an exploration room. The Players should search the room, completing a quest or finding loot.  No Combat encounter here.";
                        break;
                    case 'other':
                    default:
                        $roomTypeContent = "This is an other room. The Players should search the room, completing a quest or finding loot.  No Combat encounter here.";
                }

                $prompt =
                    str_replace(
                        ['{type}', '{height}', '{width}'],
                        [$room->type, $room->height, $room->width],
                        $jsonInstructions['room']['prompt']
                    )
                    . $roomTypeContent
                    . $jsonInstructions['initalInstructions']
                    . $jsonInstructions['room'][$room->type];
                $input = ['role' => 'user', 'content' => $prompt];
                $nextPrompt = $messages;
                $nextPrompt[] = $input;

                $maxAttempts = 3;
                $attempt = 0;
                while ($attempt < $maxAttempts) {
                    $attempt++;

                    $generatedRoom = $openAIService->generateChatResponse($nextPrompt, 3000, true);

                    $roomData = json_decode($generatedRoom, true);
                    if (
                        is_array($roomData)
                        && isset($roomData['room_name'], $roomData['room_summary'])
                    ) {
                        break;
                    }

                    $messages[] = [
                        'role' => 'system',
                        'content' => "Your last response was invalid JSON or missing required keys. " .
                            "Please output ONLY valid JSON with 'room_name' and 'room_summary'. No extra explanation."
                    ];
                }

                if (
                    !is_array($roomData)
                    || !isset($roomData['room_name'], $roomData['room_summary'])
                ) {
                    logger('Failed to get a valid JSON structure for room data after multiple attempts.');
                } else {
                    $room->description = $generatedRoom;
                    $room->save();
                    $summary = ['role' => 'assistant',
                        'content' => $roomData['room_name'] . " " . $roomData['room_summary']
                    ];
                    $messages[] = $summary;
                    $result[$room->id]['propmpt'] = $prompt;
                    $result[$room->id]['messages'] = $messages;
                    $result[$room->id]['content'] = $generatedRoom;
                }
                sleep(5);
            }


        $messages =[
            ['role' => 'system', 'content' => $initialContext]
        ];

        $corridors = $dungeon->corridors;


        $corridorsJson = [];
        $c = 1;
        foreach ($corridors->take(5) as $corridor) {
            $corridorsJson["corridor_" . $c] = $corridor->description ?? "";
            $c++;
        }
        $corridor_format = json_encode($corridorsJson);

        $corridors_context = "Generate descriptions of several corridors minding the dungeon theme and setting. Don't mention the number of the corridor. maximal 2 sentences per description. Please return just a JSON  in the format: " . $corridor_format;


        $messages[] = ['role' => 'user', 'content' => $corridors_context];
        $generatedCorridors = $openAIService->generateChatResponse($messages, 3000, true);

        $generatedCorridors = json_decode($generatedCorridors, true);

        $c = 1;
        foreach ($corridors as $corridor) {
            if (count($generatedCorridors) > count($corridors)) {
                shuffle($generatedCorridors);
                $corridor->description = $generatedCorridors[0];
            } else {
                $corridor->description = $generatedCorridors['corridor_' . $c];
            }
            $corridor->save();
            $c++;
        }

        sleep(5);

        $trappedCorridors = $corridors->where('is_trapped', 1);
        foreach ($trappedCorridors as $corridor) {
            $prompt = "This is a trapped corridor. generate a short description of the trap with stats and effects. Savage Worlds SWADE RPG rules."
                . $jsonInstructions['initalInstructions']
                . $jsonInstructions['trap']
            ;
            $input = ['role' => 'user', 'content' => $prompt];
            $nextPrompt  = $messages;
            $nextPrompt[] = $input;
            $trapDescription = $openAIService->generateChatResponse($nextPrompt ,3000, true);
            sleep(2);
            $corridor->trap_description = $trapDescription;
            $corridor->save();
        }




        return $result;

    }



    public function getInitialContext() {

    }
}

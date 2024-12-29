<?php

namespace App\Models;

use App\Services\OpenAIService;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dungeon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'size',
        'width',
        'height',
        'user_id',
        'session_id',
        'grid',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
    public function settings()
    {
        return $this->hasOne(DungeonSetting::class);
    }

    /**
     * Initialize an empty grid with empty spaces (#).
     */
    public function initializeGrid()
    {
        return array_fill(0, $this->height, array_fill(0, $this->width, '#'));
    }

    /**
     * Generate the dungeon with rooms, doors, and corridors.
     */
    public function generateDungeon()
    {
        $grid = $this->initializeGrid();

        // Step 1: Place the initial room in the center
        $centerX = intdiv($this->width, 2);
        $centerY = intdiv($this->height, 2);
        $initialRoomWidth = rand(4, 8);
        $initialRoomHeight = rand(4, 8);

        $initialRoomX = $centerX - intdiv($initialRoomWidth, 2);
        $initialRoomY = $centerY - intdiv($initialRoomHeight, 2);

        $this->placeRoom($grid, $initialRoomX, $initialRoomY, $initialRoomWidth, $initialRoomHeight);

        // Step 2: Place doors (1–4) on the initial room
        $initialDoors = $this->placeDoors($grid, $initialRoomX, $initialRoomY, $initialRoomWidth, $initialRoomHeight, true);

        // Step 3: Process each door
        foreach ($initialDoors as $door) {
            $this->processDoor($grid, $door);
        }

        $this->cleanupDoors($grid);

        $this->markExits($grid);

        $this->placeStartingLocation($grid);

        $this->placeBossRoom($grid);

        return $grid;
    }

    /**
     * Place the starting location for heroes.
     */
    private function placeStartingLocation(&$grid)
    {
        $exitTiles = [];

        // Step 1: Collect all 'E' tiles (Exits)
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($grid[$y][$x] === 'E') {
                    $exitTiles[] = ['x' => $x, 'y' => $y];
                }
            }
        }

        // Step 2: Choose a random exit if available
        if (!empty($exitTiles)) {
            $start = $exitTiles[array_rand($exitTiles)];
            $grid[$start['y']][$start['x']] = 'S'; // Mark start
            logger("Starting location placed at Exit: ({$start['x']}, {$start['y']})");
            return;
        }

        // Step 3: Search for Edge Rooms ('R') Adjacent to the Actual Grid Edge
        for ($y = 1; $y < $this->height - 1; $y++) {
            for ($x = 1; $x < $this->width - 1; $x++) {
                if ($grid[$y][$x] === 'R' && $this->isAdjacentToPhysicalEdge($x, $y, $grid)) {
                    $grid[$y][$x] = 'S'; // Mark start
                    logger("Starting location placed at Edge Room: ({$x}, {$y})");
                    return;
                }
            }
        }

        // Step 4: Fallback (Edge Case)
        logger("No valid start location found!");
    }

    /**
     * Check if a room tile is adjacent to the actual physical edge of the grid.
     */
    private function isAdjacentToPhysicalEdge($x, $y, &$grid)
    {
        $adjacentOffsets = [
            [-1, 0], [1, 0], [0, -1], [0, 1]
        ];

        foreach ($adjacentOffsets as [$dx, $dy]) {
            $adjX = $x + $dx;
            $adjY = $y + $dy;

            // Check if the adjacent tile is out of bounds
            if (!$this->isInBounds($adjX, $adjY)) {
                return true; // Adjacent to the physical edge
            }

            // Check if adjacent tile is a boundary wall ('W') at the grid edge
            if (
                ($adjX === 0 || $adjY === 0 || $adjX === $this->width - 1 || $adjY === $this->height - 1) &&
                $grid[$adjY][$adjX] === 'W'
            ) {
                return true; // Adjacent to grid wall at the physical edge
            }
        }

        return false; // Not adjacent to physical edge
    }

    /**
     * Place the boss room at the furthest room tile from the start ('S').
     */
    private function placeBossRoom(&$grid)
    {
        $start = null;
        $roomTiles = [];

        // Step 1: Locate the start tile ('S')
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($grid[$y][$x] === 'S') {
                    $start = ['x' => $x, 'y' => $y];
                }
                if ($grid[$y][$x] === 'R') {
                    $roomTiles[] = ['x' => $x, 'y' => $y];
                }
            }
        }

        // Validate start exists
        if (!$start) {
            logger("No starting point ('S') found. Cannot place boss room.");
            return;
        }

        // Step 2: Calculate distances and find the furthest room tile
        $maxDistance = -1;
        $bossRoom = null;

        foreach ($roomTiles as $room) {
            $distance = $this->calculateDistance($start['x'], $start['y'], $room['x'], $room['y']);
            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $bossRoom = $room;
            }
        }

        // Step 3: Mark the furthest room as 'B'
        if ($bossRoom) {
            $grid[$bossRoom['y']][$bossRoom['x']] = 'B'; // Mark Boss Room
            logger("Boss Room placed at ({$bossRoom['x']}, {$bossRoom['y']}) with distance: {$maxDistance}");
        } else {
            logger("No valid room found to place Boss Room.");
        }
    }

    /**
     * Calculate Euclidean distance between two points.
     */
    private function calculateDistance($x1, $y1, $x2, $y2)
    {
        return sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
    }


    private function cleanupDoors(&$grid)
    {
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($grid[$y][$x] === 'D') {
                    if (!$this->isValidDoor($grid, $x, $y)) {
                        $grid[$y][$x] = 'W'; // Replace invalid door with a wall
                    }
                }
            }
        }
    }

    private function isValidDoor(&$grid, $x, $y)
    {
        $adjacentOffsets = [
            [-1, 0], [1, 0], [0, -1], [0, 1]
        ];

        $adjacentTiles = [];
        foreach ($adjacentOffsets as [$dx, $dy]) {
            $adjX = $x + $dx;
            $adjY = $y + $dy;

            if ($this->isInBounds($adjX, $adjY)) {
                $adjacentTiles[] = $grid[$adjY][$adjX];
            } else {
                $adjacentTiles[] = null; // Out of bounds
            }
        }

        // Check valid patterns for doors
        return ($adjacentTiles[0] === 'R' && $adjacentTiles[1] === 'R') || // Top and Bottom: Room-Room
            ($adjacentTiles[2] === 'R' && $adjacentTiles[3] === 'R') || // Left and Right: Room-Room
            ($adjacentTiles[0] === 'R' && $adjacentTiles[1] === 'C') || // Top: Room, Bottom: Corridor
            ($adjacentTiles[1] === 'R' && $adjacentTiles[0] === 'C') || // Bottom: Room, Top: Corridor
            ($adjacentTiles[2] === 'R' && $adjacentTiles[3] === 'C') || // Left: Room, Right: Corridor
            ($adjacentTiles[3] === 'R' && $adjacentTiles[2] === 'C');   // Right: Room, Left: Corridor
    }


    /**
     * Place a room on the grid.
     */

    private function placeRoom(&$grid, $x, $y, $width, $height, $type = 'empty', $name = 'Room', $description = null)
    {
        // Place walls and ensure edges are properly sealed
        for ($row = $y - 1; $row <= $y + $height; $row++) {
            for ($col = $x - 1; $col <= $x + $width; $col++) {
                if (!$this->isInBounds($col, $row)) {
                    continue; // Ignore tiles fully out of bounds
                }

                // Mark outer boundary as walls
                if ($row === $y - 1 || $row === $y + $height || $col === $x - 1 || $col === $x + $width) {
                    $grid[$row][$col] = 'W';
                }
            }
        }

        // Place room interior
        for ($row = $y; $row < $y + $height; $row++) {
            for ($col = $x; $col < $x + $width; $col++) {
                if ($this->isInBounds($col, $row)) {
                    $grid[$row][$col] = 'R';
                }
            }
        }

        // Seal grid edges explicitly
        $this->sealOutOfBoundsEdges($grid, $x, $y, $width, $height);

        // Save the room to the database
        $this->saveRoomToDatabase($x, $y, $width, $height, $type, $name, $description);
    }

    private function saveRoomToDatabase($x, $y, $width, $height, $type = 'empty', $name = 'Room', $description = null)
    {
//        // If no description is provided, generate one using OpenAI
//        if (is_null($description)) {
//            $openAIService = new OpenAIService(); // Instantiate OpenAI service
//
//            // Define a prompt for OpenAI (you can customize this as needed)
//            $prompt = "Generate a detailed description of a dungeon room, it is $type.";
//
//            // Get the response from OpenAI
//            $description = $openAIService->generateChatResponse([
//                ['role' => 'user', 'content' => $prompt]
//            ],
//                50);
//        }

        // Save the room to the database
        Room::create([
            'dungeon_id' => $this->id,
            'name' => $name,
            'description' => $description,  // Store the generated description
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
            'type' => $type,
            'is_explored' => false,
        ]);
    }


    private function sealOutOfBoundsEdges(&$grid, $x, $y, $width, $height)
    {
        // Top Edge
        if ($y - 1 < 0) {
            for ($col = max($x - 1, 0); $col <= min($x + $width, $this->width - 1); $col++) {
                $grid[0][$col] = 'W';
            }
        }

        // Bottom Edge
        if ($y + $height >= $this->height) {
            for ($col = max($x - 1, 0); $col <= min($x + $width, $this->width - 1); $col++) {
                $grid[$this->height - 1][$col] = 'W';
            }
        }

        // Left Edge
        if ($x - 1 < 0) {
            for ($row = max($y - 1, 0); $row <= min($y + $height, $this->height - 1); $row++) {
                $grid[$row][0] = 'W';
            }
        }

        // Right Edge
        if ($x + $width >= $this->width) {
            for ($row = max($y - 1, 0); $row <= min($y + $height, $this->height - 1); $row++) {
                $grid[$row][$this->width - 1] = 'W';
            }
        }
    }


    /**
     * Place doors (1–4) on a room.
     */
    private function placeDoors(&$grid, $x, $y, $width, $height, $isInitialRoom = false)
    {
        $doorCount = rand(1, 4);
        $doors = [];

        $doorOptions = [
            ['x' => $x + rand(0, $width - 1), 'y' => $y - 1], // Top wall
            ['x' => $x + rand(0, $width - 1), 'y' => $y + $height], // Bottom wall
            ['x' => $x - 1, 'y' => $y + rand(0, $height - 1)], // Left wall
            ['x' => $x + $width, 'y' => $y + rand(0, $height - 1)], // Right wall
        ];

        shuffle($doorOptions);

        foreach (array_slice($doorOptions, 0, $doorCount) as $door) {
            $doorX = $door['x'];
            $doorY = $door['y'];

            if (
                $this->isInBounds($doorX, $doorY) &&
                isset($grid[$doorY][$doorX]) &&
                $grid[$doorY][$doorX] === 'W' &&
                !$this->isAdjacentToDoor($grid, $doorX, $doorY)
            ) {
                $grid[$doorY][$doorX] = 'D'; // Place door
                $doors[] = ['x' => $doorX, 'y' => $doorY];
            }
        }

        return $doors;
    }



    /**
     * Process a door connection: Room or Corridor.
     */
    private function processDoor(&$grid, $door)
    {
        $connectionType = rand(0, 1); // 0 = Room, 1 = Corridor

        // 50% chance to create either a room or a corridor
        if ($connectionType === 0) {
            $this->addRoomFromDoor($grid, $door);
        } else {
            $this->addCorridorFromDoor($grid, $door);
        }
    }



    /**
     * Add a room from a door.
     */
    private function addRoomFromDoor(&$grid, $door, $isFromCorridor = false)
    {
        $initialWidth = rand(4, 8);
        $initialHeight = rand(4, 8);
        $minRoomSize = 3;

        $roomWidth = $initialWidth;
        $roomHeight = $initialHeight;

        $x = $door['x'];
        $y = $door['y'];

        while ($roomWidth >= $minRoomSize && $roomHeight >= $minRoomSize) {
            $placementX = $x;
            $placementY = $y;

            // Adjust placement based on direction
            if ($this->isTopWall($door, $grid)) {
                $placementY -= $roomHeight;
                $placementX -= intdiv($roomWidth, 2);
            } elseif ($this->isBottomWall($door, $grid)) {
                $placementY += 1;
                $placementX -= intdiv($roomWidth, 2);
            } elseif ($this->isLeftWall($door, $grid)) {
                $placementX -= $roomWidth;
                $placementY -= intdiv($roomHeight, 2);
            } elseif ($this->isRightWall($door, $grid)) {
                $placementX += 1;
                $placementY -= intdiv($roomHeight, 2);
            }
            // Corridor-Originating Doors
            elseif ($isFromCorridor) {
                if (isset($grid[$door['y'] - 1][$door['x']]) && $grid[$door['y'] - 1][$door['x']] === 'C') {
                    $placementY += 1;
                    $placementX -= intdiv($roomWidth, 2);
                } elseif (isset($grid[$door['y'] + 1][$door['x']]) && $grid[$door['y'] + 1][$door['x']] === 'C') {
                    $placementY -= $roomHeight;
                    $placementX -= intdiv($roomWidth, 2);
                } elseif (isset($grid[$door['y']][$door['x'] - 1]) && $grid[$door['y']][$door['x'] - 1] === 'C') {
                    $placementX += 1;
                    $placementY -= intdiv($roomHeight, 2);
                } elseif (isset($grid[$door['y']][$door['x'] + 1]) && $grid[$door['y']][$door['x'] + 1] === 'C') {
                    $placementX -= $roomWidth;
                    $placementY -= intdiv($roomHeight, 2);
                }
            }

            // Validate placement
            if ($this->canPlaceRoom($grid, $placementX, $placementY, $roomWidth, $roomHeight)) {
                $roomType = $this->determineRoomType($roomWidth, $roomHeight);

                $this->placeRoom($grid, $placementX, $placementY, $roomWidth, $roomHeight, $roomType);
                $this->placeRoomWalls($grid, $placementX, $placementY, $roomWidth, $roomHeight);
                $grid[$door['y']][$door['x']] = 'D';

                // Add new doors to the room
                $newDoors = $this->placeDoors($grid, $placementX, $placementY, $roomWidth, $roomHeight);
                foreach ($newDoors as $newDoor) {
                    $this->processDoor($grid, $newDoor);
                }
            }

            // Reduce room size and retry
            $roomWidth--;
            $roomHeight--;
        }

        return false; // Room could not be placed
    }

    /**
     * Determine the type of a room based on its size.
     *
     * @param int $width
     * @param int $height
     * @return string
     */
    private function determineRoomType($width, $height)
    {
        $surfaceArea = $width * $height;

        if ($surfaceArea > 25 && rand(0, 1) === 1) {
            return 'monster'; // 50% chance for monster room if area > 25
        }

        return 'empty'; // Default to empty
    }


    private function rollbackCorridor(&$grid, $corridorPath)
    {
        // Reverse traverse the corridor path
        foreach (array_reverse($corridorPath) as $tile) {
            $x = $tile['x'];
            $y = $tile['y'];

            // Stop rollback if encountering a door connected to another room
            $adjacentOffsets = [
                [-1, 0], [1, 0], [0, -1], [0, 1]
            ];

            foreach ($adjacentOffsets as [$dx, $dy]) {
                $adjX = $x + $dx;
                $adjY = $y + $dy;

                if ($this->isInBounds($adjX, $adjY) && isset($grid[$adjY][$adjX])) {
                    if ($grid[$adjY][$adjX] === 'R') {
                        return; // Stop rollback if connected to a valid path
                    }
                }
            }

            // Remove the corridor tile
            $grid[$y][$x] = '#';
        }
    }



    private function placeRoomWalls(&$grid, $x, $y, $roomWidth, $roomHeight)
    {
        // Top and Bottom Walls
        for ($col = $x; $col < $x + $roomWidth; $col++) {
            if ($this->isInBounds($col, $y - 1)) {
                $grid[$y - 1][$col] = $grid[$y - 1][$col] ?? 'W'; // Top Wall
            }
            if ($this->isInBounds($col, $y + $roomHeight)) {
                $grid[$y + $roomHeight][$col] = $grid[$y + $roomHeight][$col] ?? 'W'; // Bottom Wall
            }
        }

        // Left and Right Walls
        for ($row = $y; $row < $y + $roomHeight; $row++) {
            if ($this->isInBounds($x - 1, $row)) {
                $grid[$row][$x - 1] = $grid[$row][$x - 1] ?? 'W'; // Left Wall
            }
            if ($this->isInBounds($x + $roomWidth, $row)) {
                $grid[$row][$x + $roomWidth] = $grid[$row][$x + $roomWidth] ?? 'W'; // Right Wall
            }
        }
    }





    /**
     * Validate if a room can be placed at the given coordinates.
     */
    private function canPlaceRoom(&$grid, $x, $y, $width, $height)
    {
        for ($row = $y; $row < $y + $height; $row++) {
            for ($col = $x; $col < $x + $width; $col++) {
                if (
                    $row < 0 || $row >= $this->height ||
                    $col < 0 || $col >= $this->width ||
                    isset($grid[$row][$col]) && $grid[$row][$col] !== '#'
                ) {
                    return false; // Space is not valid
                }
            }
        }
        return true;
    }




    /**
     * Add a corridor from a door.
     */
    private function addCorridorFromDoor(&$grid, $door)
    {
        $length = rand(3, 6); // Limit corridor length
        $direction = $this->getCorridorDirection($door, $grid);

        if (!$direction) {
            return; // Skip if no valid direction is detected
        }

        $corridorPath = []; // Track corridor tiles

        for ($i = 0; $i < $length; $i++) {
            switch ($direction) {
                case 'up': $door['y']--; break;
                case 'down': $door['y']++; break;
                case 'left': $door['x']--; break;
                case 'right': $door['x']++; break;
            }

            // Validate grid boundaries
            if (
                !$this->isInBounds($door['x'], $door['y']) ||
                isset($grid[$door['y']][$door['x']]) && $grid[$door['y']][$door['x']] !== '#'
            ) {
                break; // Stop if out of bounds or tile is occupied
            }

            $grid[$door['y']][$door['x']] = 'C'; // Mark as corridor
            $corridorPath[] = ['x' => $door['x'], 'y' => $door['y']];
        }

        // Place a door at the end of the corridor
        $endX = $door['x'];
        $endY = $door['y'];

        if (
            $this->isInBounds($endX, $endY) &&
            isset($grid[$endY][$endX]) &&
            $grid[$endY][$endX] === 'C'
        ) {
            $grid[$endY][$endX] = 'D'; // End corridor with a door

            // Attempt to add a room from the door
            if (!$this->addRoomFromDoor($grid, ['x' => $endX, 'y' => $endY], true)) {
                // Rollback the corridor if no room could be placed
                $this->rollbackCorridor($grid, $corridorPath);
            }
        }
    }


    private function isAdjacentToDoor(&$grid, $x, $y)
    {
        $adjacentOffsets = [
            [-1, 0], [1, 0], [0, -1], [0, 1]
        ];

        foreach ($adjacentOffsets as [$dx, $dy]) {
            $adjX = $x + $dx;
            $adjY = $y + $dy;

            if (
                $this->isInBounds($adjX, $adjY) &&
                isset($grid[$adjY][$adjX]) &&
                $grid[$adjY][$adjX] === 'D'
            ) {
                return true; // Adjacent to a door
            }
        }

        return false; // No adjacent doors found
    }



    private function isInBounds($x, $y)
    {
        return $x >= 0 && $x < $this->width && $y >= 0 && $y < $this->height;
    }



    /**
     * Mark all corridor ('C') tiles on the edges of the grid as exits ('E').
     */
    private function markExits(&$grid)
    {
        // Top and Bottom Edges
        for ($x = 0; $x < $this->width; $x++) {
            if ($grid[0][$x] === 'C') {
                $grid[0][$x] = 'E'; // Top edge
            }
            if ($grid[$this->height - 1][$x] === 'C') {
                $grid[$this->height - 1][$x] = 'E'; // Bottom edge
            }
        }

        // Left and Right Edges
        for ($y = 0; $y < $this->height; $y++) {
            if ($grid[$y][0] === 'C') {
                $grid[$y][0] = 'E'; // Left edge
            }
            if ($grid[$y][$this->width - 1] === 'C') {
                $grid[$y][$this->width - 1] = 'E'; // Right edge
            }
        }
    }




    private function getCorridorDirection($door, $grid)
    {
        // Check door direction based on adjacent tiles
        if ($this->isTopWall($door, $grid)) {
            return 'up';
        } elseif ($this->isBottomWall($door, $grid)) {
            return 'down';
        } elseif ($this->isLeftWall($door, $grid)) {
            return 'left';
        } elseif ($this->isRightWall($door, $grid)) {
            return 'right';
        }

        return null; // Default if no valid direction is found
    }


    private function isTopWall($door, $grid)
    {
        return isset($grid[$door['y'] + 1][$door['x']]) && $grid[$door['y'] + 1][$door['x']] === 'R';
    }

    private function isBottomWall($door, $grid)
    {
        return isset($grid[$door['y'] - 1][$door['x']]) && $grid[$door['y'] - 1][$door['x']] === 'R';
    }

    private function isLeftWall($door, $grid)
    {
        return isset($grid[$door['y']][$door['x'] + 1]) && $grid[$door['y']][$door['x'] + 1] === 'R';
    }

    private function isRightWall($door, $grid)
    {
        return isset($grid[$door['y']][$door['x'] - 1]) && $grid[$door['y']][$door['x'] - 1] === 'R';
    }




}

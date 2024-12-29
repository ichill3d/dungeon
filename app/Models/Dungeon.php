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
        $edgeRoomTiles = [];

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
        // Loop over the grid and check the proximity to the edge for rooms
        $minDistance = PHP_INT_MAX; // Start with an infinitely large distance
        $bestRoom = null;

        for ($y = 1; $y < $this->height - 1; $y++) {
            for ($x = 1; $x < $this->width - 1; $x++) {
                if ($grid[$y][$x] === 'R') {
                    // Check if it's adjacent to the edge
                    if ($this->isAdjacentToPhysicalEdge($x, $y, $grid)) {
                        // Calculate Manhattan distance to the nearest edge
                        $distance = min($x, $this->width - $x - 1, $y, $this->height - $y - 1);
                        if ($distance < $minDistance) {
                            $minDistance = $distance;
                            $bestRoom = ['x' => $x, 'y' => $y];
                        }
                    }
                }
            }
        }

        // Step 4: Place start at the best room found
        if ($bestRoom !== null) {
            $grid[$bestRoom['y']][$bestRoom['x']] = 'S'; // Mark start
            logger("Starting location placed at Edge Room: ({$bestRoom['x']}, {$bestRoom['y']})");
            return;
        }

        // Step 5: Fallback (Edge Case)
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
                    logger("Door recoginized at ({$x}, {$y}).");

                    if (!$this->isValidDoor($grid, $x, $y)) {
                        logger("Door invalidated at ({$x}, {$y}).");
                        //$grid[$y][$x] = $this->replaceDoorWith($grid, $x, $y); // Replace invalid door with a wall
                        $grid[$y][$x] = "W"; // Replace invalid door with a wall
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
                logger("Door placed at ({$doorX}, {$doorY})");
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
            logger("Door connected to a room.");
            $this->addRoomFromDoor($grid, $door);
        } else {
            logger("Door connected to a corridor.");
            $this->addCorridorFromDoor($grid, $door);
        }
    }



    /**
     * Add a room from a door.
     */
    private function addRoomFromDoor(&$grid, $door, $isFromCorridor = false)
    {
        $initialWidth = rand(4, 8); // Initial random room width
        $initialHeight = rand(4, 8); // Initial random room height
        $minRoomSize = 3; // Minimum room size

        $roomWidth = $initialWidth;
        $roomHeight = $initialHeight;

        $x = $door['x'];
        $y = $door['y'];

        // Try creating room until a valid one is found or we reduce the room size below the minimum
        while ($roomWidth >= $minRoomSize && $roomHeight >= $minRoomSize) {
            $placementX = $x;
            $placementY = $y;

            // Adjust placement based on door direction
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
            // If it's from a corridor, adjust placement accordingly
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

            // Validate placement to ensure no overlap with existing rooms or corridors
            if ($this->canPlaceRoom($grid, $placementX, $placementY, $roomWidth, $roomHeight)) {
                $roomType = $this->determineRoomType($roomWidth, $roomHeight);

                // Place the room if it's valid
                $this->placeRoom($grid, $placementX, $placementY, $roomWidth, $roomHeight, $roomType);
                $this->placeRoomWalls($grid, $placementX, $placementY, $roomWidth, $roomHeight);
                $grid[$door['y']][$door['x']] = 'D'; // Set the door to be a valid door
                logger("Room placed at ({$placementX}, {$placementY})");

                // Add new doors to the room
                $newDoors = $this->placeDoors($grid, $placementX, $placementY, $roomWidth, $roomHeight);
                foreach ($newDoors as $newDoor) {
                    $this->processDoor($grid, $newDoor);
                }

                return true; // Room placed successfully
            }

            // Reduce room size and retry if placement failed
            $roomWidth--;
            $roomHeight--;
        }

        // If the room could not be placed, return false
        logger("Failed to place room after reducing size.");
        return false;
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
    private function addCorridorFromDoor(&$grid, $door)
    {
        $maxLength = rand(5, 10); // Maximum corridor length
        $turnChance = 0.2; // 20% chance to turn after each step
        $maxAttempts = 50; // Retry up to 50 times if needed

        $attempts = 0;
        $exitCorridor = false;

        // Attempt to place the corridor
        while ($attempts < $maxAttempts) {
            // Create an empty corridor path
            $corridorPath = [];

            // Start the path at the tile next to the door
            $currentX = $door['x'];
            $currentY = $door['y'];

            // Offset to the next tile based on the door's direction
            $directions = ['up', 'down', 'left', 'right'];
            $direction = $this->getCorridorDirection($door, $grid); // Get the corridor direction
            switch ($direction) {
                case 'up': $currentY--; break;
                case 'down': $currentY++; break;
                case 'left': $currentX--; break;
                case 'right': $currentX++; break;
            }

            // Check if the first tile is legal (not a wall, room, or out of bounds)
            if (!$this->isInBounds($currentX, $currentY) ||
                $grid[$currentY][$currentX] === 'W' ||
                $grid[$currentY][$currentX] === 'R' // Prevent overlap with rooms
            ) {
                // If the first tile is not valid, stop the corridor creation and retry
                $attempts++;
                continue;
            }

            // Add the first tile to the corridor path
            $corridorPath[] = ['x' => $currentX, 'y' => $currentY];

            // Continue generating the corridor
            for ($i = 0; $i < $maxLength; $i++) {
                // Move in the current direction
                switch ($direction) {
                    case 'up': $currentY--; break;
                    case 'down': $currentY++; break;
                    case 'left': $currentX--; break;
                    case 'right': $currentX++; break;
                }

                // Check if the move is out of bounds
                if (!$this->isInBounds($currentX, $currentY)) {
                    // Place exit if we go beyond the grid
                    $exitCorridor = true;
                    break;
                }

                // Check if the move is valid (within bounds and not overlapping walls, rooms, or existing corridors)
                if (
                    $grid[$currentY][$currentX] === 'W' ||  // Wall check
                    $grid[$currentY][$currentX] === 'R'     // Room check
                ) {
                    break; // Stop if out of bounds, tile is occupied, or the corridor meets another
                }

                if ($grid[$currentY][$currentX] === 'C' || $grid[$currentY][$currentX] === 'D') {
                    break; // Stop if the corridor is blocked by another corridor or door
                }

                // Add the new tile to the corridor path
                $corridorPath[] = ['x' => $currentX, 'y' => $currentY];

                // Occasionally change direction randomly (20% chance)
                if (rand(0, 100) / 100 < $turnChance) {
                    // Randomly pick a new direction (excluding the opposite direction)
                    $newDirections = array_diff($directions, [$direction]);
                    $direction = $newDirections[array_rand($newDirections)];
                }
            }

            // Check if a room can be placed at the end of the corridor path
            $potentialEndX = $currentX;
            $potentialEndY = $currentY;

            // Check if a room can be placed at the theoretical end of the corridor path
            if (!$this->canPlaceRoom($grid, $potentialEndX, $potentialEndY, 3, 3)) { // Adjust room size check as needed
                // If no room can be placed, retry by continuing the loop
                $attempts++;
                continue;
            }

            // Fill the corridor path with "C" (corridor tiles)
            foreach ($corridorPath as $tile) {
                $x = $tile['x'];
                $y = $tile['y'];
                $grid[$y][$x] = 'C'; // Mark corridor tile
            }

            // After the corridor, place a door at the end or continue a room
            if ($this->isInBounds($currentX, $currentY) && $grid[$currentY][$currentX] === 'C') {
                $grid[$currentY][$currentX] = 'D'; // End corridor with a door
                if(!$this->addRoomFromDoor($grid, ['x' => $currentX, 'y' => $currentY], true)) {
                    $this->rollbackCorridor($grid, $corridorPath);
                }
                logger("Corridor Placed, End Door placed at ({$currentX}, {$currentY})");
            }

            // The room has already been added at the end, so no need to add again
            logger("Room placed successfully at the end of the corridor");

            return; // Successfully placed the corridor and room, exit the function
        }

        // If the loop reaches the max attempts, log that corridor placement failed
        logger("Failed to place a corridor after {$maxAttempts} attempts.");
    }



    private function rollbackCorridor(&$grid, $corridorPath)
    {
        // Reverse traverse the corridor path
        foreach ($corridorPath as $tile) {
            $x = $tile['x'];
            $y = $tile['y'];

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

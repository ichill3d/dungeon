<?php

if (!function_exists('getNextDieType')) {
    /**
     * Get the next bigger die type.
     *
     * @param mixed $current
     * @return string
     */
    function getNextDieType($current): string {
        // Validate that the input is an integer
        if (!is_numeric($current)) {
            return $current;
        }

        $dieTypes = [4, 6, 8, 10, 12]; // Available die types

        foreach ($dieTypes as $type) {
            if ($type > $current) {
                return (string)$type; // Return the next bigger die type
            }
        }

        // If current is bigger than the largest die type (12)
        if ($current > 12) {
            return '12+' . ($current - 12); // Show "12+" and the difference
        }

        return '12'; // Default to 12 if not found
    }

    function isSingleMonster($input) {
        // Convert object to array if necessary
        if (is_object($input)) {
            $input = (array) $input;
        }

        // Ensure $input is an array
        if (!is_array($input)) {
            throw new InvalidArgumentException('Input must be an array or object.');
        }

        // Check if the array is numerically indexed (multiple entries)
        return array_keys($input) !== range(0, count($input) - 1);
    }
}

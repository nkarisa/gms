<?php

if (!function_exists('renameObjectProperty')) {
    /**
     * Renames a specific property within every object in an array of objects.
     *
     * @param array $objectArray The array containing objects (e.g., stdClass).
     * @param string $oldKey The property name to be replaced (e.g., 'id').
     * @param string $newKey The new property name to use (e.g., 'fk_earning_category_id').
     * @return array The array with objects having the updated property names.
     */
    function renameObjectProperty(array $objectArray, string $oldKey, string $newKey): array
    {
        $updatedArray = [];

        foreach ($objectArray as $item) {
            // Ensure the item is an object and the property exists before attempting to access/modify
            if (is_object($item) && property_exists($item, $oldKey)) {

                // 1. Get the value of the old property
                $value = $item->$oldKey;

                // 2. Add the value back with the new property name
                $item->$newKey = $value;

                // 3. Remove the old property
                unset($item->$oldKey);
            }

            // Add the modified object to the results
            $updatedArray[] = $item;
        }

        return $updatedArray;
    }

}

if(!function_exists('renameArrayKey')){
     /**
     * Renames a specific key within every sub-array of a nested array.
     * * @param array $nestedArray The array containing sub-arrays.
     * @param string $oldKey The key name to be replaced (e.g., 'id').
     * @param string $newKey The new key name to use (e.g., 'fk_earning_category_id').
     * @return array The array with the updated key names.
     */
    function renameArrayKey(array $nestedArray, string $oldKey, string $newKey): array
    {
        $updatedArray = [];

        foreach ($nestedArray as $item) {
            // Check if the old key exists in the current sub-array
            if (array_key_exists($oldKey, $item)) {
                // 1. Get the value of the old key
                $value = $item[$oldKey];

                // 2. Remove the old key
                unset($item[$oldKey]);

                // 3. Add the value back with the new key name
                // Note: This adds the new key to the end of the sub-array.
                $item[$newKey] = $value;
            }

            // Add the modified sub-array to the results
            $updatedArray[] = $item;
        }

        return $updatedArray;
    }
}

if(!function_exists('checkDateRangesOverlap')){
    function checkDateRangesOverlap(DateTime $start1, DateTime $end1, DateTime $start2, DateTime $end2): bool {
    // Range 1: [start1, end1]
    // Range 2: [start2, end2]

    // Check if the start of range 1 is before the end of range 2
    // AND the start of range 2 is before the end of range 1

    return $start1 < $end2 && $start2 < $end1;
}
}

if(!function_exists('isValidDate')){
    function isValidDate(string $dateString, string $format = 'Y-m-d'): bool
    {
        // Attempt to create a DateTime object from the string using the specified format.
        $dateTime = \DateTime::createFromFormat($format, $dateString);

        // 1. Check if the object creation succeeded (it won't return false for '0000-00-00'
        //    if the format is 'Y-m-d', so we need the second check).
        // 2. Check if the format was strictly adhered to by reformatting the object
        //    and comparing it to the original string. This catches dates like '2024-15-40'.
        return $dateTime && $dateTime->format($format) === $dateString;
    }
}
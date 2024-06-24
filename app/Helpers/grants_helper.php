<?php

define('DS', DIRECTORY_SEPARATOR);

if (!function_exists('get_phrase')) {
    function get_phrase($phrase, $translation = '')
    {
        helper('inflector');
        // $translation = ucwords(str_replace("_", " ", $phrase));
        $translation =  humanize($phrase, '_');

        return $translation;
    }
}


/**
 * Function to create a comprehensive array of database specifications.
 * The function reads a base schema from a JSON file and merges it with
 * any additional specifications found in separate extension files.
 *
 * @return array The merged array of database specifications.
 */
if (!function_exists('create_specs_array')) {
    function create_specs_array()
    {
        // Define the path to the base schema file
        $schemaPath = APPPATH . 'DBManifest' . DIRECTORY_SEPARATOR . 'manifest.json';

        // Read the base schema from the file
        $schema = file_get_contents($schemaPath);

        // Decode the JSON schema into an associative array
        $specs_array = json_decode($schema, true);

        // Define the path to the directory containing extension files
        $path = APPPATH . 'version' . DIRECTORY_SEPARATOR . 'extend';

        // Create the directory if it doesn't exist
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        // Get a list of files in the directory
        $files = scandir($path);

        // Iterate through each file
        foreach ($files as $file) {
            // Skip over directories and hidden files
            if (is_dir($path . DIRECTORY_SEPARATOR . $file) || substr($file, 0, 1) === '.') {
                continue;
            }

            // Read the contents of the extension file
            $contents = file_get_contents($path . DIRECTORY_SEPARATOR . $file);

            // Decode the JSON contents into an associative array
            $ext_specs_array = json_decode($contents, true);

            // Skip if the extension file is empty
            if (empty($ext_specs_array)) {
                continue;
            }

            // Iterate through each application in the extension file
            foreach ($ext_specs_array as $app_name => $tables) {
                // Skip if the 'tables' key is not set
                if (isset($tables['tables'])) {
                    // Iterate through each table in the application
                    foreach ($tables['tables'] as $table_name => $table_props) {
                        // Skip if the table name is not a string or the table properties are not an array or null
                        if (is_string($table_name) && (is_array($table_props) || is_null($table_props))) {
                            // Skip if the table properties do not contain 'field_data' or 'lookup_tables' keys or if 'field_data' is empty
                            if (
                                is_array($table_props) &&
                                (!isset($table_props['field_data']) && !isset($table_props['lookup_tables']) || empty($table_props['field_data']))
                            ) {
                                continue;
                            }

                            // Merge the table properties into the main specs array
                            $specs_array[$app_name]['tables'][$table_name] = $table_props;
                        }
                    }
                }
            }
        }

        // Return the merged array of database specifications
        return $specs_array;
    }
}

/**
 * Function to encode or decode an ID using Hashids library.
 *
 * @param int $id The ID to be encoded or decoded.
 * @param string $action The action to perform. 'encode' for encoding, 'decode' for decoding. Default is 'encode'.
 * @return mixed The encoded or decoded ID. If decoding fails, returns null.
 *
 * @throws Exception If Hashids library is not installed or cannot be loaded.
 */
if (!function_exists('hash_id')) {
    function hash_id(int|string $id, string $action = 'encode'): null|string
    {
        // Initialize Hashids with a secret key and length
        $hashids = new Hashids\Hashids('#Compassion321', 10);

        // Perform the specified action
        if ($action == 'encode') {
            // Encode the ID
            return $hashids->encode($id);
        } elseif (isset($hashids->decode($id)[0])) {
            // Decode the ID and return the first element
            // print_r($hashids->decode($id));exit(); // Uncomment for debugging
            return $hashids->decode($id)[0];
        } else {
            // Return null if decoding fails
            return null;
        }
    }
}


/**
 * Function to elevate an array element to a key.
 * This function takes an array and an element to elevate.
 * It creates a new array where the element to elevate becomes the key,
 * and the original array item becomes the value.
 *
 * @param array $unelevated_array The original array.
 * @param string $element_to_elevate The element to elevate to the key.
 * @return array The elevated array.
 *
 * @throws Exception If the input array is not an array.
 * @throws Exception If the element to elevate is not a string.
 */
if (!function_exists('elevate_array_element_to_key')) {
    function elevateArrayElementToKey($unelevavated_array, $element_to_elevate)
    {
        // Check if the input array is an array
        if (!is_array($unelevavated_array)) {
            throw new Exception('Invalid array');
        }

        // Check if the element to elevate is a string
        if (!is_string($element_to_elevate)) {
            throw new Exception('Element to elevate must be a string');
        }

        $elevated_array = array();
        foreach ($unelevavated_array as $item) {

            // Cast $item to array if object
            $item = is_object($item) ? (array) $item : $item;

            // Check if the element to elevate exists in the item
            if (!array_key_exists($element_to_elevate, $item)) {
                throw new Exception('Element to elevate does not exist in the array item');
            }

            $elevated_array[$item[$element_to_elevate]] = $item;

            // Remove the element to elevate from the value
            unset($elevated_array[$item[$element_to_elevate]][$element_to_elevate]);
        }

        return $elevated_array;
    }
}

/**
 * Function to elevate an associative array element to a key.
 * This function takes an associative array and an element to elevate.
 * It creates a new array where the element to elevate becomes the key,
 * and the original array item becomes the value.
 *
 * @param array $unelevated_array The original associative array.
 * @param string $element_to_elevate The element to elevate to the key.
 * @return array The elevated associative array.
 *
 * @throws Exception If the input array is not an associative array.
 * @throws Exception If the element to elevate is not a string.
 */
if (!function_exists('elevate_assoc_array_element_to_key')) {
    function elevateAssocArrayElementToKey($unevelavated_array, $element_to_elevate)
    {
        // Check if the input array is an associative array
        // if (!is_array($unevelavated_array) || array_values($unevelavated_array) === $unevelavated_array) {
        //     throw new Exception('Invalid associative array');
        // }

        // Check if the element to elevate is a string
        // if (!is_string($element_to_elevate)) {
        //     throw new Exception('Element to elevate must be a string');
        // }

        $elevated_array = array();
        $cnt = 0;
        foreach ($unevelavated_array as $item) {

            // Cast $item to array if object
            $item = is_object($item) ? (array) $item : $item;

            // Check if the element to elevate exists in the item
            if (!array_key_exists($element_to_elevate, $item)) {
                throw new Exception('Element to elevate does not exist in the array item');
            }

            $elevated_array[$item[$element_to_elevate]][$cnt] = $item;

            // Remove the element to elevate from the value
            unset($elevated_array[$item[$element_to_elevate]][$cnt][$element_to_elevate]);
            $cnt++;
        }

        return $elevated_array;
    }
}

/**
 * Function to generate a record prefix from a given string.
 *
 * @param string $string The input string from which to generate the record prefix.
 * @return string The generated record prefix.
 *
 * @throws Exception If the input string is not a valid string.
 */
if (!function_exists('record_prefix')) {
    function record_prefix($string)
    {
        // Check if the input string is a valid string
        if (!is_string($string)) {
            throw new Exception('Invalid string');
        }

        // Extract the first two characters of the string
        $lead_string = substr($string, 0, 2);

        // Extract the last two characters of the string
        $trail_string = substr($string, -2, 2);

        // Combine the lead and trail strings, convert to uppercase, and return
        return strtoupper($lead_string . $trail_string);
    }
}

/**
 * Generates a track number and name for an approveable item.
 *
 * @param string $approveable_item The name of the approveable item.
 * @return array An associative array containing the track number and name.
 *
 * @throws Exception If the input string is not a valid snake_case string.
 */
if (!function_exists('generate_item_track_number_and_name')) {
    function generate_item_track_number_and_name($approveable_item)
    {
        // Check if the input string is a valid snake_case string
        // if (!preg_match('/^[a-z0-9_]+$/i', $approveable_item)) {
        //     throw new Exception('Invalid snake_case string');
        // }

        // Generate a random number between 1000 and 90000
        $header_random = rand(1000, 90000);

        // Create an associative array with the track number and name
        $columns[$approveable_item . '_track_number'] = record_prefix($approveable_item) . '-' . $header_random;
        $columns[$approveable_item . '_name'] = ucfirst($approveable_item) . ' # ' . $header_random;

        // Return the associative array
        return $columns;
    }
}


if (!function_exists('isEmpty')) {
    function isEmpty($input)
    {
        $strTemp = $input;
        $strTemp = trim($strTemp);

        if ($strTemp == '') 
        {
            return true;
        }

        return false;
    }
}

/**
 * Explodes a string into an array based on a given separator and assigns keys to the resulting array.
 *
 * @param string $separator The separator to use for exploding the string.
 * @param string $string The string to explode.
 * @param array $vars An optional array of keys to assign to the exploded parts.
 * @param bool $reverse_assignment An optional flag to reverse the assignment of keys and exploded parts.
 * @return array The resulting array with keys assigned to the exploded parts.
 */
if(!function_exists('exploding')){
    function exploding($separator, $string, $vars = [], $reverse_assignment = true) {
        
        $explode = explode($separator, $string);

        $keyedExplode = $explode;
        if(!empty($vars)){
            
            if($reverse_assignment){
                $explode = array_reverse($explode);
                $vars = array_reverse($vars);
            }

            $keyedExplode = [];
            for($i = 0; $i < count($vars); $i++){
                if(isset($explode[$i])){
                    $keyedExplode[$vars[$i]] = $explode[$i];
                }
            }
        }

        return $keyedExplode;
    }
}

if(!function_exists('clear_cache_files')){
    function clear_cache_files($table){

        $cache_dirs_actions = ['view','list','edit','single_form_add','multi_form_add','show_list'];

        foreach($cache_dirs_actions as $action){
          $dir = APPPATH.'cache'.DS.$table.'+'.$action;
          
          if(!file_exists($dir)) continue;

          remove_directory($dir);
          
        }
    }
}

if(!function_exists('remove_directory')){
    function remove_directory($dir){
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
                    RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
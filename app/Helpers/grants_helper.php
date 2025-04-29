<?php

define('DS', DIRECTORY_SEPARATOR);

if (!function_exists('get_phrase')) {
    function get_phrase($phrase, $translated_string = '', $phrase_variables_values = [])
    {
        helper('inflector');

        $accountSystem = session()->has('user_account_system_code') ? ucfirst(session()->get('user_account_system_code')) : 'Global';
        $translation = lang("$accountSystem/App.$phrase", $phrase_variables_values);

        if (count(explode("/", $translation)) > 1) {
            if ($translated_string == '') {
                $translation = ucwords(str_replace("_", " ", $phrase));
            } else {
                $translation = $translated_string;
            }
        }

        if(!empty($phrase_variables_values)){
			foreach ($phrase_variables_values as $placeholder => $replacement) {
				$placeholder = '{{' . $placeholder . '}}';
				$translation = str_replace($placeholder, $replacement, $translation);
			}
		}

        return $translation;
    }
}

 //Formerly as budget_review_buffer_month
 if (! function_exists('month_after_adding_size_of_budget_review_period')) {
    function month_after_adding_size_of_budget_review_period($current_month)
    {

        $current_month_with_buffer = $current_month + service("settings")->get("GrantsConfig.size_in_months_of_a_budget_review_period");

        if ($current_month_with_buffer > 12) {

            if ($current_month_with_buffer > 24) {
                $current_month_with_buffer = $current_month_with_buffer % 12;
            } else {
                $current_month_with_buffer = $current_month_with_buffer - 12;
            }
        }

        return $current_month_with_buffer;
    }
}

if (!function_exists('add_record_button')) {
    function add_record_button($table_controller, $parent_controller, $id = null, $has_listing = false, $is_multi_row = false)
    {
        $add_view = $has_listing ? "multiFormAdd" : "singleFormAdd";
        $add_view = $is_multi_row ? "multiRowAdd" : $add_view;
        $link = "";
        if ($id != null) {
            $link = '<a href="' . base_url() . strtolower($table_controller) . '/' . $add_view . '/' . $id . '/' . $parent_controller . '" class="btn btn-default hidden-print ">' . get_phrase('add_' . strtolower($table_controller)) . '</a>';
        } else {
            $link = '<a style="margin-bottom:-70px;z-index:100;position:relative;" href="' . base_url() . $table_controller . '/' . $add_view . '" class="btn btn-default hidden-print ">' . get_phrase('add_' . strtolower($table_controller)) . '</a>';
        }

        $grantsLibrary = service('grantslib');
        $featureLibrary = $grantsLibrary->loadLibrary($table_controller);

        if(method_exists($featureLibrary,'showAddButton')){
            $showAddButton = $featureLibrary->showAddButton();

            if(!$showAddButton){
                $link = "";
            }
        }

        return $link;
    }
}

if (!function_exists('record_prefix')) {
    function record_prefix($string)
    {
        $lead_string = substr($string, 0, 2);
        $trail_string = substr($string, -2, 2);

        return strtoupper($lead_string . $trail_string);
    }
}

if (!function_exists('list_table_delete_action')) {
    function list_table_delete_action($table_controller, $primary_key)
    {

        $string = '<a class="list_delete_link" href="' . base_url() . ucfirst($table_controller) . '/delete/' . hash_id($primary_key) . '">' . get_phrase("delete") . '</a>';

        return $string;
    }
}

if (!function_exists('list_table_edit_action')) {
    function list_table_edit_action($table_controller, $primary_key, $status_id = 0)
    {

        $string = '<a class="list_edit_link" href="' . base_url() . $table_controller . '/edit/' . hash_id($primary_key, 'encode') . '">' . get_phrase("edit") . '</a>';

        return $string;
    }
}

if (!function_exists('camel_case_header_element')) {
    function camel_case_header_element($header_element)
    {
        return get_phrase($header_element); //ucwords(str_replace('_',' ',$header_element));
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
        // set_time_limit(300);
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

        if ($strTemp == '') {
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
if (!function_exists('exploding')) {
    function exploding($separator, $string, $vars = [], $reverse_assignment = true)
    {

        $explode = explode($separator, $string);

        $keyedExplode = $explode;
        if (!empty($vars)) {

            if ($reverse_assignment) {
                $explode = array_reverse($explode);
                $vars = array_reverse($vars);
            }

            $keyedExplode = [];
            for ($i = 0; $i < count($vars); $i++) {
                if (isset($explode[$i])) {
                    $keyedExplode[$vars[$i]] = $explode[$i];
                }
            }
        }

        return $keyedExplode;
    }
}

if (!function_exists('clear_cache_files')) {
    function clear_cache_files($table)
    {

        $cache_dirs_actions = ['view', 'list', 'edit', 'single_form_add', 'multi_form_add', 'show_list'];

        foreach ($cache_dirs_actions as $action) {
            $dir = APPPATH . 'cache' . DS . $table . '+' . $action;

            if (!file_exists($dir))
                continue;

            remove_directory($dir);

        }
    }
}

if (!function_exists('remove_directory')) {
    function remove_directory($dir)
    {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator(
            $it,
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}

if (!function_exists('render_list_table_header')) {
    function render_list_table_header($header_array)
    {
        $string = '';

        foreach ($header_array as $th_value) {
            if (strpos($th_value, 'key') == true || substr($th_value, -2) === "id") {
                continue;
            }

            $string .= '<th nowrap="nowrap">' . get_phrase($th_value) . '</th>';
        }
        $string .= '</tr>';

        return $string;
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow)); 

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}


if (!function_exists('currency_conversion')) {
    function currency_conversion($office_id, $conversion_month = '2020-05-01')
    {

        // Database connection for codeigniter 4
        $db = \Config\Database::connect();
    
        $builder = $db->table('office');
        $builder->where('office_id',$office_id);
        $office_currency_id = $builder->get()->getRow()->fk_country_currency_id;

        $user_currency_id = service('session')->get('user_currency_id');

        $base_currency_id = service('session')->get('base_currency_id');

        $builder = $db->table('currency_conversion_detail');
        $builder->join('currency_conversion', 'currency_conversion.currency_conversion_id=currency_conversion_detail.fk_currency_conversion_id');
        $office_rate_obj = $builder->where('fk_country_currency_id', $office_currency_id)->get();

        $office_rate = 1;

        if ($office_rate_obj->getNumRows() > 0) {
            $office_rate = $office_rate_obj->getRow()->currency_conversion_detail_rate;
        }


        $builder = $db->table('currency_conversion_detail');
        $builder->join('currency_conversion', 'currency_conversion.currency_conversion_id=currency_conversion_detail.fk_currency_conversion_id');
        $user_rate_obj = $builder->where('fk_country_currency_id', $user_currency_id)->get();

        $user_rate = 1;

        if ($user_rate_obj->getNumRows() > 0) {
            $user_rate = $user_rate_obj->getRow()->currency_conversion_detail_rate;
        }

        $computed_rate = 1;

        if ($user_currency_id !== $base_currency_id) {
            $computed_rate = $user_rate / $office_rate;
        } else {
            $computed_rate = 1 / $office_rate;
        }

        return $computed_rate; // .' - '. $user_rate . ' - '.$office_rate;
    }
}

if(!function_exists('approval_action_button')){
    function approval_action_button(
        $table_name, 
        $item_status, 
        $item_id, 
        $status_id, 
        $item_initial_item_status_id, 
        $item_max_approval_status_ids, 
        $disable_btn=false, 
        $confirmation_required = true, 
        $custom_status_name = '', 
        $voided_chq=false, 
        $missing_voucher_detail_flag=false
        ): string{
            
    $disable_class='';
    $statusLibrary = new \App\Libraries\Core\StatusLibrary();

    if($disable_btn){
      $disable_class='disabled';
    }
    
    $buttons = "<div class = 'btn btn-info disabled'>".get_phrase('exempted_status')."</div>"; 
    if(!service('session')->system_admin && !isset($item_status[$status_id])){
        $return_item = $statusLibrary->returnToPreviousPositiveStatus($table_name, $item_id, $item_initial_item_status_id);
        // in_array($status_id, $item_max_approval_status_ids) or
        if(!$return_item){
            return $buttons;
        }else{
            $status_id = $item_initial_item_status_id;
        }
    }

    $role_ids = service('session')->role_ids; 
    $status = 0;
    $status_button_label = '';
    $status_decline_button_label = '';
    $status_name = $custom_status_name;
    // $status_approval_sequence = 0;
    $status_approval_direction = 0;

    $buttons = '';
    $role_ids = service('session')->role_ids;	
    $status = $item_status[$status_id];
    $status_button_label = $item_status[$status_id]['status_button_label'] != '' ? $item_status[$status_id]['status_button_label'] : $item_status[$status_id]['status_name'];
    $status_decline_button_label = $item_status[$status_id]['status_decline_button_label'] != "" ? $item_status[$status_id]['status_decline_button_label']: get_phrase('return');
    $status_name = $item_status[$status_id]['status_name'];
    // $status_approval_sequence =  $item_status[$status_id]['status_approval_sequence'];
    $status_approval_direction = $item_status[$status_id]['status_approval_direction'];

    if(isset($item_status[$status_id])){
        $status = $item_status[$status_id];
        $status_button_label = $item_status[$status_id]['status_button_label'] != '' ? $item_status[$status_id]['status_button_label'] : $item_status[$status_id]['status_name'];
        $status_decline_button_label = $item_status[$status_id]['status_decline_button_label'] != "" ? $item_status[$status_id]['status_decline_button_label']: get_phrase('decline');
        $status_name = $item_status[$status_id]['status_name'];
        // $status_approval_sequence =  $item_status[$status_id]['status_approval_sequence'];
        $status_approval_direction = $item_status[$status_id]['status_approval_direction'];
    }
    
    $approve_next_status = 0;
    $decline_next_status = 0;

    $next_status = approval_next_status($table_name, $item_status, $item_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids);
    extract($next_status);

    $match_roles = isset($status['status_role']) ? array_intersect($status['status_role'],$role_ids) : [];
    //print_r($match_roles);
    $info_color = 'info';

    if(in_array($status_id,$item_max_approval_status_ids)){
        $info_color = "primary";
    }

    if(sizeof($match_roles) > 0){
        // Show action button with button label
        if(!in_array($status_id,$item_max_approval_status_ids)){
            $color = 'success';

            if($status_approval_direction == -1){
                $color = 'danger';
            }

            if($voided_chq==true){
                $color='warning';
            }
            //echo $status_button_label;
            //Check if voucher is missing voucher details
            
            if($missing_voucher_detail_flag==true){
                $voucher_detail_value=1;

                $voucher_detail_value="data-voucher-missing-details='1'";
            }else{
                $voucher_detail_value="data-voucher-missing-details='0'";
            }
            $buttons = "<button id= '".$item_id."' type='button' style='margin-right:5px' data-table='".$table_name."' data-item_id='".$item_id."'  $voucher_detail_value  data-confirmation='".$confirmation_required."' data-current_status='".$status_id."' data-next_status='".$approve_next_status."' class='btn btn-".$color." item_action ".$disable_class."'>".$status_button_label."</button>";
        }else{
           
            $buttons .= "<button id= '".$item_id."' type='button' style='margin-right:5px' class='btn btn-".$info_color." disabled final_status'>".$status_name."</button>";
        }
        
        // Show decline button with decline button label
        if( $status_id != $item_initial_item_status_id && $status_approval_direction != -1){
            $buttons .= "<button id= 'decline_btn_".$item_id."' type='button' data-table='".$table_name."' data-confirmation='".$confirmation_required."' data-item_id='".$item_id."' data-current_status='".$status_id."' data-next_status='".$decline_next_status."' class='btn btn-danger  ".$disable_class." item_action'>".$status_decline_button_label."</button>";
        }	
    }else{
        // Show status name/label
         if($voided_chq==true){
            $info_color='warning';
        }
        $buttons = "<button type='button' style='margin-right:5px' class='btn btn-".$info_color." disabled final_status'>".$status_name."</button>";
    }

    return $buttons;
}
}


if(!function_exists('approval_next_status')){
    function approval_next_status($table_name, $item_status, $item_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids){
        $status_approval_sequence = 1;
        $status_approval_direction = 1;
        $status = [];

        $status_approval_sequences = array_values(array_unique(array_column($item_status, 'status_approval_sequence')));
        sort($status_approval_sequences);

        $approve_next_status = 0;
        $decline_next_status = 0;
        $sequence_order_number = 0;

        if(isset($item_status[$status_id])){
            $status = $item_status[$status_id];
            $status_approval_sequence =  $item_status[$status_id]['status_approval_sequence'];
            $status_approval_direction = $item_status[$status_id]['status_approval_direction'];
            $sequence_order_number = array_search($status_approval_sequence, $status_approval_sequences);
        }

        // Compute next approval status and decline status
        foreach($item_status as $id_status => $status_data){            
            
            $next_order_number = $sequence_order_number < count($status_approval_sequences) - 1 ? $sequence_order_number + 1 : $sequence_order_number;
            $next_sequence_number = $status_approval_sequences[$next_order_number];

            // Forward Jump
            if(
                $status_data['status_approval_sequence'] ==  $next_sequence_number && 
                !in_array($status_id, $item_max_approval_status_ids) &&
                $status_data['status_approval_direction'] == 1 &&
                ($status_approval_direction == 1 ||$status_approval_direction == 0)
                ){
                $approve_next_status = $id_status;
            }

            // For Reinstating
            if(
                $status_data['status_approval_sequence'] ==  $status_approval_sequence && 
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == 0 &&
                $status_approval_direction == -1
            ){
                $approve_next_status = $id_status;
            }

            // For Approving Reinstatement
            if(
                $status_data['status_approval_sequence'] ==  $next_sequence_number && 
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == 1 &&
                $status_approval_direction == 0
            ){
                $approve_next_status = $id_status;
            }

            // For Declining
            if(
                $status_data['status_approval_sequence'] ==  $status_approval_sequence && 
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == -1
                ){
                $decline_next_status = $id_status;
            }

            // Approving reinstated item that was declined from full approval status

            if(
                $status_data['status_approval_sequence'] ==  $status_approval_sequence && 
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == 0 &&
                $status_approval_direction == 0
            ){
                $approve_next_status = $item_max_approval_status_ids[0];
            }
        }

        $role_ids = service('session')->role_ids;
        $user_id = service('session')->user_id;

        // Only get positive status greater than the next approval status seq.
        $filtered_positive_item_status_above_current_sequence = array_filter($item_status, function($value) use($item_status, $approve_next_status) {
            return $value['status_approval_direction'] == 1 && $approve_next_status != 0 && $value['status_approval_sequence'] >= $item_status[$approve_next_status]['status_approval_sequence'];
        });

        
        $seqs = array_column($filtered_positive_item_status_above_current_sequence,'status_approval_sequence');
        $status_roles = array_column($filtered_positive_item_status_above_current_sequence,'status_role');
        $seqs_with_roles = array_combine($seqs, $status_roles);
        ksort($seqs_with_roles);

        $ids = array_keys($filtered_positive_item_status_above_current_sequence);
        $seqs = array_column($filtered_positive_item_status_above_current_sequence,'status_approval_sequence');
        $seq_with_ids = array_combine($seqs, $ids);


        // Compute the next approval status if the user is an actor in the next approval process
        if($approve_next_status && sizeof(array_intersect($item_status[$approve_next_status]['status_role'], $role_ids)) > 0){
            foreach($seqs_with_roles as $status_approval_sequence => $state_roles){ 
                $id = $seq_with_ids[$status_approval_sequence];
                if(sizeof(array_intersect($state_roles, $role_ids)) == 0 || in_array($id, $item_max_approval_status_ids)){
                    $approve_next_status = $id;
                    break;
                }
            }
        }

        $next_approval_states = compact('approve_next_status', 'decline_next_status');

        return $next_approval_states;
    }
}


if (!function_exists('sanitize_characters')) {

    function sanitize_characters($string)
    {
        $string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.
        return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $string)); // Removes special chars.

    }
}


if (!function_exists('create_breadcrumb')) {
    function create_breadcrumb()
    {

        $session = service('session');
        $menuLibrary = new \App\Libraries\Core\MenuLibrary();
        $menuLibrary->createBreadcrumb();

        $breadcrumb_list = $session->breadcrumb_list;
		$string = '<nav class = "hidden-print" aria-label="breadcrumb"><ol class="breadcrumb">';

        foreach ($breadcrumb_list as $menuItem) {
            if ($menuLibrary->checkIfMenuIsActive($menuItem)) continue;

            $string .= '<li class="breadcrumb-item"><a href="' . base_url() . $menuItem . '/list">' . get_phrase($menuItem) . '</a></li>';
        }

        $string .= '</ol></nav>';

        return $string;
    }
}

if(!function_exists('decode_setting')){
    function decode_setting ($confiClass, $ConfigKey){
        $dbValue = service('settings')->get("$confiClass.$ConfigKey");

        if(!is_array($dbValue)){
            return json_decode($dbValue);
        }else{
            return $dbValue;
        }
    }
}


if (!function_exists('combine_name_with_ids')) {
    function combine_name_with_ids($office_names_and_ids_arr, $id_field_name, $name_field_name)
    {
        $names_and_ids_arr_without_context_for_office_part=[];    
        foreach ($office_names_and_ids_arr as $name_and_ids) {

            if(strpos($name_and_ids[$name_field_name],'office')){
                $explode_to_remove_context_office_of_part = explode('Context for office', $name_and_ids[$name_field_name]);

                $name_and_ids[$name_field_name] = $explode_to_remove_context_office_of_part[1];

                $names_and_ids_arr_without_context_for_office_part[] = $name_and_ids;
            }else{
                $names_and_ids_arr_without_context_for_office_part[] = $name_and_ids;
            }

        }

        $names = array_column($names_and_ids_arr_without_context_for_office_part, $name_field_name);
        $ids = array_column($names_and_ids_arr_without_context_for_office_part, $id_field_name);
        return array_combine($ids, $names);
    }
}

// if (!function_exists('db_error')) {
//     function db_error($error_code)
//     {
//         $mysql_error_codes = [
//             1452 => "Duplicate records not allowed"
//         ];

//         return isset($mysql_error_codes[$error_code]) ? $mysql_error_codes[$error_code] : "Database error occurred";
//     }
// }

if (!function_exists('alert_error_message')) {
    function alert_error_message(&$error_messages)
    {
        $messages = array_column($error_messages, 'message');
        array_walk($messages, 'create_error_message');
    }
}

if (!function_exists('create_error_message')) {

    function create_error_message($message, $key)
    {

        $explode_msq = explode(':', $message)[0];

        if ($explode_msq != '') {
            echo '=>' . $explode_msq . "\n";
        }
    }
}

if (!function_exists('is_office_in_context_offices')) {
    function is_office_in_context_offices($office_id)
    {
        return in_array($office_id, array_column(session()->context_offices, 'office_id'));
    }
}

if(!function_exists('get_related_voucher')){
    function get_related_voucher($voucher_id){
        $db = \Config\Database::connect();
        return $db->table("voucher")->getWhere(array('voucher_id'=>$voucher_id))->getRow()->voucher_number;
    }
}

if (!function_exists('show_logo')) {
    function show_logo($office_id)
    {
        $logo = "";
        if (!service("settings")->get('GrantsConfig.use_default_logo') && file_exists( "/uploads/office_logos/" . $office_id . ".png")) {
            $logo = '<img src="' . base_url() . 'uploads/office_logos/' . $office_id . '.png"  style="max-height:150px;" alt="Logo"/>';
        } else {
            //$logo = '<img src="' . base_url() . 'uploads/logo.png"  style="max-height:150px;" alt="Logo"/>';
        }
        return $logo;
    }
}

if(!function_exists('format_date')){
    function format_date($mysql_date_format, $format = 'uk'){
        // format can be uk, us
        // Replace the $format with a user session for locale
        $date = new DateTime($mysql_date_format);
        $formatted_date = '';

        if($format == 'us'){
            $formatted_date = $date->format('m/d/Y');
        }else{
            $formatted_date = $date->format('d/m/Y');
        }

        return $formatted_date;
    }
}

if(!function_exists('approval_steps')){
    function approval_steps($account_system_id, $approveable_item_name){   
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $approval_steps = $statusLibrary->getApprovalStepsForAccountSystemApproveItem($account_system_id, $approveable_item_name);
        return $approval_steps;
    }
}

if(!function_exists('calculateFinancialYear')){
    function calculateFinancialYear($inputDate, $startMonth = 7, $two_digit_year = true) {

        $fyString = '';

        // Parse the input date
        $date = new DateTime($inputDate);
        $year = (int)$date->format('Y');
        $month = (int)$date->format('n');

        // Maximum number of months in a year
        $max_count = 12;

        // Initialize variables
        $range_of_months_in_year = [];
        $current_month = $startMonth;

        // Get the list of months with months after 12 get to 13, 14, 15 .....
        for($i = 0; $i < $max_count; $i++){
            $range_of_months_in_year[$i] = $current_month;
            $current_month += 1;
        }
        
        // Sanitize the months numbered beyond 12 to reset them to 1, 2,3 .....
        for($x = 0; $x < count($range_of_months_in_year); $x++){
            if($range_of_months_in_year[$x] > $max_count){
                $range_of_months_in_year[$x] = $range_of_months_in_year[$x] - $max_count;
            }
        }

        // Get the month positions for the month in input date and max month
        $input_month_position = array_search($month, $range_of_months_in_year);
        $max_month_position = array_search($max_count, $range_of_months_in_year);

        // Check if fiscal year is in next year
        $is_fiscal_year_in_next_year = $input_month_position > $max_month_position;
        // 1,2,3,4,5,6,7,8,9,10,11,12

        if($two_digit_year){
            // The fiscal year ends in the next year 
            $fy = $year % 100 + 1;

            if($is_fiscal_year_in_next_year || $startMonth == 1){
                // The fiscal year ends in the current year
                $fy = $year % 100;
            }

            $fyString = str_pad($fy, 2, '0', STR_PAD_LEFT);
        }else{
            // The fiscal year ends in the next year 
            $fyString = $year + 1;

            if($is_fiscal_year_in_next_year || $startMonth == 1){
                // The fiscal year ends in the current year
                $fyString = $year;
            }
        }
        

        return  $fyString;
    }}
    

    if(!function_exists('validate_date')){
        function validate_date($date, $format = 'Y-m-d')
        {
            $d = DateTime::createFromFormat($format, $date);
            // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
            return $d && $d->format($format) === $date;
        }
    }

    if(!function_exists('alias_columns')){
        function alias_columns($columns, $special_separator = 'as'){
        
            $cols = [];
        
            for($i = 0; $i < sizeof($columns); $i++){
              $col_explode = explode($special_separator, $columns[$i]);
            //   log_message('error', json_encode($col_explode));
              $cols[$i]['query_columns'] = trim($col_explode[0]);
              $cols[$i]['list_columns'] = isset($col_explode[1]) ? trim($col_explode[1]) : trim($col_explode[0]);
            }
    
            // log_message('error', json_encode($cols));
            return $cols;
          }
    }

    /**
 * month_order
 * 
 * @author Nicodemus Karisa
 * @date 18th APril 2023
 * @reviewer None
 * @reviewed_date None
 * 
 * @param int office_id - Office Id 
 * 
 * @return array list of months in a year in order of an FCP
 * 
 * @todo:
 * Ready for Peer Review
 */

if(!function_exists('month_order')){
	function month_order($office_id, $budget_id = 0): array {

        $db = \Config\Database::connect('read');
		$months = [];

        $builderMonth = $db->table('month');
        $builderCustomFY = $db->table('custom_financial_year');

		// log_message('error', json_encode([$office_id, $budget_id]));
		
		if($office_id == 0){
            
			$builderMonth->select(array('month_id','month_order','month_number','month_name'));
			$builderMonth->orderBy('month_order', 'ASC');
			$months_array = $builderMonth->get()->getResultArray();
			
			// $months = array_column($months_array, 'month_number');
			foreach($months_array as $month){
				$months[$month['month_order']] = $month;
			}
		}else{
			$office_fy_start_month = 7; // This is July - Default system year start month
            
			$builderCustomFY->select(array('custom_financial_year_start_month'));
            $builderCustomFY->where(array('custom_financial_year.fk_office_id' => $office_id, 'custom_financial_year_is_default' => 1));

			if($budget_id > 0){
                $builderCustomFY->join('budget','budget.fk_custom_financial_year_id=custom_financial_year.custom_financial_year_id');
                $builderCustomFY->where(array('budget_id' => $budget_id));
			}
			
			$office_fy_start_month_obj =  $builderCustomFY->get();
	
			if($office_fy_start_month_obj->getNumRows() > 0){
				$office_fy_start_month = $office_fy_start_month_obj->getRow()->custom_financial_year_start_month;
			}
	
            $builderMonth->select(array('month_id','month_order','month_number','month_name'));
			$months_array_query =  $builderMonth->get();

            $months_array =$months_array_query->getResultArray();
	
			// log_message('error', json_encode($office_fy_start_month));

			$init_month_order = 1;
	
			$extended_month_order = (12 - $office_fy_start_month) + 2;
	
			foreach($months_array as $month){
				if($month['month_number'] < $office_fy_start_month){
					$months[$extended_month_order] = $month;
					$extended_month_order++;
				}else{
					$months[$init_month_order] = $month;
					$init_month_order++;
				}
			}
	
			ksort($months);
		}

		// log_message('error', json_encode($months));

		return $months;
	}
}

if(!function_exists('transfer_types')){
    function transfer_types(){
        //Income Transfer value
        $income_transfer='income_transfer';//get_phrase('income_transfer', 'Income Transfer');


        //Expense Transfer value
        $expense_transfer='expense_transfer';//get_phrase('expense_transfer', 'Expense Transfer');

        return [1 => $income_transfer, 2 =>  $expense_transfer];
    }
}


if(!function_exists('mark_note_as_read')){
    function mark_note_as_read($reader_user_id, $message_detail_id){
        $messageLib=new \App\Libraries\Core\MessageLibrary();

        $messageLib->markNoteAsRead($reader_user_id, $message_detail_id);
    }
}

if(!function_exists('keyed_alias_columns')){
    function keyed_alias_columns($columns, $special_separator = 'as') {
        $alias_columns = alias_columns($columns, $special_separator);

        $list_columns = array_column($alias_columns, 'list_columns');
        $query_columns = array_column($alias_columns, 'query_columns');

        $rst = array_combine($list_columns, $query_columns);

        return $rst;
    }
}

if(!function_exists('get_query_column_for_list_column')){
    function get_query_column_for_list_column($columns, $list_column, $special_separator = 'as') {
        $keyed_alias_columns = keyed_alias_columns($columns, $special_separator);   
        
        // log_message('error', json_encode($keyed_alias_columns));

        $query_column = $keyed_alias_columns[$list_column];

        return $query_column;
    }
}

if (!function_exists('list_lookup_tables')) {
    function list_lookup_tables($table_name)
    {

        $grantsLibrary = new \App\Libraries\System\GrantsLibrary();
        $table_fields = $grantsLibrary->getAllTableFields($table_name);

        $list_lookup_tables = [];

        foreach ($table_fields as $table_field) {
            if (substr($table_field, 0, 3) == 'fk_') {
                $list_lookup_tables[] = substr($table_field, 3, -3);
            }
        }

        return $list_lookup_tables;
    }

    /**
 * Characters that Need to Be Escaped in JSON:
* " (Double Quote)
* \ (Backslash)
* / (Forward Slash, optionally)
* \n (Newline)
* \r (Carriage Return)
* \t (Tab)
* \b (Backspace)
* \f (Form feed)
*/

if(!function_exists('cleanStringForJson')){
    function cleanStringForJson($string) {
		$string = str_replace("\x00", '', $string);
		return preg_replace("/[\x01-\x1F\x7F']/u", '', $string);
    }
}
}
<?php

define('DS', DIRECTORY_SEPARATOR);

if(!function_exists('month_order')){
	function month_order($office_id, $budget_id = 0): array {

		// Database connection for codeigniter 4
        $db = \Config\Database::connect();
        $builder = $db->table('month');
		$months = [];

		// log_message('error', json_encode([$office_id, $budget_id]));
		
		if($office_id == 0){
            
			$builder->select(array('month_id','month_order','month_number','month_name'));
			$builder->orderBy('month_order', 'ASC');
			$months_array = $builder->get('month')->getResultArray();
			
			// $months = array_column($months_array, 'month_number');
			foreach($months_array as $month){
				$months[$month['month_order']] = $month;
			}
		}else{
			$office_fy_start_month = 7; // This is July - Default system year start month

			$builder->select(array('custom_financial_year_start_month'));
			$builder->where(array('custom_financial_year.fk_office_id' => $office_id, 'custom_financial_year_is_default' => 1));

			if($budget_id > 0){
				$builder->join('budget','budget.fk_custom_financial_year_id=custom_financial_year.custom_financial_year_id');
				$builder->where(array('budget_id' => $budget_id));
			}
			
			$office_fy_start_month_obj = $builder->get('custom_financial_year');
	
			if($office_fy_start_month_obj->getNumRows() > 0){
				$office_fy_start_month = $office_fy_start_month_obj->getRow()->custom_financial_year_start_month;
			}
	
			$builder->select(array('month_id','month_order','month_number','month_name'));
			$months_array = $builder->get('month')->getResultArray();
	
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


if (!function_exists('get_fy')) {
	function get_fy($date_string, $office_id = 0 , $override_fy_year_digits_config = false) : int
	{
        $fy_year_digits = service('settings')->get('GrantsConfig.fy_year_digits');

		$date_month_number = date('n', strtotime($date_string));
		$fy = ($fy_year_digits == 4 && !$override_fy_year_digits_config) ? date('Y', strtotime($date_string)) : date('y', strtotime($date_string));

		$months = array_column(month_order($office_id),'month_number');

		// log_message('error', json_encode($months));

        $first_month = current($months);
        $last_month = end($months);

        $fy_year_reference = service('settings')->get('GrantsConfig.fy_year_reference');

        $half_year_months = array_chunk($months, 6);

        if ($first_month != 1 && $last_month != 12) {

            if (in_array($date_month_number, $half_year_months[0]) && $fy_year_reference == 'next') {
                $fy++;
            }
        }

		// log_message('error', json_encode($fy));

        return $fy;
    }

    if (!function_exists('year_month_order')) {
        function year_month_order($custom_financial_year)
        {
            $months_order = [];
            $customFinancialYearLibrary = new \App\Libraries\Grants\CustomFinancialYearLibrary();
            
            if(isset($custom_financial_year['custom_financial_year_start_month'])){
                $months_order =  $customFinancialYearLibrary->getMonthsOrderForCustomYear($custom_financial_year['custom_financial_year_id']);
            }else{
                // Database connection for codeigniter 4
                $db = \Config\Database::connect();
                $builder = $db->table('month');

                $builder->select(array('month_number'));
                $builder->orderBy('month_order ASC');
                $months_array = $builder->get('month')->getResultArray();
                $months_order = array_column($months_array, 'month_number');  
            }
    
            return $months_order;
        }
    }

    if (!function_exists('fy_start_date')) {
        function fy_start_date($date_string, $custom_financial_year)
        {
            
            $customFinancialYearLibrary = new \App\Libraries\Grants\CustomFinancialYearLibrary();
    
            $startMonth = isset($custom_financial_year['custom_financial_year_start_month']) ? $custom_financial_year['custom_financial_year_start_month'] : 7;
            $month = strlen($startMonth) == 1 ? '0'.$startMonth : $startMonth;
    
            $months_order = [];
    
            if(isset($custom_financial_year['custom_financial_year_start_month'])){
                $months_order =  $customFinancialYearLibrary->getMonthsOrderForCustomYear($custom_financial_year['custom_financial_year_id']);
            }else{
                $db = \Config\Database::connect();
                $builder = $db->table('month');
                $builder->select(array('month_number'));
                $builder->orderBy('month_order ASC');
                $months_array = $builder->get('month')->getResultArray();
                $months_order = array_column($months_array, 'month_number');  
            }
    
            $first_month = current($months_order);
            $last_month = end($months_order);
    
            $half_year_months = array_chunk($months_order, 6);
    
            $fy = calculateFinancialYear($date_string, $startMonth, false);
    
            // log_message('error', json_encode(['fy_year_reference' => $fy_year_reference, 'fy' => $fy, 'half_year_months' => $half_year_months, 'first_month' => $first_month, 'last_month' => $last_month, 'date_month_number' => $date_month_number,'date_string' => $date_string, 'custom_financial_year' => $custom_financial_year, 'months_order' => $months_order]));
    
            if ($first_month != 1 && $last_month != 12) {
                if (in_array($startMonth, $half_year_months[0])) {
                    $fy--;
                }
            }
    
            return $fy.'-'.$month.'-01';
        }}

        if (!function_exists('financial_year_quarter_months')) {
            function financial_year_quarter_months($month_number, $office_id = 0)
            {
        
                // $CI->read_db->select(array('month_number'));
                // $CI->read_db->order_by('month_order ASC');
                // $months = $CI->read_db->get('month')->result_array();
        
                // $month_mumbers = array_column($months, 'month_number');

                $db = \Config\Database::connect();
                $builder = $db->table('budget_review_count');
        
                $month_numbers = array_column(month_order($office_id),'month_number');
        
                $builder->where(array('fk_account_system_id' => service('session')->get('user_account_system_id')));
                $count_of_reviews_in_year = $builder->get('budget_review_count')->getRow()->budget_review_count_number;
                $count_of_months_in_period = count($month_numbers) / $count_of_reviews_in_year;
                /**
                 * 1 - 12 
                 * 2 - 6  
                 * 3 - 4
                 * 4 - 3
                 */
                $range_of_reviews = range(1, $count_of_reviews_in_year); // [1,2,3,4] - Assume the $count_of_reviews_in_year = 4
                $month_arrays_in_period = array_chunk($month_numbers, $count_of_months_in_period); //[[7,8,9],[10,11,12],[1,2,3],[4,5,6]]
        
                $months_in_quarters = array_combine($range_of_reviews, $month_arrays_in_period);
        
                $current_quarter_months = [];
        
                foreach ($months_in_quarters as $quarter_number => $months_in_quarter) {
                    if (in_array($month_number, $months_in_quarter)) {
                        $current_quarter_months['quarter_number'] = $quarter_number;
                        $current_quarter_months['months_in_quarter'] = $months_in_quarter;
                    }
                }
        
                return $current_quarter_months;
            }
        }

        if (!function_exists('upload_url')) {
            function upload_url($controller, $record_id = "", $extra_keys = [])
            {
                //return "uploads".DS."attachments".DS.$controller.DS.$record_id.DS.implode(DS,$extra_keys);
        
                $s3_folder_path="uploads/attachments/" . $controller;
               
                if($record_id!=''){
                    $s3_folder_path="uploads/attachments/" . $controller . "/" . $record_id;
                }
        
                if(!empty($extra_keys)){
                    $s3_folder_path .='/'.implode("/", $extra_keys);
                }
                return $s3_folder_path;
            }
        }
}
?>
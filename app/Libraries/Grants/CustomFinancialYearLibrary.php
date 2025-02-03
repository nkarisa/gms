<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CustomFinancialYearModel;

class CustomFinancialYearLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $customFinancialYearModel;

    function __construct()
    {
        parent::__construct();

        $this->customFinancialYearModel = new CustomFinancialYearModel();

        $this->table = 'custom_financial_year';
    }

    /**
     *getDefaultCustomFinancialYearIdByOffice():This method returns arow of custom financial years.
     * @author Livingstone Onduso: Dated 30-01-2025
     * @access public
    * @return array 
    * @param int $office_id
   */
    public function getDefaultCustomFinancialYearIdByOffice(int $office_id){

        // $custom_financial_year_start_month = 7;
        $custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];
    
        $builder = $this->read_db->table("custom_financial_year");
        $builder->select(array('custom_financial_year_start_month','custom_financial_year_id', 'custom_financial_year_is_active', 'custom_financial_year_reset_date'));
        $builder->where(array('custom_financial_year_is_default'=> 1,'fk_office_id' => $office_id));
        $custom_financial_year_obj = $builder->get();
        
        if($custom_financial_year_obj->getNumRows() > 0){
          // log_message('error', json_encode($custom_financial_year_obj->row()));
          $custom_financial_year = $custom_financial_year_obj->getRowArray();
        }
    
        return $custom_financial_year;
      }

      function getCustomFinancialYearById($custom_financial_year_id){
        $custom_financial_year = [];
    
        $builder = $this->read_db->table('custom_financial_year');
        $builder->select(array('custom_financial_year_id','custom_financial_year_start_month', 'custom_financial_year_is_active'));
        $builder->where(array('custom_financial_year_id' => $custom_financial_year_id));
        $custom_financial_year_obj = $builder->get();
    
        if($custom_financial_year_obj->getNumRows() > 0){
          $custom_financial_year = $custom_financial_year_obj->getRowArray();
        }
    
        return $custom_financial_year;
    }

    function transactionPeriodBehindDefaultCustomFyResetDate($next_vouching_date,$custom_financial_year){

        $transaction_period_behind_default_custom_fy_reset_date = false;
        // log_message('error', json_encode(['office_id' => $office_id, 'next_vouching_date' => $next_vouching_date, 'custom_financial_year' => $custom_financial_year]));
      
        $custom_financial_year_id = $custom_financial_year['custom_financial_year_id'];
    
        if($custom_financial_year_id != null){
          $next_vouching_date_stamp = strtotime('first day of this  month', strtotime($next_vouching_date));
          $custom_financial_year_reset_date_stamp = strtotime('first day of this month', strtotime($custom_financial_year['custom_financial_year_reset_date']));
    
          // log_message('error', json_encode([$next_vouching_date_stamp, $custom_financial_year_reset_date_stamp]));
    
          if($custom_financial_year_reset_date_stamp > $next_vouching_date_stamp){
            $transaction_period_behind_default_custom_fy_reset_date = true;
          }
        }
    
        return $transaction_period_behind_default_custom_fy_reset_date;
      }

      function getPreviousCustomFinancialYearByCurrentId($office_id, $current_custom_financial_year_id){
    
        $builder = $this->read_db->table("custom_financial_year");
        $builder->select(array('custom_financial_year_id','custom_financial_year_start_month','custom_financial_year_is_active','custom_financial_year_reset_date'));
        $builder->where(array('fk_office_id' => $office_id, 'custom_financial_year_id <> ' => $current_custom_financial_year_id));
        $builder->orderBy('custom_financial_year_id ASC');
        $custom_financial_year_obj = $builder->get();
    
        $previous_custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];
    
        if($custom_financial_year_obj->getNumRows() > 0){
          $previous_custom_financial_year = $custom_financial_year_obj->getRowArray();
        }
    
        return $previous_custom_financial_year;
      }

      function officeCustomFinancialYears($office_id){

        $custom_financial_years = [];

        $builder = $this->read_db->table("custom_financial_year");
        $builder->where(array('fk_office_id' => $office_id));
        $builder->orderBy('custom_financial_year_id ASC');
        $custom_financial_years_obj = $builder->get();

        if($custom_financial_years_obj->getNumRows() > 0){
            $custom_financial_years = $custom_financial_years_obj->getResultArray();
        }

        return $custom_financial_years;
    }

    function getMonthsOrderForCustomYear($custom_financial_year_id){

        $start_month = 7; 
        
        $builder = $this->read_db->table("custom_financial_year");
        $builder->select(array('custom_financial_year_start_month'));
        $builder->where(array('custom_financial_year_id' => $custom_financial_year_id));
        $start_month_obj = $builder->get();

        if($start_month_obj->getNumRows() > 0){
            $start_month =  $start_month_obj->getRow()->custom_financial_year_start_month;
        }
        
        $months = range($start_month, 12);

        if(count($months) < 12){
            $months_in_next_year = range(1, (12 - count($months)));
            $months = array_merge($months,$months_in_next_year);
        }

        return $months;
    }

    function listTableVisibleColumns(): array {
      return [
      'custom_financial_year_id',
      'custom_financial_year_track_number as track_number',
      'office_name',
      'custom_financial_year_start_month as start_month',
      'custom_financial_year_reset_date as reset_date',
      'custom_financial_year_is_active as is_active',
      'custom_financial_year_is_default as is_default',
      'custom_financial_year_created_date as created_date',];
    }


    
    
}
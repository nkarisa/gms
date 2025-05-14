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
  public function getDefaultCustomFinancialYearIdByOffice(int $office_id)
  {

    // $custom_financial_year_start_month = 7;
    $custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];

    $builder = $this->read_db->table("custom_financial_year");
    $builder->select(array('custom_financial_year_start_month', 'custom_financial_year_id', 'custom_financial_year_is_active', 'custom_financial_year_reset_date'));
    $builder->where(array('custom_financial_year_is_default' => 1, 'fk_office_id' => $office_id));
    $custom_financial_year_obj = $builder->get();

    if ($custom_financial_year_obj->getNumRows() > 0) {
      // log_message('error', json_encode($custom_financial_year_obj->row()));
      $custom_financial_year = $custom_financial_year_obj->getRowArray();
    }

    return $custom_financial_year;
  }

  function getCustomFinancialYearById($custom_financial_year_id)
  {
    $custom_financial_year = [];

    $builder = $this->read_db->table('custom_financial_year');
    $builder->select(array('custom_financial_year_id', 'custom_financial_year_start_month', 'custom_financial_year_is_active'));
    $builder->where(array('custom_financial_year_id' => $custom_financial_year_id));
    $custom_financial_year_obj = $builder->get();

    if ($custom_financial_year_obj->getNumRows() > 0) {
      $custom_financial_year = $custom_financial_year_obj->getRowArray();
    }

    return $custom_financial_year;
  }

  function transactionPeriodBehindDefaultCustomFyResetDate($next_vouching_date, $custom_financial_year)
  {

    $transaction_period_behind_default_custom_fy_reset_date = false;
    // log_message('error', json_encode(['office_id' => $office_id, 'next_vouching_date' => $next_vouching_date, 'custom_financial_year' => $custom_financial_year]));

    $custom_financial_year_id = $custom_financial_year['custom_financial_year_id'];

    if ($custom_financial_year_id != null) {
      $next_vouching_date_stamp = strtotime('first day of this  month', strtotime($next_vouching_date));
      $custom_financial_year_reset_date_stamp = strtotime('first day of this month', strtotime($custom_financial_year['custom_financial_year_reset_date']));

      // log_message('error', json_encode([$next_vouching_date_stamp, $custom_financial_year_reset_date_stamp]));

      if ($custom_financial_year_reset_date_stamp > $next_vouching_date_stamp) {
        $transaction_period_behind_default_custom_fy_reset_date = true;
      }
    }

    return $transaction_period_behind_default_custom_fy_reset_date;
  }

  function getPreviousCustomFinancialYearByCurrentId($office_id, $current_custom_financial_year_id)
  {

    $builder = $this->read_db->table("custom_financial_year");
    $builder->select(array('custom_financial_year_id', 'custom_financial_year_start_month', 'custom_financial_year_is_active', 'custom_financial_year_reset_date'));
    $builder->where(array('fk_office_id' => $office_id, 'custom_financial_year_id <> ' => $current_custom_financial_year_id));
    $builder->orderBy('custom_financial_year_id ASC');
    $custom_financial_year_obj = $builder->get();

    $previous_custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];

    if ($custom_financial_year_obj->getNumRows() > 0) {
      $previous_custom_financial_year = $custom_financial_year_obj->getRowArray();
    }

    return $previous_custom_financial_year;
  }

  function officeCustomFinancialYears($office_id)
  {

    $custom_financial_years = [];

    $builder = $this->read_db->table("custom_financial_year");
    $builder->where(array('fk_office_id' => $office_id));
    $builder->orderBy('custom_financial_year_id ASC');
    $custom_financial_years_obj = $builder->get();

    if ($custom_financial_years_obj->getNumRows() > 0) {
      $custom_financial_years = $custom_financial_years_obj->getResultArray();
    }

    return $custom_financial_years;
  }

  function getMonthsOrderForCustomYear($custom_financial_year_id)
  {

    $start_month = 7;

    $builder = $this->read_db->table("custom_financial_year");
    $builder->select(array('custom_financial_year_start_month'));
    $builder->where(array('custom_financial_year_id' => $custom_financial_year_id));
    $start_month_obj = $builder->get();

    if ($start_month_obj->getNumRows() > 0) {
      $start_month =  $start_month_obj->getRow()->custom_financial_year_start_month;
    }

    $months = range($start_month, 12);

    if (count($months) < 12) {
      $months_in_next_year = range(1, (12 - count($months)));
      $months = array_merge($months, $months_in_next_year);
    }

    return $months;
  }

  function listTableVisibleColumns(): array
  {
    return [
      'custom_financial_year_id',
      'custom_financial_year_track_number as track_number',
      'office_name',
      'custom_financial_year_start_month as start_month',
      'custom_financial_year_reset_date as reset_date',
      'custom_financial_year_is_active as is_active',
      'custom_financial_year_is_default as is_default',
      'custom_financial_year_created_date as created_date',
    ];
  }


  public function changeFieldType(): array
  {
    $fields = [];

    $builder = $this->read_db->table('month');
    $builder->select(['month_number', 'month_name']);
    $months = $builder->get()->getResultArray();

    $fields['custom_financial_year_start_month']['field_type'] = 'select';

    foreach ($months as $month) {
      $fields['custom_financial_year_start_month']['options'][$month['month_number']] = $month['month_name'];
    }

    // $fields['custom_financial_year_reset_date']['field_type'] = 'text';

    return $fields;
  }



  public function lookupValues(): array
  {
    $lookupValues = [];

    $offices = [];

    //Offices of the users.
    $office_hierachy=array_column(session()->get('hierarchy_offices'),'office_id');

    if (!session()->get('system_admin')) {

      $builder = $this->read_db->table('office');
      $builder->select(['office_id', 'office_name']);
      $builder->where(['fk_context_definition_id' => 1, 'office_is_active' => 1]);
      $builder->whereIn('office_id',$office_hierachy);
      //$builder->orWhere('office_is_readonly', 0);
      $offices_obj = $builder->get();

      if ($offices_obj->getNumRows() > 0) {
        $offices = $offices_obj->getResultArray();
      }

      $lookupValues['office'] = $offices;
    }



    return $lookupValues;
  }

  public function singleFormAddVisibleColumns():array{

    return ['office_name', 'custom_financial_year_reset_date','custom_financial_year_start_month'];

   
  }


   // Update any default custom_financial_year record to 0
   function actionBeforeInsert($post_array):array{
    $office_id = $post_array['header']['fk_office_id'];
    $new_reset_date = $post_array['header']['custom_financial_year_reset_date'];

    // Check if there is an existing default custom fy with 3 years and below based on the custom_financial_year_reset_date
    $checkExistingCustomFYWith3YearsAndBelow = $this->checkExistingCustomFYWith3YearsAndBelow($office_id, $new_reset_date);
    // log_message('error', json_encode($checkExistingCustomFYWith3YearsAndBelow));
    if($checkExistingCustomFYWith3YearsAndBelow){
        return ['message' => get_phrase('existing_custom_fy_with_3_years_and_below','There is an existing default custom financial year with 3 years and below based on the custom financial year reset date')];
    }

    // Check if there is an existing default custom financial year. Turn it to non default it exists
    $checkExistingDefaultCustomFY = $this->checkExistingDefaultCustomFY($office_id);

    if($checkExistingDefaultCustomFY){
        $this->setCustomFyAsNonDefault($office_id);
    }

    // Update the default custom_financial_year record
    $writeBuilder=$this->write_db->table('custom_financial_year');

    $data['custom_financial_year_is_active']=1;
    $data['custom_financial_year_is_default']=1;

    $writeBuilder->where(array('custom_financial_year_is_default' => 1,'fk_office_id' => $office_id));
    $writeBuilder->update($data);

    return $post_array;
}

// checkExistingCustomFYWith3YearsAndBelow has the custom_financial_year_reset_date 3 years and below
function checkExistingCustomFYWith3YearsAndBelow($office_id, $current_reset_data){
  $checkExistingCustomFYWith3YearsAndBelow = false;
  
  $readBuilder=$this->read_db->table('custom_financial_year');
  $readBuilder->where(array('custom_financial_year_is_default' => 1,'fk_office_id' => $office_id));
  $custom_financial_year_obj = $readBuilder->get();

 //  log_message('error', json_encode($custom_financial_year_obj->result_array()));
  
  if($custom_financial_year_obj->getNumRows() > 0){
      $previous_reset_date = $custom_financial_year_obj->getRow()->custom_financial_year_reset_date;
      
      $date1 = new \DateTime($previous_reset_date);
      $date2 = new \DateTime($current_reset_data);
    
      
     //  log_message('error', json_encode(compact('office_id', 'current_reset_data','previous_reset_date')));

     // Calculate the difference
     $diff = $date1->diff($date2);

     // Convert the difference to total months
     $totalMonths = ($diff->y * 12) + $diff->m;

     if ($totalMonths < 36) {
         $checkExistingCustomFYWith3YearsAndBelow = true;
     }
 }

 return $checkExistingCustomFYWith3YearsAndBelow;
}

private function setCustomFyAsNonDefault($office_id){

  $data['custom_financial_year_is_active']=0;

  $data['custom_financial_year_is_default']=0;

  $writeBuilder=$this->write_db->table("custom_financial_year");

  $writeBuilder->where(array('custom_financial_year_is_default' => 1,'fk_office_id' => $office_id));
  
  $writeBuilder->update($data);
}

private function checkExistingDefaultCustomFY($office_id){
  $checkExistingDefaultCustomFY = false;

  $readerBuilder=$this->read_db->table("custom_financial_year");

  $readerBuilder->where(array('custom_financial_year_is_default' => 1,'fk_office_id' => $office_id));
  $count = $readerBuilder->get()->getNumRows();

  if($count > 0){
      $checkExistingDefaultCustomFY = true;
  }

  return $checkExistingDefaultCustomFY;
}


}

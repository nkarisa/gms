<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\OfficeModel;

class OfficeLibrary extends GrantsLibrary
{

  protected $table;
  protected $officeModel;

  function __construct()
  {
    parent::__construct();

    $this->officeModel = new OfficeModel();

    $this->table = 'office';
  }


  function getRecordOfficeId($table, $primary_key)
  {

    $lookup_tables = $this->lookupTables($table);
    $pk_field = $this->primaryKeyField($table);

    $office_id = 0;

    if (in_array('office', $lookup_tables)) {
      $builder = $this->read_db->table($table);
      $builder->where($pk_field, $primary_key);
      $office_id = $builder->get()->getRow()->fk_office_id;
    }

    return $office_id;
  }

  function getAllOfficeContext(): array
  {

    $builder = $this->read_db->table('context_definition');

    $builder->select(array('context_definition_id', 'context_definition_name'));

    if (!$this->session->system_admin) {
      $builder->whereIn('context_definition_level', [1, 2, 3, 4]);
    }
    $all_context_offices = $builder->get()->getResultArray();

    $all_office_context_ids = array_column($all_context_offices, 'context_definition_id');
    $all_office_context_names = array_column($all_context_offices, 'context_definition_name');

    $all_office_context_ids_and_names = array_combine($all_office_context_ids, $all_office_context_names);

    return $all_office_context_ids_and_names;

  }

  function userOffice($context_id, $user_id): array
  {

    //Check context
    switch ($context_id) {
      case 1:
        $context_office = 'context_center';
        break;
      case 2:
        $context_office = 'context_cluster';
        break;
      case 3:
        $context_office = 'context_cohort';
        break;
      case 4:
        $context_office = 'context_country';
        break;
      case 5:
        $context_office = 'context_region';
        break;
      case 6:
        $context_office = 'context_global';
        break;
    }
    //Get office for a user e.g. KE0415- Ekambuli CDC
    $builder = $this->read_db->table('office');
    $builder->select(array('office_name', 'office_id'));
    $builder->join($context_office, $context_office . '.fk_office_id=office.office_id');
    $builder->join($context_office . '_user', $context_office . '_user.fk_' . $context_office . '_id=' . $context_office . '.' . $context_office . '_id');
    $builder->where(['fk_user_id' => $user_id]);
    $office_name = $builder->get()->getResultArray();

    return $office_name;
  }


  function getOffices($context_definition_id, $add_user_form)
  {

    $offices = [];
    switch ($context_definition_id) {
      case 1:
        $offices = $this->getClustersOrCohortsOrCountries('context_center', 'context_center_id', 'office_name', true, $add_user_form);
        break;
      case 2:
        $offices = $this->getClustersOrCohortsOrCountries('context_cluster', 'context_cluster_id', 'office_name', true, $add_user_form);
        break;
      case 3:
        $offices = $this->getClustersOrCohortsOrCountries('context_cohort', 'context_cohort_id', 'office_name', true, $add_user_form);
        break;
      case 4:
        $offices = $this->getClustersOrCohortsOrCountries('context_country', 'context_country_id', 'office_name', true, $add_user_form);
        break;
      case 5:
        $offices = $this->getClustersOrCohortsOrCountries('context_region', 'context_region_id', 'office_name', true, $add_user_form);
        break;
      case 6:
        $offices = $this->getClustersOrCohortsOrCountries('context_global', 'context_global_id', 'office_name', true, $add_user_form);
        break;
    }

    return $offices;
  }


  function getClustersOrCohortsOrCountries(string $table_name, string $column_id, string $column_name, bool $return_active_office_only = false, $add_user_form = 0): array
  {

    $builder = $this->read_db->table($table_name);

    if (!$this->session->system_admin) {
      $builder->where(array('office.fk_account_system_id' => $this->session->user_account_system_id));
    }

    if ($return_active_office_only) {
      $builder->where(array('office.office_is_active' => 1));
    }

    $join_string = 'office.office_id=' . $table_name . '.fk_office_id';

    //If not Add user Form and we are on EDIT user Form
    if ($add_user_form == 0) {
      $column_id = 'office_id';
    }

    $builder->select([$column_id, $column_name]);

    $builder->join('office', $join_string);

    $clusters_or_cohort_or_contries_offices = $builder->get()->getResultArray();

    $office_ids = array_column($clusters_or_cohort_or_contries_offices, $column_id);
    $office_names = array_column($clusters_or_cohort_or_contries_offices, $column_name);

    $office_ids_and_names = array_combine($office_ids, $office_names);

    return $office_ids_and_names;

  }

  function getOfficeAccountSystem($office_id){
    $builder = $this->read_db->table('office');
    $builder->select(array('office_id','office_name','account_system_id','account_system_name'));
    $builder->join('account_system', 'account_system.account_system_id=office.fk_account_system_id');
    $builder->where(array('office_id' => $office_id));
    $office_account_system = $builder->get()->getRowArray();
    return $office_account_system;
  }


  /**
   * get_offices(): return an array of offices like fcp/cluster/region
   * @author Onduso 
   * @access public 
   * @return array
   * @param int $account_system_id, int $context_definition_id
   */
  public function getOfficesByAccountSystemId(int $account_system_id, int $context_definition_id): array
  {

    $builder = $this->read_db->table('office');

    $builder->select(['office_id', 'office_name']);
    $builder->where(['office_is_active' => 1, 'fk_account_system_id' => $account_system_id, 'fk_context_definition_id' => $context_definition_id]);
    $offices = $builder->get()->getResultArray();

    $office_ids = array_column($offices, 'office_id');
    $office_names = array_column($offices, 'office_name');

    $office_ids_and_names = array_combine($office_ids, $office_names);

    return $office_ids_and_names;
  }

   /**
     * get_office_name(): get office name of the user; 
     * @author Onduso 
     * @access private 
     * @return string
     * @dated: 18/08/2023
     * @param int $user_office
     */
    public function getOfficeName(int $officeId): string
    {
        $builder = $this->read_db->table('office');
        $builder->select(['office_name']);
        $builder->where(['office_id' => $officeId]);
        $user_office_name = $builder->get()->getRow()->office_name;

        return $user_office_name;
    }

    function pagePosition(){
      $widget['position_1']['list'] = view("office/buttons");
      return $widget;
    }


    function dataTableCondition(\CodeIgniter\Database\BaseBuilder $builder, $dataFields){
      $context_definition_id = $dataFields['context_definition_id'];
      $builder->where('office.fk_context_definition_id', $context_definition_id);
    }

    function listTableVisibleColumns(): array {
      return ['office_track_number', 'office_name', 'office_is_active', 'office_is_suspended','office_start_date', 'office_end_date', 'context_definition_name', 'account_system_name'];
    }

    function formatColumnsValues(string $columnName, mixed $columnValue, array $rowArray): mixed {
      switch($columnName){
        case "office_end_date":
          $columnValue = $columnValue == "0000-00-00" ? get_phrase('value_not_set') : $columnValue;
          break;
        case "mass_update":
          $disabled = "";
          if($rowArray["context_definition_name"] != "center"){
            $disabled = "disabled";
          }
          $columnValue = '<div class="form-group"><input class="checkbox" '.$disabled.' type="checkbox" onclick="check_or_uncheck_checkbox()" name="office_ids[]"  id="'.$rowArray['office_id'].'"></div>';
          break;
        case "action":
          $userLibrary = new UserLibrary();
          $label = 'Suspend';
          $color = 'btn-danger';

          if($rowArray['office_is_suspended']){
              $label = 'Unsuspend';
              $color = 'btn-success';
          }
          
          $disabled = "disabled";
          if(
            $userLibrary->checkRoleHasPermissions('office', 'update') &&
            $rowArray['context_definition_name'] == 'center'
            ){
              $disabled = "";
          }

          $columnValue = '<div data-office_id = "'.$rowArray['office_id'].'" data-suspension_status = "'.$rowArray['office_is_suspended'].'" class="btn '.$color.' suspend '.$disabled.'">'.$label.'</div>';
          break;
        case "office_reporting_to":
          $columnValue = $this->getReportingOfficeName($rowArray['office_id'], $rowArray['context_definition_name']);
          break;
        default:
          break;
      }

      return $columnValue;
    }

    function getReportingOfficeName(int $officeId, string $officeContextName){
      $reportingOfficeName = "";
      if($officeContextName == 'center'){
        $builder = $this->read_db->table('context_center');
        $builder->select("office_name");
        $builder->join("context_cluster","context_cluster.context_cluster_id=context_center.fk_context_cluster_id");
        $builder->join("office","office.office_id=context_cluster.fk_office_id");
        $builder->where("context_center.fk_office_id", $officeId);
        $reportingOfficeName = $builder->get()->getRow()->office_name;
      }elseif($officeContextName == 'cluster'){
        $builder = $this->read_db->table('context_cluster');
        $builder->select("office_name");
        $builder->join("context_cohort","context_cohort.context_cohort_id=context_cluster.fk_context_cohort_id");
        $builder->join("office","office.office_id=context_cohort.fk_office_id");
        $builder->where("context_cluster.fk_office_id", $officeId);
        $reportingOfficeName = $builder->get()->getRow()->office_name;
      }elseif($officeContextName == 'cohort'){
        $builder = $this->read_db->table('context_cohort');
        $builder->select("office_name");
        $builder->join("context_country","context_country.context_country_id=context_cohort.fk_context_country_id");
        $builder->join("office","office.office_id=context_country.fk_office_id");
        $builder->where("context_cohort.fk_office_id", $officeId);
        $reportingOfficeName = $builder->get()->getRow()->office_name;
      }elseif($officeContextName == 'country'){
        $builder = $this->read_db->table('context_country');
        $builder->select("office_name");
        $builder->join("context_region","context_region.context_region_id=context_country.fk_context_region_id");
        $builder->join("office","office.office_id=context_region.fk_office_id");
        $builder->where("context_country.fk_office_id", $officeId);
        $reportingOfficeName = $builder->get()->getRow()->office_name;
      }

      return $reportingOfficeName;
    }

    function additionalListColumns(): array {
      $columns = [
        'office_reporting_to' => 'office_name',
        'mass_update' => "office_id", 
        "action" => "mass_update"
      ];

      return $columns;
    }

    public function getAllAccountSystemOffices($account_system_id, $context_definition_id = 0){
      $builder = $this->read_db->table('office');
      $builder->select(array('office_id','office_track_number','office_code','context_definition_id','context_definition_name',
      'office_name','office_start_date','context_cluster_name','context_cohort_name','context_cohort_name','context_country_name','office_is_suspended')); 
      
      if(!$this->session->system_admin){
        $builder->where(array('fk_account_system_id' => $account_system_id));
        $builder->whereIn('office_id',array_column($this->session->hierarchy_offices,'office_id'));
      }
  
      if($context_definition_id > 0){
        $builder->where(array('office.fk_context_definition_id' => $context_definition_id));
      }
  
      $builder->join('context_definition','context_definition.context_definition_id=office.fk_context_definition_id');
      $builder->join('context_center','context_center.fk_office_id=office.office_id',"LEFT");
      $builder->join('context_cluster','context_cluster.context_cluster_id=context_center.fk_context_cluster_id',"LEFT");
      $builder->join('context_cohort','context_cohort.context_cohort_id=context_cluster.fk_context_cohort_id',"LEFT");
      $builder->join('context_country','context_country.context_country_id=context_cohort.fk_context_country_id',"LEFT");
      $builder->join('context_region','context_region.context_region_id=context_country.fk_context_region_id',"LEFT");
      $builder->join('context_global','context_global.context_global_id=context_region.fk_context_global_id',"LEFT");
      $offices_obj = $builder->get();
  
      $offices = [];
  
      if($offices_obj->getNumRows() > 0){
        $offices = $offices_obj->getResultArray();
      }
  
      return $offices;
    }

     /**
   * Get edit records
   * 
   * This method retrives records to be edited 
   * .
   * 
   * @param int $office_id - Primary ID of the office
   * @return array - array of a row
   * @Author :Livingstone Onduso
   * @Date: 08/05/2022
   */

  function getEditOfficeRecords(int $office_id){

    //Get the context_defination to edit
    $context_definition_id = $this->read_db->table('office')
    ->where(['office_id'=>$office_id])->get()->getRow()->fk_context_definition_id;

    //Get reporting_context_id and name
    switch($context_definition_id) {
      case 1:
         $table_name='context_center';
         $context_reporting_column_name='fk_context_cluster_id';
         break;
      case 2:
        $table_name='context_cluster';
        $context_reporting_column_name='fk_context_cohort_id';
        break;
      case 3: 
        $table_name='context_cohort';
        $context_reporting_column_name='fk_context_country_id';
        break;
      case 4:
        $table_name='context_country';
        $context_reporting_column_name='fk_context_region_id';
        break;
      case 5: 
        $table_name='context_region';
        $context_reporting_column_name='fk_context_global_id';
        break;
      case 6:
        $table_name='context_global';
        break;

    }

    //Get the join table and get the reporting context office 
    $explode_to_column=explode('_', $context_reporting_column_name);
    $join_table_name=$explode_to_column[1].'_'.$explode_to_column[2]; 
    $join_column_id_str=$join_table_name.'.'.$join_table_name.'_id='.$table_name.'.'.$context_reporting_column_name;

    $builder = $this->read_db->table($table_name);
    $builder->select([$context_reporting_column_name,$join_table_name.'_name']);
    $builder->join($join_table_name, $join_column_id_str);
    $builder->where(array($table_name.'.'.'fk_office_id'=>$office_id));
    $reporting_context = $builder->get()->getRowArray();

    //Get the office record to edit
    $builder = $this->read_db->table('office');
    $builder->select(['office_id','office_name','office_code','office_description', 'office_start_date', 'office_is_active','office_is_readonly', 'office.fk_context_definition_id as fk_context_definition_id','context_definition_name', 'account_system_name','fk_account_system_id','fk_country_currency_id']);
    $builder->join('context_definition', 'context_definition.context_definition_id=office.fk_context_definition_id');
    $builder->join('account_system','account_system.account_system_id=office.fk_account_system_id');
    $builder->where(['office_id'=>$office_id]);
    $records_to_be_edited = $builder->get()->getRowArray();

    //Merge the reporting office and the office records
    $all_records_to_be_edited = array_merge($records_to_be_edited,$reporting_context);

    return $all_records_to_be_edited;
  }

   /**
   * Get ids and names columns details from the tables
   * This method retrives combined ids
   * @return Array - array
   * @Author :Livingstone Onduso
   * @Date: 07/08/2022
   */

   function retrieveIdsAndNamesRecords(Array $select_columns, string $table_name):array{
    $builder = $this->read_db->table($table_name);
    $builder->select($select_columns);
    $context = $builder->get()->getResultArray();

    $ids = array_column($context,$select_columns[0]);
    $names = array_column($context,$select_columns[1]);
    $combined_ids_and_names=array_combine($ids, $names);

    return $combined_ids_and_names;
  }

  function add(){
    $flag = false;
    $message = 'Office creation failed';
    $error_messages = [];

    $this->write_db->transBegin();

    $post = $this->request->getPost()['header'];

    $office['office_name'] = $post['office_name'];
    $office['office_description'] = $post['office_description'];
    $office['office_code'] = $post['office_code'];
    $office['fk_context_definition_id'] = $post['fk_context_definition_id'];
    $office['office_start_date'] = $post['office_start_date'];
    $office['fk_country_currency_id'] = $post['fk_country_currency_id'];
    $office['office_is_active'] = $post['office_is_active'];
    $office['fk_account_system_id'] = $post['fk_account_system_id'];
    $office['fk_country_currency_id'] = $post['fk_country_currency_id'];

    $office['office_is_readonly'] = 1;
    //Modify this to 0 if the office==center
    if($post['fk_context_definition_id']==1){
      $office['office_is_readonly'] = 0;
    }
   
    $office_to_insert = $this->mergeWithHistoryFields($this->controller, $office, false);
    $this->write_db->table('office')->insert( $office_to_insert);

    $error_messages['office']=$this->write_db->error();

    $inserted_office_id = $this->write_db->insertId();

    // Create an office context 
    $context_definition = $this->read_db->table('context_definition')
    ->where(array('context_definition_id' => $post['fk_context_definition_id']))->get()->getRow();

    $context_definition_name = $context_definition->context_definition_name;

    $reporting_context_definition_name = $this->getReportingOfficeContext($context_definition)->context_definition_name;

    $reporting_context_definition_table = 'context_' . $reporting_context_definition_name;

    $office_context['context_' . $context_definition_name . '_name'] = "Context for office " . $post['office_name'];
    $office_context['context_' . $context_definition_name . '_description'] = "Context for office " . $post['office_name'];
    $office_context['fk_' . $reporting_context_definition_table . '_id'] = $post['office_context'];
    $office_context['fk_context_definition_id'] = $post['fk_context_definition_id'];
    $office_context['fk_office_id'] = $inserted_office_id;

    //echo json_encode($office_context);
    $office_context_to_insert = $this->mergeWithHistoryFields('context_' . $context_definition_name, $office_context, false);

    $this->write_db->table('context_' . $context_definition_name)->insert( $office_context_to_insert);

    $error_messages['context'] = $this->write_db->error();

    // Create office System Opening Balance Record
    $system_opening_balance['system_opening_balance_name'] = 'Financial Opening Balance for ' . $post['office_name'];
    $system_opening_balance['fk_office_id'] = $inserted_office_id;
    $system_opening_balance['month'] = $post['office_start_date'];

    $system_opening_balance_to_insert = $this->mergeWithHistoryFields('system_opening_balance', $system_opening_balance, false);

    $this->write_db->table('system_opening_balance')->insert( $system_opening_balance_to_insert);

    $error_messages['system_openning'] = $this->write_db->error();

    if ($this->write_db->transStatus() == false) {
      $this->write_db->transRollback();
     alert_error_message($error_messages);
      
    } else {
      $this->write_db->transCommit();
      // Append office to user session after creating an office to allow user see the office immediately the create it without the need to log out
      $hierarchy_offices = $this->session->hierarchy_offices;
      array_push($hierarchy_offices, ['office_name' => $post['office_name'], 'office_id' => $inserted_office_id, 'office_is_active' => 1]);
      $this->session->set(
        'hierarchy_offices',
        $hierarchy_offices
      );
      $flag = true;
      $message = "Office inserted successfully ";

    }

    return $this->response->setJSON(compact('message', 'flag'));
  }

  function getReportingOfficeContext($context_definition)
  {

    $reporting_context_definition_level = $context_definition->context_definition_level + 1;

    $reporting_context_definition = $this->read_db->table('context_definition')
    ->where(array('context_definition_level' => $reporting_context_definition_level)
    )->get()->getRow();

    return $reporting_context_definition;
  }

      /**
   * get_office_start_date_by_id
   * 
   * Get the start date of the office and first day of the start month by a given office Id
   * 
   * @author Nicodemus Karisa
   * @authored_date 14th June 2023
   * @reviewed_date None
   * 
   * @param int $office_id - Office Id
   * 
   * @return array - Returns 2 dates that is the first day of the office start month and the actual office start date with keys 
   * actual_start_date and month_start_date respectively
   */

   public function getOfficeStartDateById(int $office_id):array{

    $office_start_date = date('Y-m-01');
    $office_start_month = date('Y-m-01');

    $builder = $this->read_db->table('office');
    $builder->where(array('office_id' => $office_id));
    $office_start_date_obj = $builder->get();

    if($office_start_date_obj->getNumRows() > 0){
      $office_start_date = $office_start_date_obj->getRow()->office_start_date;
      $office_start_month = date('Y-m-01', strtotime($office_start_date));
    }

    $dates = ['actual_start_date' => $office_start_date, 'month_start_date' => $office_start_month];

    return $dates;
  }

}
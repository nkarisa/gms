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

}
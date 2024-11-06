<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ProjectModel;
class ProjectLibrary extends GrantsLibrary
{

    protected $table;
    protected $projectModel;

    function __construct()
    {
        parent::__construct();

        $this->projectModel = new ProjectModel();

        $this->table = 'project';
    }


    function detailListTableVisibleColumns(): array{
      return ['project_track_number','project_name','project_code','project_start_date','project_end_date','funder_name'];
    }

    function defaultFieldValue(): array{
      $default_field_values = [];
  
      if(!$this->session->system_admin){
        $builder = $this->read_db->table('funding_status');
        $builder->where(['fk_account_system_id'=>$this->session->user_account_system_id,
          'funding_status_is_active'=>1,'funding_status_is_available'=>1]);
          $default_field_values['fk_funding_status_id'] = $builder->get()->getRow()->funding_status_id;
      }   
  
      return $default_field_values;
    }

    function lookupValues(): array{
      $lookUpValues = parent::lookupValues();

      if(!$this->session->system_admin){
        $builder = $this->read_db->table('funding_status');
        $builder->select(['funding_status_id','funding_status_name']);
        
        $builder->where('fk_account_system_id', $this->session->user_account_system_id);
        $builder->where('funding_status_is_active', 1);
        $builder->where('funding_status_is_available', 1);

        $lookUpValues['funding_status'] = $builder->get()->getResultArray();
      }
      
      return $lookUpValues;
    }
   
}
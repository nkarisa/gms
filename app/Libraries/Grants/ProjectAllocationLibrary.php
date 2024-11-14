<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ProjectAllocationModel;
class ProjectAllocationLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ProjectAllocationModel();

        $this->table = 'project_allocation';
    }

    function deactivateDefaultAllocation($office_id, $suspension_status){
        $builder = $this->read_db->table('project');
        $builder->select(array('project_allocation_id'));
        $builder->where(array('fk_office_id' => $office_id, 'project_is_default' => 1));
        $builder->join('project_allocation','project_allocation.fk_project_id=project.project_id');
        $default_project_allocation_ids_obj = $builder->get();
  
        $default_project_allocation_ids = [];
  
        if($default_project_allocation_ids_obj->getNumRows() > 0){
          $default_project_allocation_ids_raw = $default_project_allocation_ids_obj->getResultArray();
  
          $default_project_allocation_ids = array_column($default_project_allocation_ids_raw, 'project_allocation_id');
  
          $data['project_allocation_is_active'] = $suspension_status;
          $builder = $this->write_db->table('project_allocation');
          $builder->whereIn('project_allocation_id', $default_project_allocation_ids);
          $builder->update( $data);
        }
  
     }
   
}
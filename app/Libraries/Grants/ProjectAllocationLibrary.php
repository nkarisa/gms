<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ProjectAllocationModel;
class ProjectAllocationLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $projectAllocationModel;

    function __construct()
    {
        parent::__construct();

        $this->projectAllocationModel = new ProjectAllocationModel();

        $this->table = 'project_allocation';
    }

  public function detachDetailTable(): bool{
    return true;
  }

  public function lookupValues(): array {
    $lookup_values = parent::lookupValues();
    $projectReadBuilder = $this->read_db->table('project');
    $projectAllocationReadBuilder = $this->read_db->table('project_allocation');

    if(!$this->session->system_admin){
      $projectReadBuilder->where(array('fk_account_system_id'=>$this->session->user_account_system_id));
      $projectReadBuilder->join('funder','funder.funder_id=project.fk_funder_id');
      $lookup_values['project'] = $projectReadBuilder->get()->getResultArray();
    }else{
      $lookup_values['project'] = $projectReadBuilder->get()->getResultArray();

    }

    $not_exist_string_condition = "AND fk_project_id = ".hash_id($this->id,'decode');
    $this->getUnusedLookupValues($projectAllocationReadBuilder , $lookup_values,'office','project_allocation',$not_exist_string_condition);

    return $lookup_values;
   }

  public function deactivateDefaultAllocation($office_id, $suspension_status): void{
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
     
    //  function detailListTableVisibleColumns(): array{
    //   return [
    //     'project_allocation_track_number',
    //     'office_name',
    //     'project_name',
    //     'project_allocation_extended_end_date',
    //     'project_allocation_created_date',
    //     'project_allocation_last_modified_date'
    //   ];
    //  }

    public function singleFormAddVisibleColumns(): array {
      return [
        'project_name',
        'office_name'
      ];
     }

     public function detailListTableVisibleColumns(): array{
        return [
          'project_allocation_track_number',
          'project_name',
          'office_name',
          'project_allocation_is_active',
          'project_allocation_extended_end_date',
          'project_allocation_created_date'
        ];
     }

     public function editVisibleColumns(): array {
      return [
        'project_name',
        'project_allocation_is_active',
        'office_name',
        'project_allocation_extended_end_date'
      ];
     }

    public function transactionValidateDuplicatesColumns(): array{
      return ['fk_project_id','fk_office_id'];
    }

    function multiSelectField(): string {
      return "office";
    }
}
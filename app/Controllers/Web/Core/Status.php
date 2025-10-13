<?php

namespace App\Controllers\Web\Core;

use App\Controllers\Web\WebController;
use App\Libraries\Core\OfficeLibrary;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Status extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function masterTable(){
        $builder = $this->read_db->table('status');
        $builder->select(array('status_track_number','status_name',
        'status_button_label','status_decline_button_label','status_signatory_label','status_approval_sequence',
        'status_created_date','CONCAT(user_firstname," ", user_lastname) as status_created_by',
        'approval_flow_name','approval_flow_id','approve_item_name'));
        $builder->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
        $builder->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
        $builder->join('user','user.user_id=status.status_created_by');
        $builder->where(array('status_id'=>hash_id($this->id,'decode')));
        $result = $builder->get()->getRowArray();
    
        $approval_flow_id = $result['approval_flow_id'];
        unset($result['approval_flow_id']);
    
        $result['approval_flow_name'] = '<a href="'.base_url().'approval_flow/view/'.hash_id($approval_flow_id).'">'.$result['approval_flow_name'].'</a>';
    
        return $result;
      }

    function result($id = '', $parentId = null){
        $result = parent::result($id, $parentId);
        $statusRoleLibrary = new \App\Libraries\Core\StatusRoleLibrary();
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $approvalExemptionLibrary = new \App\Libraries\Core\ApprovalExemptionLibrary();

        // $this->load->model('approval_exemption_model');
    
        if($this->action == 'view'){
        //   $this->load->model('office_model');
    
          $master_table = $this->masterTable();
    
          $table_name = $master_table['approve_item_name'];
          unset($master_table['approve_item_name']);
    
          $result['header'] = $master_table;
    
          $result['detail']['status_role']['columns'] = $statusRoleLibrary->detailListTableVisibleColumns();
          $result['detail']['status_role']['has_details_table'] = true; 
          $result['detail']['status_role']['has_details_listing'] = false;
          $result['detail']['status_role']['is_multi_row'] = false;
          $result['detail']['status_role']['show_add_button'] = true;
    
          if($officeLibrary->checkIfTableHasRelationshipWithOffice($table_name)){ // Only show exemption section when the record type has an office relationship 
            $result['detail']['approval_exemption']['columns'] = $approvalExemptionLibrary->listTableVisibleColumns();
            $result['detail']['approval_exemption']['has_details_table'] = true; 
            $result['detail']['approval_exemption']['has_details_listing'] = false;
            $result['detail']['approval_exemption']['is_multi_row'] = false;
            $result['detail']['approval_exemption']['show_add_button'] = true;
          }
          
    
          return $result;
    
        }else{
          return $result;
        }
      }

    function checkIfStatusIsStraightJump($status_id){
      $statusLibrary = new \App\Libraries\Core\StatusLibrary();
      $is_straight_jump = 0;

      $statusBuilder = $this->read_db->table('status');

      $statusBuilder->select(array('status_id','approve_item_name','status_approval_direction'));
      $statusBuilder->where(array('status_id'=>$status_id));
      $statusBuilder->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
      $statusBuilder->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
      $status_approval_direction_obj = $statusBuilder->get()->getRow();

      $initial_item_status = $statusLibrary->initialItemStatus($status_approval_direction_obj->approve_item_name);
      $max_approval_ids = $statusLibrary->getMaxApprovalStatusId($status_approval_direction_obj->approve_item_name);
      $status_id = $status_approval_direction_obj->status_id;
      $status_approval_direction = $status_approval_direction_obj->status_approval_direction;

      if(($status_approval_direction == 1 || $status_approval_direction == 0) && $initial_item_status != $status_id){
        $is_straight_jump = 1;
      }

      $response = ['is_straight_jump' => $is_straight_jump, 'initial_status' => $initial_item_status == $status_id, 'final_status' => in_array($status_id, $max_approval_ids )];
      
      return $this->response->setJSON($response);
  }
}

<?php

namespace App\Controllers\Web\Core;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Office extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function result($id = '', $parentTable = null){

        $result = parent::result($id, $parentTable);
        
        if($this->action == 'list'){
            $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
            $all_offices = $officeLibrary->getAllAccountSystemOffices($this->session->user_account_system_id, 2);

            for($i = 0; $i < count($all_offices); $i++){
                if($all_offices[$i]['context_definition_name'] == 'cluster'){
                $result['cluster_offices'][$i] = $all_offices[$i];
                }
            }
        }elseif ($this->action == 'singleFormAdd') {
            $countryCurrencyLibrary = new \App\Libraries\Grants\CountryCurrencyLibrary();
            $result['country_currency_id']=$countryCurrencyLibrary->getCountryCurrencyId();
        }elseif($this->action == 'edit'){
      
            $office_id = hash_id($this->id,'decode');
            $officeLibrary = new \App\Libraries\Core\OfficeLibrary();

            $result['office_record_to_edit']=$officeLibrary->getEditOfficeRecords($office_id);
            $result['defination_contexts']=$officeLibrary->retrieveIdsAndNamesRecords(['context_definition_id','context_definition_name'],'context_definition');
            $result['account_systems']=$officeLibrary->retrieveIdsAndNamesRecords(['account_system_id','account_system_name'],'account_system');
            $result['country_currency']=$officeLibrary->retrieveIdsAndNamesRecords(['country_currency_id','country_currency_name'],'country_currency');
      
          }

        return $result;
    }

    /**
   * Bulky update for FCPs to a cluster
   * This method updates fcps to a cluster in bulk
   * @return ResponseInterface 
   * @Author :Livingstone Onduso
   * @Date: 08/21/2022
   */

  function massUpdateForFcps(): ResponseInterface{

    $post = $this->request->getPost();
    $fcp_office_ids = $post['office_ids'];
    $cluster_office_id = $post['cluster_office_id'];

    $this->write_db->transBegin();

    $data['fk_context_cluster_id'] = $this->read_db->table('context_cluster')
    ->where(array('fk_office_id'=>$cluster_office_id))
    ->get()->getRow()->context_cluster_id;
    
    $builder = $this->write_db->table("context_center");
    $builder->whereIn('fk_office_id',$fcp_office_ids);
    $builder->update($data);
    if ($this->write_db->transStatus() == false) {
      $this->write_db->transRollback();
      $message=0;
    } else {
      $this->write_db->transCommit();
      $message=1;
    }

   return $this->response->setJSON(compact('message'));
  }

  function responsesForContextDefinition(): ResponseInterface
  {

    $post = $this->request->getPost();

    /** Remove this */
    $context_definition = $this->read_db->table("context_definition")
    ->where(array('context_definition_id' => $post['context_definition_id'])
    )->get()->getRow();


    $reporting_context_definition_level = 6;

    if ($context_definition->context_definition_level < 6) {
      $reporting_context_definition_level = $context_definition->context_definition_level + 1;
    }

    $reporting_context_definition = $this->read_db->table('context_definition')
    ->where(array('context_definition_level' => $reporting_context_definition_level)
    )->get()->getRow();

    $reporting_context_definition_table = 'context_' . $reporting_context_definition->context_definition_name;

    $builder = $this->read_db->table($reporting_context_definition_table);
    $builder->select(array($reporting_context_definition_table . '_id', $reporting_context_definition_table . '_name'));
    $builder->join('office', 'office.office_id=' . $reporting_context_definition_table . '.fk_office_id');

    if (!$this->session->system_admin) {
      $builder->join('account_system', 'account_system.account_system_id=office.fk_account_system_id');
      $builder->where(array('account_system_code' => $this->session->user_account_system_code));
    }

    $builder->where(array('office_is_active' => 1));
    $result = $builder->get()->getResultArray();

    $office_contexts_combine = combine_name_with_ids($result, $reporting_context_definition_table . '_id', $reporting_context_definition_table . '_name');
    $office_context = $this->libs->selectField('office_context', $office_contexts_combine);

    return $this->response->setJSON(array('office_context' => $office_context));
  }

  function suspendOffice(){
    $post = $this->request->getPost();
    $office_id = $post['office_id'];
    $suspension_status = $post['suspension_status'];
    $flag = false;
    $message = get_phrase('office_suspension_failed');

    $this->write_db->transStart();
    $status_to_update = $suspension_status == 0 ? 1 : 0;
    
    $data['office_is_suspended'] = $status_to_update;
    $builder = $this->write_db->table('office');
    $builder->where(array('office_id' => $office_id));
    $builder->update($data);

    $projectAllocationLibrary = new \App\Libraries\Grants\ProjectAllocationLibrary();
    $projectAllocationLibrary->deactivateDefaultAllocation($office_id, $suspension_status);

    $this->write_db->transComplete();
      
    if($this->write_db->transStatus() == true){
      $flag = true;
      $message = get_phrase('office_suspension_success');
    }

    return $this->response->setJSON(compact('flag','message'));
  }
}

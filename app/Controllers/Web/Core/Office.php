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
        }elseif ($this->action == 'single_form_add') {
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
}

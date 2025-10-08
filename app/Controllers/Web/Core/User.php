<?php

namespace App\Controllers\Web\Core;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class User extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function result($id = "", $parentTable = null){
        $result = parent::result($id);

        $uniqueIdentifierLibrary = new \App\Libraries\Core\UniqueIdentifierLibrary();
        $userLibrary = new \App\Libraries\Core\UserLibrary();
        $grantsLibrary = new \App\Libraries\System\GrantsLibrary();
        $departmentLibrary = new \App\Libraries\Core\DepartmentLibrary();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $roleLibrary = new \App\Libraries\Core\RoleLibrary();
        $designationLibrary = new \App\Libraries\Core\DesignationLibrary();
    
        $user_info = [];
    
        if($this->action == 'view' || $this->action == 'edit'){
          $user_info = $userLibrary->getUserInfo(['user_id' =>hash_id($this->id,'decode')]);
          $result['valid_user_unique_identifier'] = $uniqueIdentifierLibrary->validUserUniqueIdentifier(hash_id($this->id,'decode'));
        }
        
    
        if ($this->action == 'view') {
          $result['user_info'] = $user_info;
          $user_id = hash_id($this->id, 'decode');
          $status_data = $grantsLibrary->actionButtonData($this->controller, $user_info['account_system_id']);
          
          $user_id = $user_info['user_id'];
          $role_id = $user_info['role_id'];
          $role_ids = array_keys($userLibrary->userRoleIds($user_id));
          $departments = array_column($departmentLibrary->retrieveUserDepartment($user_id), 'department_name');
          
          $result['status_data'] = $status_data;
          $result['user_info']['role_name'] = implode(",", array_values($userLibrary->getUserRoles($user_id)));
          $result['user_info']['department'] = implode(",", $departments);
          $result['user_context_id'] = $user_info['context_definition_id'];
    
          $result['role_permission'] = $userLibrary->getUserPermissions($role_ids);
          $result['user_hierarchy_offices'] = $userLibrary->getUserContextOffices($user_id);
          $result['approval_workflow_assignments'] = $statusLibrary->getApprovalAssignments($role_id);
    
          $user_unique_identifier_uploads = $uniqueIdentifierLibrary->userUniqueIdentifierUploads($user_id);
    
          $builder = $this->read_db->table('user');
          $builder->select(array('user_personal_data_consent_content'));
          $builder->where(array('user_id' => $user_id));
          $result['data_consent'] = $builder->get()->getRow()->user_personal_data_consent_content;
    
          $result['user_identity_documents'] = $user_unique_identifier_uploads;
        } elseif ($this->action == 'singleFormAdd') {
          $result['all_context_offices'] = $officeLibrary->getAllOfficeContext();
        } elseif ($this->action == 'edit') {
          //Get all info for user
          $result['edit_user_info']['account_system_identifier'] = $uniqueIdentifierLibrary->getAccountSystemUniqueIdentifier($user_info['account_system_id']);
          $result['edit_user_info'] = $user_info;
    
          //Get user office
          $user_id = $user_info['user_id'];
          $context_id = $user_info['context_definition_id'];
          
          $user_office = $officeLibrary->userOffice($context_id, $user_id);
          $result['edit_user_info']['user_office'] = $user_office;
          $result['account_system_id'] = $user_info['account_system_id'];
    
          //Get user department
          $result['edit_user_info']['departments'] = $departmentLibrary->retrieveUserDepartment($user_id);
    
          //Get all other user related context offices, departments
          $result['all_offices'] = $officeLibrary->getOffices($context_id, 0);
          $result['all_departments'] = $departmentLibrary->retrieveDepartments($context_id);
    
          //Get context definitions e.g. center, fcp
          $result['all_context_offices'] = $officeLibrary->getAllOfficeContext();
    
          //Get user_user_roles
          $result['edit_user_info']['user_seconday_roles'] = $userLibrary->userRoleIdsWithExpiryDates($user_id);
          $result['edit_user_info']['user_primary_role'] = $userLibrary->userRoles($user_id);
              
          //Get all other roles of the user context
          $result['all_context_roles'] = $roleLibrary->retrieveRoles($context_id);
    
          //Designition 
          $result['edit_user_info']['user_designation'] = $userLibrary->userDesignation($user_id, $context_id);
    
          //All other designitions
          $result['all_designations'] = $designationLibrary->retrieveDesignations($context_id);
        } 
    
        return $result;
    }

    public function checkIfEmailIsUsed()
    {
  
      $valid_email = true;
      $post = $this->request->getPost();

      $builder = $this->read_db->table('user');
      $builder->oRwhere(array('user_email' => strtolower(trim($post['user_email']))));
      $count_of_users_with_email = $builder->get()->getNumRows();
  
      if ($count_of_users_with_email > 0) {
        $valid_email = false;
      }
  
      return $this->response->setJSON(['valid_email' => $valid_email]);
    }

    function getOfficesByAjax($context_id){
      $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
      $offices = $officeLibrary->getOffices($context_id, 0);
      return $this->response->setJSON($offices);
    }

    function retrieveDepartmentsByAjax($context_office){
      $departmentLibrary = new \App\Libraries\Core\DepartmentLibrary();
      $departments = $departmentLibrary->retrieveDepartments($context_office);
      // log_message('error', json_encode($departments));
      return $this->response->setJSON($departments);
    }

    function retrieveRolesByAjax($context_office){
      $roleLibrary = new \App\Libraries\Core\RoleLibrary();
      $roles = $roleLibrary->retrieveRoles($context_office);
      return $this->response->setJSON($roles);
    }

    function retrieveDesignationsByAjax($context_office){
      $designationLibrary = new \App\Libraries\Core\DesignationLibrary();
      $designations = $designationLibrary->retrieveDesignations($context_office);
      return $this->response->setJSON($designations);
    }

    function getAccountSystemsByAjax(){
      $accountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();
      $account_systems = $accountSystemLibrary->getAccountSystems();
      
      $ids = array_column($account_systems, 'account_system_id');
      $names = array_column($account_systems, 'account_system_name');

      return $this->response->setJSON(array_combine($ids, $names));
    }

    function getCountryCurrencyByAjax(){
      $countryCurrencyLibrary = new \App\Libraries\Grants\CountryCurrencyLibrary();
      $country_currencies = $countryCurrencyLibrary->getCountryCurrency();
      return $this->response->setJSON($country_currencies);
    }

}

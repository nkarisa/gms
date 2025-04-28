<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\RoleModel;
class RoleLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $roleModel;

    function __construct()
    {
        parent::__construct();

        $this->roleModel = new RoleModel();

        $this->table = 'role';
    }

    function retrieveRoles($context_definition){

        $builder = $this->read_db->table('role');

        $builder->select(array('role_id', 'role_name'));
        if(!$this->session->system_admin){
          $builder->where(['fk_account_system_id'=>$this->session->user_account_system_id]);
    
        }
        $builder->where(array('role_is_active'=>1,  'fk_context_definition_id'=>$context_definition));
        $roles = $builder->get()->getResultArray();
    
        $roles_ids=array_column($roles,'role_id');
        $roles_names=array_column($roles,'role_name');
    
        $roles_ids_and_names=array_combine($roles_ids,$roles_names);
    
        return $roles_ids_and_names;
       }
   
       function detailTables(): array {
        return ['role_permission','role_group_association'];
       }


    function singleFormAddVisibleColumns(): array {
      $fields = [
        'role_name',
        'role_shortname',
        'role_description',
        'role_is_active',
        'context_definition_name',
        'account_system_name'
      ];

      if($this->session->system_admin){
        $fields = [...$fields, 'role_is_new_status_default','role_is_department_strict'];
      }

      return $fields;
    }

    function checkRoleHasUsers($roleId){
      $userReadBuilder = $this->read_db->table('user');

      $userReadBuilder->where('fk_role_id', $roleId);
      $countUsersUsingRole = $userReadBuilder->countAllResults();
      
      return $countUsersUsingRole > 0 ? true : false;
    }

    function editVisibleColumns(): array {
      $fields = [...$this->singleFormAddVisibleColumns()];

      // Check if role has user associated to it
      $roleHasUsers = $this->checkRoleHasUsers(hash_id($this->id, 'decode'));
      
      if($roleHasUsers == true){
        unset($fields[array_search('role_is_active', $fields)]);
      }
      
      return $fields;
    }

    function lookupValues(): array
    {
        $lookup_values = parent::lookupValues();
        $contextDefinitionLibrary = new \App\Libraries\Core\ContextDefinitionLibrary();

        if(!$this->session->system_admin){
            $contextDefinitions = $contextDefinitionLibrary->contextDefinitions();
            $context_definition_level = $this->session->context_definition['context_definition_level'];

            $lookup_values['context_definition'] = array_filter($contextDefinitions, function ($contextDefinition) use($context_definition_level) {
                if($contextDefinition['context_definition_level'] <= $context_definition_level){
                    return $contextDefinition;
                }
            });

            $accountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();
            $getAccountSystems = $accountSystemLibrary->getAccountSystems();
            
            $lookup_values['account_system'] = array_filter($getAccountSystems, function($accountSystem){
                $user_account_system_id = $this->session->user_account_system_id;
                if($accountSystem->account_system_id == $user_account_system_id){
                    return $accountSystem;
                }
            });
        }

        return $lookup_values;
    }

  
  // function showListEditActionDependancyData(array $roles): array{
  //     $userReadBuilder = $this->read_db->table('user');
  //     $role_ids = array_column($roles, 'role_id');

  //     $userReadBuilder->select(['fk_role_id as role_id','COUNT(*) as user_count']);
  //     $userReadBuilder->whereIn('fk_role_id', $role_ids);
  //     $userReadBuilder->groupBy('fk_role_id');
  //     $rolesUserCountObj = $userReadBuilder->get();

  //     $rolesUserCount = [];
  //     if($rolesUserCountObj->getNumRows() > 0){
  //       $rolesUserCount = $rolesUserCountObj->getResultArray();
  //     }

  //     $roleIdsUsed = array_column($rolesUserCount, 'role_id');

  //     return compact('roleIdsUsed');
  // } 

  //   function showListEditAction(array $row, array $dependancyData = []): bool{

  //     $roleIdsUsed = $dependancyData['roleIdsUsed'];
  //     if(in_array($row['role_id'], $roleIdsUsed)){
  //       return false;
  //     }

  //     return true;
  //   }
}
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

        }

        return $lookup_values;
    }

  function actionAfterEdit(array $postData, int $approveId, int $itemId): bool {
    $rolePermissionWriteBuilder = $this->write_db->table('role_permission');

    if($postData['role_is_active'] == 0){
      // Disable all role permissions
      $rolePermissionWriteBuilder->where('fk_role_id', $itemId);
      $rolePermissionWriteBuilder->update(['role_permission_is_active' => 0]);
    }else{
      // Enable all role permissions
      $rolePermissionWriteBuilder->where('fk_role_id', $itemId);
      $rolePermissionWriteBuilder->update(['role_permission_is_active' => 1]);
    }
    return true;
  }
}
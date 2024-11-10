<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\RoleModel;
class RoleLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new RoleModel();

        $this->table = 'core';
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
}
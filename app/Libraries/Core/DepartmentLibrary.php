<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\DepartmentModel;
class DepartmentLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new DepartmentModel();

        $this->table = 'core';
    }


    function retrieveUserDepartment($user_id){
        $builder = $this->read_db->table('department');
        $builder->select(array('department_id','department_name'));
        $builder->join('department_user','department_user.fk_department_id=department.department_id');
        $builder->where(array('department_is_active'=>1,'fk_user_id'=>$user_id));
        $result = $builder->get()->getResultArray();
    
        return $result;
       }
   

       function retrieveDepartments($context_definition){
        $builder = $this->read_db->table('department');
        $builder->select(array('department_id', 'department_name'));
        $builder->where(array('department_is_active'=>1,'fk_context_definition_id'=>$context_definition));
        $departments = $builder->get()->getResultArray();
    
        $departments_ids=array_column($departments,'department_id');
    
        $departments_names=array_column($departments,'department_name');
    
        $departments_ids_and_names=array_combine($departments_ids,$departments_names);
    
        return $departments_ids_and_names;
       }
}
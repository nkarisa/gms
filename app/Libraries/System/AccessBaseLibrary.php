<?php 

namespace App\Libraries\System;

/**
* The Access_base class is part of the system APIs that controls the access permission for users.
*
* @author Nicodemus Karisa
* @package Grants Management System
* @copyright Compassion International Kenya
* @license https://compassion-africa.org/lisences.html
*
*/
class AccessBaseLibrary{

    /**
    * control_column_visibility
    * 
    * This method checks if a field/column has permission to with a create label
    * @param string $table - Selected table
    * @param array $visible_columns : Array of visible/ selected columns/ fields
    * @param string $permission_label : Can be create, update or read
    * 
    * @return array
    */
    function controlColumnVisibility(String $table, Array $visible_columns, String $permission_label = 'create'): Array{
        $controlled_visible_columns = array();

        foreach($visible_columns as $column){
        if($this->checkRoleHasFieldPermission($table,$permission_label,$column)){
            $controlled_visible_columns[] = $column;
        }  
        }

        return $controlled_visible_columns;
    }

    /**
     * check_role_has_field_permission
     * 
     * This method is a wrapper of the user_model check_role_has_field_permission method.
     * It helps to check if the logged user has permission to acccess a controlled field
     * Any field that has been flagged in the permission table is referred to as a controlled field
     * 
     * @param string $table - Selected table
     * @param string $permission_label - Can be 1 or 2
     * @param string $column - Selected column
     * @return bool
     */
    function checkRoleHasFieldPermission(String $table, String $permission_label,String $column):bool{
        $userLibrary = new \App\Libraries\Core\UserLibrary();  // Instantiate UserLibrary here
        return $userLibrary->checkRoleHasFieldPermission(
        $table, $permission_label, $column
        );
    }

}
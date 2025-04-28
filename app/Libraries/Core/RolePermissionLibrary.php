<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\RolePermissionModel;
class RolePermissionLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new RolePermissionModel();

        $this->table = 'core';
    }

    function singleFormAddVisibleColumns(): array {
        return ['role_name','permission_name'];
    }

    function detailListTableVisibleColumns(): array{
        return array('role_permission_track_number','role_permission_is_active',
        'role_name','permission_name','permission_description');
    }

    function multiSelectField(): string {
        return 'permission';
    }

    function editVisibleColumns(): array {
        return [
            'permission_name',
            'role_name',
            'role_permission_is_active'
        ];
    }

    function lookUpValues():array 
  {
      $lookup_values = parent::lookUpValues();
      $permissionReadBuilder = $this->read_db->table('permission');

      if(!$this->session->system_admin){
          $permissionReadBuilder->select(array('permission_id','permission_name'));
          $permissionReadBuilder->where(array('permission_is_global' => 0));
          $permissionReadBuilder->where('NOT EXISTS (SELECT * FROM role_permission WHERE role_permission.fk_permission_id=permission.permission_id AND fk_role_id = '.hash_id($this->id,'decode').')','',FALSE);
          $lookup_values['permission'] = $permissionReadBuilder->get()->getResultArray();
      }

      return $lookup_values;
  }
   
}
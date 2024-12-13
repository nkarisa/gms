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
   
}
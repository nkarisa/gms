<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\RoleGroupModel;
class RoleGroupLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new RoleGroupModel();

        $this->table = 'role_group';
    }

    function detailTables(): array {
        return [
            'permission_template'
        ];
    }

    function listTableVisibleColumns(): array {
        return [
            'role_group_track_number',
            'role_group_name',
            'role_group_is_active',
            'account_system_name',
            'context_definition_name',
            'role_group_created_date'
        ];
    }
   
}
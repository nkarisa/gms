<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\RoleGroupAssociationModel;
class RoleGroupAssociationLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new RoleGroupAssociationModel();

        $this->table = 'core';
    }


    function detailListTableVisibleColumns(): array{
        return ['role_group_association_track_number','role_group_name','role_name','role_group_association_is_active','role_group_association_created_date'];
    }
   
}
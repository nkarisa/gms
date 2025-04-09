<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\RoleGroupAssociationModel;
class RoleGroupAssociationLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
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
   
    public function singleFormAddVisibleColumns(): array
    {
        return [
            'role_name',
            'role_group_name'
        ];
    }

    function multiSelectField(): string{
        return 'role';
    }

    public function lookupValues(): array
    {
        $lookup_values = parent::lookupValues();

        if(!$this->session->system_admin){
            $readBuilder = $this->read_db->table('role_group');
            $readBuilder->select(array('role_group_id','role_group_name'));
            $readBuilder->whereIn('fk_account_system_id',[1,$this->session->user_account_system_id]);
            $lookup_values['role_group'] = $readBuilder->get()->getResultArray();
        }

        return $lookup_values;
    }
}
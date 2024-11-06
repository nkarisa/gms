<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\StatusRoleModel;
class StatusRoleLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new StatusRoleModel();

        $this->table = 'core';
    }


    // function detailListQuery()
    // {
    //     $this->read_db->join('role', 'role.role_id=status_role.fk_role_id');
       
    //     $this->read_db->join('status', 'status.status_id=status_role.status_role_status_id');
    //     $this->read_db->where(array('status_role_status_id' => hash_id($this->id, 'decode')));
    //     $result = $this->read_db->get('status_role')->result_array();

    //     return $result;
    // }

    function list(array $listSelectColumns, string $parentId = null, string $parentTable = null): array
    {
        
        $builder = $this->read_db->table('status_role');
        $builder->select($listSelectColumns);
        $builder->join('role', 'role.role_id=status_role.fk_role_id');
        $builder->join('status', 'status.status_id=status_role.status_role_status_id');
        $builder->where('status_role_status_id', hash_id($parentId, 'decode'));
        $results = $builder->get()->getResultArray();

        return ['results' => $results];
    }

    function singleFormAddVisibleColumns(): array
    {
        return ['role_name'];
    }


    public function lookupValues(): array
    {
        // Call the parent lookup_values method
        $lookup_values = parent::lookupValues();

        // Decode the status_id from the hashed ID
        $status_id = hash_id($this->id, 'decode');

        // Handle the 'edit' action case
        if ($this->action === 'edit') {
            $status_role_id = hash_id($this->id, 'decode');
            $statusRoleQuery = $this->read_db->table('status_role')
                ->where('status_role_id', $status_role_id)
                ->get();
            $status_id = $statusRoleQuery->getRow()->status_role_status_id;
        }

        // Get the approve_item_name related to the given status_id
        $approveItemQuery = $this->read_db->table('approve_item')
            ->select('approve_item_name')
            ->join('approval_flow', 'approval_flow.fk_approve_item_id = approve_item.approve_item_id')
            ->join('status', 'status.fk_approval_flow_id = approval_flow.approval_flow_id')
            ->where('status_id', $status_id)
            ->get();
        $approve_item_name = $approveItemQuery->getRow()->approve_item_name;

        // Query to get role information with conditions
        $roleQuery = $this->read_db->table('role')
            ->select('role_id, role_name')
            ->where("NOT EXISTS (SELECT * FROM status_role WHERE status_role.fk_role_id = role.role_id AND status_role_status_id = " . $status_id . ")", null, false);

        if (!$this->session->get('system_admin')) {
            $roleQuery->where('fk_account_system_id', $this->session->get('user_account_system_id'))
                ->where('role_is_active', 1)
                ->whereNotIn('role_id', $this->session->get('role_ids'));

            // Additional filtering for non-edit action
            if ($this->action !== 'edit') {
                $roleQuery->where('menu_derivative_controller', $approve_item_name)
                    ->where('role_permission_is_active', 1)
                    ->join('role_permission', 'role_permission.fk_role_id = role.role_id')
                    ->join('permission', 'permission.permission_id = role_permission.fk_permission_id')
                    ->join('menu', 'menu.menu_id = permission.fk_menu_id');
            }
        }

        $lookup_values['role'] = $roleQuery->get()->getResultArray();

        return $lookup_values;
    }
   
}
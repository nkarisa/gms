<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\PermissionModel;

class PermissionLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $permissionModel;

    function __construct()
    {
        parent::__construct();

        $this->permissionModel = new PermissionModel();

        $this->table = 'permission';
    }

    public function createPermission($permissionData){
        $permissionBuilder = $this->write_db->table('permission');

        // Check if the permission exists
        $permissionBuilder->where(['fk_menu_id' => $permissionData['menu_id']]);
        $countMenuPermissions = $permissionBuilder->countAllResults();

        if($countMenuPermissions == 0){
            // Get Permission Labels 
            $permissionLabelBuilder = $this->write_db->table('permission_label');
            $permissionLabelObj = $permissionLabelBuilder->get();

            if($permissionLabelObj->getNumRows() > 0){

                $permissionLabels = $permissionLabelObj->getResultArray();

                $cnt = 0;
                foreach($permissionLabels as $permissionLabel){
                    $itemNameAndTrackNumber = $this->generateItemTrackNumberAndName('permission');
                    $permissionName = ucfirst($permissionLabel['permission_label_name'] . ' ' . str_replace('_',' ',$permissionData['table_name']));

                    $permissionInsertData[$cnt]['permission_track_number'] = $itemNameAndTrackNumber['permission_track_number'];
                    $permissionInsertData[$cnt]['permission_name'] = $permissionName;
                    $permissionInsertData[$cnt]['permission_description'] = $permissionName;
                    $permissionInsertData[$cnt]['permission_is_active'] = 1;
                    $permissionInsertData[$cnt]['fk_permission_label_id'] = $permissionLabel['permission_label_id'];
                    $permissionInsertData[$cnt]['permission_type'] = 1; // Page Access
                    $permissionInsertData[$cnt]['permission_field'] = '';
                    $permissionInsertData[$cnt]['permission_is_global'] = 1;
                    $permissionInsertData[$cnt]['fk_menu_id'] = $permissionData['menu_id'];

                    $permissionInsertData[$cnt]['permission_created_date'] = date('Y-m-d');
                    $permissionInsertData[$cnt]['permission_created_by'] = $this->session->user_id;
                    $permissionInsertData[$cnt]['permission_last_modified_date'] = date('Y-m-d h:i:s');
                    $permissionInsertData[$cnt]['permission_last_modified_by'] = $this->session->user_id;

                    $permissionInsertData[$cnt]['fk_approval_id'] = NULL;
                    $permissionInsertData[$cnt]['fk_status_id'] = NULL;

                    $cnt++;
                }

                if(count($permissionInsertData) > 0){
                    $permissionBuilder->insertBatch($permissionInsertData);
                }

            }
        }

    }

}
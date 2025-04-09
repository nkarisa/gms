<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\PermissionTemplateModel;
class PermissionTemplateLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new PermissionTemplateModel();

        $this->table = 'permission_template';
    }

    function actionBeforeInsert($post_array): array
    {
        $menuLibrary = new \App\Libraries\Core\MenuLibrary();
        $menus = $menuLibrary->getMenuItems();
        $menuLibrary->upsertMenu($menus);
  
        return $post_array;
    }


    public function singleFormAddVisibleColumns(): array
    {
        return ['role_group_name','permission_name'];
    }

    function editVisibleColumns(): array{
        return ['role_group_name','permission_name','permission_template_is_active'];
    }

    public function detailListTableVisibleColumns(): array
    {
        return ['permission_template_track_number',
        'role_group_name','permission_name',
        'permission_template_is_active',
        'permission_template_created_date',
        'permission_template_last_modified_date'];
    }

    function multiSelectField(): string{
        return 'permission';
    }

    function lookupValues(): array
    {
        $lookup_values = parent::lookupValues();

        if(!$this->session->system_admin){
            $readBuilder = $this->read_db->table('permission');
            $readBuilder->select(array('permission_id','permission_name'));
            $readBuilder->where(array('permission_is_global'=>0));
            $readBuilder->where('NOT EXISTS (SELECT * FROM permission_template WHERE permission_template.fk_permission_id=permission.permission_id AND fk_role_group_id = '.hash_id($this->id,'decode').')','',FALSE);
            $lookup_values['permission'] = $readBuilder->get()->getResultArray();
        }

        return $lookup_values;
    }
   
}
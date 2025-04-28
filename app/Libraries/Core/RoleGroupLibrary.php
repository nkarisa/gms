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

    function lookupValues(): array
    {
        $lookup_values = parent::lookupValues();
        $contextDefinitionLibrary = new \App\Libraries\Core\ContextDefinitionLibrary();

        if(!$this->session->system_admin){
            $contextDefinitions = $contextDefinitionLibrary->contextDefinitions();
            $context_definition_level = $this->session->context_definition['context_definition_level'];

            $lookup_values['context_definition'] = array_filter($contextDefinitions, function ($contextDefinition) use($context_definition_level) {
                if($contextDefinition['context_definition_level'] <= $context_definition_level){
                    return $contextDefinition;
                }
            });
        }

        return $lookup_values;
    }

    function actionAfterEdit(array $postData, int $approveId, int $itemId): bool {
        $permissionTemplateWriteBuilder = $this->read_db->table('permission_template');
        if(!$postData['role_group_is_active']){
            // Deactivate all permission templates
            $permissionTemplateWriteBuilder->where(['fk_role_group_id' => $itemId]);
            $permissionTemplateWriteBuilder->update(['permission_template_is_active' => 0]);
        }else{
            // Enable all permission templates
            $permissionTemplateWriteBuilder->where(['fk_role_group_id' => $itemId]);
            $permissionTemplateWriteBuilder->update(['permission_template_is_active' => 1]);
        }

        return true;
    }
   
}
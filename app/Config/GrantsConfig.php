<?php 

// app/Config/CustomConfig.php
namespace Config;

use CodeIgniter\Config\BaseConfig;

class GrantsConfig extends BaseConfig
{
    public $preventUsingGlobalPermissionsByNonAdmins = true; // or false, depending on your default value
    public $methodToAttachPermissionToRole = 'both'; // direct, role_group, both
    public $defaultLaunchPage = 'Dashboard'; 
    public $systemName = "Grants Managememt System";
    public $modules = ['system','core','grants'];
    public $maxPriorityMenuItems = 10;
    public $tableThatDontRequireHistoryFields = ['status', 'approve_item', 'approval_flow', 'ci_sessions'];
    public $tablesNotRequiredInMenu = ['menu','ci_sessions'];
    public $extraMenuItemColumns = 5; // Number of columns to display extra menu items
    public $maxCountOfFavoritesMenuItems = 10; //
    public $master_table_columns = 2;
    public $use_select2_plugin = true;
    public $attachment_table_name = "attachment";
    public $attachment_key_column = "attachment_url";
    public $s3_region = 'eu-west-1';
    public $s3_bucket_name = "fms-bucket";
    public $max_count_of_favorites_menu_items = 10;
}

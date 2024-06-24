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

}

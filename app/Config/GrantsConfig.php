<?php 

// app/Config/CustomConfig.php
namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Summary of GrantsConfig
 * 
 * @package Config
 * @author Nicodemus Karisa Mwambire
 * @copyright 2024 Safina Solution
 * @version 3.0.0
 * @license https://safina-solutions.com/licenses/MIT MIT License
 * @file GrantsConfig.php
 * @since 2024-11-11
 * @see https://codeigniter4.github.io/CodeIgniter4/
 * 
 * This class provides configuration options for the Grants Management System.
 * Unlike the ContextConfig, this class is intended to be used for managing grant-related configurations.
 * 
 * Note that, the configuration values can be changed directly in the settings database table and its
 * advisable to use service("settings")->get('GrantsConfig.<<ConfigKey>>')
 * 
 */

class GrantsConfig extends BaseConfig
{
    public $preventUsingGlobalPermissionsByNonAdmins = true; // or false, depending on your default value
    public $methodToAttachPermissionToRole = 'both'; // direct, role_group, both
    public $defaultLaunchPage = 'Dashboard'; 
    public $systemName = "Safina Grants Managememt System";
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
    public $s3_bucket_name = "safina-version-2-fcp-fms-testing-environment";
    public $max_count_of_favorites_menu_items = 10;
    public $maintenance_mode = false;
    public $allow_skipping_of_cheque_leaves = true;
    public $use_voucher_type_abbreviation = true;
    public $use_default_logo = false;
    public $append_office_code_to_voucher_number = true;
    public $toggle_accounts_by_allocation = true; 
    public $fy_year_digits = 2;
    public $fy_year_reference = 'next';
    public $show_all_budget_tags=true;
    public $size_in_months_of_a_budget_review_period=3;
    public $cheque_cancel_and_resuse_limit = 4;
    public $review_last_quarter_after_mark_for_review=true;
    public $upload_files_to_s3=true;
    public $only_combined_center_financial_reports = false;
    public $submit_mfr_without_controls=false;
    public $funding_balance_report_aggregate_method = "receipt";
    public $allow_a_bank_to_be_linked_to_many_projects = true;
    public $dropTransactingOffices = true;
    public $dropOnlyCenter = true;

    public $refundClearanceValidPeriodInMonths = 6; // period in months
    public $accrualClearanceValidPeriodInMonths = 6;
    public $userTokenExpirationTime = 3600;

}

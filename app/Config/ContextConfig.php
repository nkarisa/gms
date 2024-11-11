<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Summary of ContextConfig
 * 
 * @package Config
 * @author Nicodemus Karisa Mwambire
 * @copyright 2024 Safina Solution
 * @version 3.0.0
 * @license https://safina-solutions.com/licenses/MIT MIT License
 * @file ContextConfig.php
 * @since 2024-11-11
 * @see https://codeigniter4.github.io/CodeIgniter4/
 * 
 * This class provides a context based configuration. 
 * All configuration options are stored in an array of accounting system Ids.
 * Note that, the configuration values can be changed directly in the settings database table
 * 
 * It recommeded that you access these configuration options using service("settings")->get('ContextConfig.<<ConfigKey>>')
 * rather than using config(ContextConfig::class). This will allow managing the configurations 
 * dynamically. 
 */
class ContextConfig extends BaseConfig
{
    /**
     * Summary of use_pca_objectives
     * @var array
     * List the accounting system Ids that use PCA objectives in their budgeting process
     * Update the values in the settings
     */
    public $use_pca_objectives = [];

    /**
     * Summary of allow_attachment_on_voucher
     * @var array
     * 
     * List the accounting system Ids that allow attachment on voucher in their vouching process
     */
    public $allow_attachment_on_voucher = [];
    public $user_privacy_consent_required = [];
    
}

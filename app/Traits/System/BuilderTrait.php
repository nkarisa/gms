<?php 

namespace App\Traits\System;

trait BuilderTrait {
    protected $accountSystemBuilder;
    protected $contextDefinitionBuilder;
    protected $countryCurrencyBuilder;
    protected $languageBuilder;
    protected $roleBuilder;
    protected $rolePermissionBuilder;
    protected $userBuilder;

    protected function initBuilders(){
        $db = \Config\Database::connect();

        $this->accountSystemBuilder = $db->table('account_system');
        $this->contextDefinitionBuilder = $db->table('context_definition');
        $this->countryCurrencyBuilder = $db->table('country_currency');
        $this->languageBuilder = $db->table('language');
    }
}
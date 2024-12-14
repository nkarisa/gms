<?php 

namespace App\Traits\System;

trait LibraryInitTrait {
    protected $accountSystemLibrary;
    protected $contextDefinitionLibrary;
    protected $countryCurrencyLibrary;
    protected $languageLibrary;
    protected $roleLibrary;
    protected $userLibrary;
    protected function initLibraries(){
        $this->accountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();
        $this->contextDefinitionLibrary = new \App\Libraries\Core\ContextDefinitionLibrary();
        $this->countryCurrencyLibrary = new \App\Libraries\Grants\CountryCurrencyLibrary();
        $this->languageLibrary = new \App\Libraries\Core\LanguageLibrary();
        $this->roleLibrary = new \App\Libraries\Core\RoleLibrary();
        $this->userLibrary = new \App\Libraries\Core\UserLibrary();
    }
}
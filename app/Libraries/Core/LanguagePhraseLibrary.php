<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\LanguagePhraseModel;
class LanguagePhraseLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new LanguagePhraseModel();

        $this->table = 'core';
    }


   
}
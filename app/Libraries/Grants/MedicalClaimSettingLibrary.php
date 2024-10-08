<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\MedicalClaimSettingModel;
class MedicalClaimSettingLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new MedicalClaimSettingModel();

        $this->table = 'grants';
    }


   
}
<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\MedicalClaimAdminSettingModel;
class MedicalClaimAdminSettingLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new MedicalClaimAdminSettingModel();

        $this->table = 'grants';
    }


   
}
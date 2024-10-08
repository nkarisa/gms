<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\MedicalClaimTypeModel;
class MedicalClaimTypeLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new MedicalClaimTypeModel();

        $this->table = 'grants';
    }


   
}
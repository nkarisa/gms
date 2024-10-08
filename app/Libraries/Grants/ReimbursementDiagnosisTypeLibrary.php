<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ReimbursementDiagnosisTypeModel;
class ReimbursementDiagnosisTypeLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ReimbursementDiagnosisTypeModel();

        $this->table = 'grants';
    }


   
}
<?php

namespace App\Libraries\Grants;

use App\Libraries\Core\ApprovalLibrary;
use App\Libraries\Core\StatusLibrary;
use App\Libraries\Core\UserLibrary;
use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ReimbursementClaimModel;

class ReimbursementClaimLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ReimbursementClaimModel();

        $this->table = 'reimbursement_claim';
    }

}
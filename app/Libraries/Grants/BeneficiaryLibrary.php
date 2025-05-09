<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BeneficiaryModel;

class BeneficiaryLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new BeneficiaryModel();

        $this->table = 'beneficiaries';
    }

    function listTableVisibleColumns(): array
    {
        $columns = [
            'beneficiary_id',
            'beneficiary_name',
            'beneficiary_number',
            'beneficiary_gender',
            'beneficiary_dob',
            'account_system_name',
        ];

        return $columns;
    }


}
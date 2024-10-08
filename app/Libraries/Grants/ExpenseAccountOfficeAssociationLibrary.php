<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ExpenseAccountOfficeAssociationModel;
class ExpenseAccountOfficeAssociationLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ExpenseAccountOfficeAssociationModel();

        $this->table = 'grants';
    }


   
}
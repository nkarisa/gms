<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ChequeBookResetConstraintModel;
class ChequeBookResetConstraintLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $chequebookresetconstraintModel;

    function __construct()
    {
        parent::__construct();

        $this->chequebookresetconstraintModel = new ChequeBookResetConstraintModel();

        $this->table = 'chequebookresetconstraint';
    }


   
}
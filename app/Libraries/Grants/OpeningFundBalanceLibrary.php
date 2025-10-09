<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OpeningFundBalanceModel;
class OpeningFundBalanceLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new OpeningFundBalanceModel();

        $this->table = 'grants';
    }


   function showAddButton(): bool {
        return false;
    }
}
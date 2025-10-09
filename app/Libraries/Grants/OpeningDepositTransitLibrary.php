<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OpeningDepositTransitModel;
class OpeningDepositTransitLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new OpeningDepositTransitModel();

        $this->table = 'grants';
    }

    function showAddButton(): bool {
        return false;
    }
   
}
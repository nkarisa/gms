<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ReplicateOpeningBalancesModel;
class ReplicateOpeningBalancesLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ReplicateOpeningBalancesModel();

        $this->table = 'grants';
    }


   
}
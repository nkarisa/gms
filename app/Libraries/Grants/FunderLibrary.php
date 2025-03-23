<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\FunderModel;
class FunderLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $funderModel;

    function __construct()
    {
        parent::__construct();

        $this->funderModel = new FunderModel();

        $this->table = 'funder';
    }

    function detailTables(): array{
        return ['project'];
    }
}
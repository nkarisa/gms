<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ReimbursementCommentModel;
class ReimbursementCommentLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ReimbursementCommentModel();

        $this->table = 'grants';
    }


   
}
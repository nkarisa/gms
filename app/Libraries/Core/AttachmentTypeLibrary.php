<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\AttachmentTypeModel;
class AttachmentTypeLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new AttachmentTypeModel();

        $this->table = 'core';
    }


   
}
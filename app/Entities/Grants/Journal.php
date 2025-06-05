<?php

namespace App\Entities\Grants;

use CodeIgniter\Entity\Entity;

class Journal extends Entity
{
    protected $datamap = [];
    protected $dates   = [
        'journal_created_date', 
        'journal_last_modified_date', 
        'journal_deleted_date'
    ];
    protected $casts   = [];
}

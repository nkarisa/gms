<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\JournalModel;

class JournalLibrary extends GrantsLibrary {
    protected $table;
    protected $journalModel;

    function __construct()
    {
        parent::__construct();

        $this->journalModel = new JournalModel();

        $this->table = 'journal';
    }
}
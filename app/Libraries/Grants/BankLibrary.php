<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BankModel;

class BankLibrary extends GrantsLibrary {
    protected $table;
    protected $bankModel;

    function __construct()
    {
        parent::__construct();

        $this->bankModel = new BankModel();

        $this->table = 'bank';
    }

    // function pagePosition(){
    //     $widget['position_1']['view'][] =  "Hello World";
    //     return $widget;
    // }

    function detailTables(): array
    {
        return ['office_bank'];
    }

}
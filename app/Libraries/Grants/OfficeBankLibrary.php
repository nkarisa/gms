<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OfficeBankModel;
class OfficeBankLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new OfficeBankModel();

        $this->table = 'grants';
    }


    public function detailListTableVisibleColumns(): array{
        return ['office_bank_track_number','office_bank_name','office_bank_is_active',
        'office_bank_account_number','office_name','bank_name','office_bank_chequebook_size','office_bank_is_default','status_name','approval_name'];
      }
}
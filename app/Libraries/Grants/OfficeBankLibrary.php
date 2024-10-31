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

    public function singleFormAddVisibleColumns(): array{
        return [
          'office_name',
          'office_bank_name',
          'bank_name',
          'office_bank_account_number',
          'office_bank_chequebook_size'
        ];
    }

    function editVisibleColumns(): array{
        return [
          'office_name',
          'bank_name',
          'office_bank_name',
          'office_bank_account_number',
          'office_bank_is_default',
          'office_bank_is_active',
          'office_bank_chequebook_size',
          'office_bank_book_exemption_expiry_date'
          
        ];
      }

      function lookupValues(): array{
        $lookupValues = array();

        $lookupValues['office'] = [];

        return $lookupValues;
      }
}
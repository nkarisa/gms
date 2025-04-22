<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\SystemOpeningBalanceModel;
class SystemOpeningBalanceLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new SystemOpeningBalanceModel();

        $this->table = 'system_opening_balance';
    }

    function detailTables(): array {
        return [
            'opening_bank_balance',
            'opening_cash_balance',
            'opening_fund_balance',
            'opening_outstanding_cheque',
            'opening_deposit_transit'
        ];
    }

    function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void {
        $queryBuilder->where(['fk_context_definition_id' => 1]);
    }
   
}
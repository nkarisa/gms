<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OpeningCashBalanceModel;
class OpeningCashBalanceLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new OpeningCashBalanceModel();

        $this->table = 'opening_cash_balance';
    }


    function systemOpeningCashBalance($office_ids, $project_ids = [], $office_bank_ids = [], $office_cash_id = 0)
    {
        $balance = 0;

        $builder = $this->read_db->table("opening_cash_balance");
        $builder->selectSum('opening_cash_balance_amount');
        $builder->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_cash_balance.fk_system_opening_balance_id');
        $builder->whereIn('system_opening_balance.fk_office_id', $office_ids);

        if (count($project_ids) > 0) {
            $builder->whereIn('project_allocation.fk_project_id', $project_ids);
            $builder->join('office_bank', 'office_bank.office_bank_id=opening_cash_balance.fk_office_bank_id');
            $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
            $builder->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
        }

        if (!empty($office_bank_ids)) {
            $builder->whereIn('opening_cash_balance.fk_office_bank_id', $office_bank_ids);
        }

        if($office_cash_id > 0){
            $builder->where(array('opening_cash_balance.fk_office_cash_id' => $office_cash_id));
        }

        $opening_cash_balance_obj = $builder->get();

        if ($opening_cash_balance_obj->getNumRows() > 0) {
            $balance = $opening_cash_balance_obj->getRow()->opening_cash_balance_amount;
        }

        return $balance;
    }
}
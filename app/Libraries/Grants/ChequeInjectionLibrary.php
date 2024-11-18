<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ChequeInjectionModel;
class ChequeInjectionLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ChequeInjectionModel();

        $this->table = 'grants';
    }


    public function showListEditAction(array $record): bool {
        if(!isset($record['cheque_injection_is_active']) || $record['cheque_injection_is_active'] == 0){
            return false;
        }
        return true;
    }

    function getInjectedChequeLeaves($office_bank_id)
    {
        $cheque_injection = [];
        $builder = $this->read_db->table('cheque_injection');
        $builder->select(['cheque_injection_number']);
        $builder->where(['fk_office_bank_id' => $office_bank_id]);
        $cheque_injection_obj =  $builder->get();

        if ($cheque_injection_obj->getNumRows() > 0) {
            $cheque_injection = array_column($cheque_injection_obj->getResultArray(), "cheque_injection_number");
        }

        return $cheque_injection;
    }
}
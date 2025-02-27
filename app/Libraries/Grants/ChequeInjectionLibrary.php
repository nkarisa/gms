<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ChequeInjectionModel;
class ChequeInjectionLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ChequeInjectionModel();

        $this->table = 'grants';
    }


    public function showListEditAction(array $record,  array $dependancyData = []): bool {
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

    function updateInjectedChequeStatus($office_bank_id, $cheque_number){
        $is_injected_cheque_number = $this->isInjectedChequeNumber($office_bank_id, $cheque_number);

        if($is_injected_cheque_number){
            $builder = $this->write_db->table('cheque_injection');
            $builder->set('cheque_injection_is_active', 0);
            $builder->where(array(
                "fk_office_bank_id" => $office_bank_id,
                'cheque_injection_number' => $cheque_number
            ));
            $builder->update();
            return true;
        }
        return false;
    }

    function isInjectedChequeNumber($office_bank_id, $cheque_number)
    {
        $is_injected_cheque_number = true;

        $builder = $this->read_db->table('cheque_injection');
        $builder->where(array(
            "fk_office_bank_id" => $office_bank_id,
            'cheque_injection_number' => $cheque_number
        ));
        $cheque_injection_obj = $builder->get();

        if ($cheque_injection_obj->getNumRows() == 0) {
            $is_injected_cheque_number = false;
        }

        return $is_injected_cheque_number;
    }
}
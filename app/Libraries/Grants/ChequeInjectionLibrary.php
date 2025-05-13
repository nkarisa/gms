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
        $this->model = new ChequeInjectionModel();
        $this->table = 'cheque_injection';
    }

    function actionBeforeInsert($post_array): array
    {

        $office_bank_id = $post_array['header']['fk_office_bank_id'];
        $cheque_injection_number = $post_array['header']['cheque_injection_number'];

            $cheque_condition = array(
                'fk_office_bank_id' => $office_bank_id,
                'voucher_cheque_number' => $cheque_injection_number,
                
            );
    
            $voucherWriteBuilder = $this->write_db->table('voucher');

            $voucherWriteBuilder->select(['voucher_id']);
            $voucherWriteBuilder->where($cheque_condition);
            $voucherWriteBuilder->where(['voucher_type_is_cheque_referenced'=>1]);
            $voucherWriteBuilder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
            $count_of_unusable_cheque_leaves = $voucherWriteBuilder->get()->getResultArray();
    
            if (count($count_of_unusable_cheque_leaves) >0) {
                $data['voucher_cheque_number'] = -$cheque_injection_number;
                $voucherWriteBuilder->where('voucher_id',$count_of_unusable_cheque_leaves[0]['voucher_id']);
                $voucherWriteBuilder->update($data);
            }
        
        return $post_array;
        
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

    public function singleFormAddVisibleColumns(): array {
        return [
            'office_bank_name',
            'cheque_injection_number',
            'item_reason_name'
        ];
    }

    public function listTableVisibleColumns(): array {
        return [
            'cheque_injection_track_number',
            'office_bank_name',
            'cheque_injection_number',
            'cheque_injection_is_active',
            'cheque_injection_created_date'
        ];
    }

    public function createChequeInjectionForOfficeBank(int $office_bank_id, int $cheque_number){
        // $chequeBookLibrary = new \App\Libraries\Grants\ChequeBookLibrary();
        $itemReasonLibrary = new \App\Libraries\Grants\ItemReasonLibrary();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
        $chequeInjectionWriteBuilder = $this->write_db->table('cheque_injection');

        // Check if the cheque number is not an active injection 
        $chequeNumberNotActiveInjection = $this->chequeNumberIsActiveInjection($office_bank_id, $cheque_number);
        
        if($chequeNumberNotActiveInjection == false){
            $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('cheque_injection');
            $defaultReason = $itemReasonLibrary->getApproveItemDefaultReason('cheque_injection');

            $data['cheque_injection_track_number'] = $itemTrackNumberAndName['cheque_injection_track_number'];
            $data['cheque_injection_name'] = $itemTrackNumberAndName['cheque_injection_name'];
            $data['fk_office_bank_id'] = $office_bank_id;
            $data['cheque_injection_number'] = $cheque_number;
            $data['fk_item_reason_id'] = $defaultReason['item_reason_id'];
            $data['cheque_injection_is_active'] = 1;
            $data['cheque_injection_created_date'] = date('Y-m-d');
            $data['cheque_injection_created_by'] = $this->session->user_id;
            $data['cheque_injection_last_modified_date'] = date('Y-m-d h:i:s');
            $data['cheque_injection_last_modified_by'] = $this->session->user_id;
            $data['fk_status_id'] = $statusLibrary->initialItemStatus('cheque_injection');
            $data['fk_approval_id'] = $approvalLibrary->insertApprovalRecord('cheque_injection');

            $chequeInjectionWriteBuilder->insert($data);
        }
    }

    private function chequeNumberIsActiveInjection(int $office_bank_id, int $cheque_number){
        $chequeInjectionReadbuilder = $this->read_db->table('cheque_injection');

        $chequeInjectionReadbuilder->where(['fk_office_bank_id' => $office_bank_id, 'cheque_injection_number' => $cheque_number, 'cheque_injection_is_active' => 1]);
        $countActiveInjections = $chequeInjectionReadbuilder->get()->getNumRows();

        return $countActiveInjections > 0 ? true : false;
    }

    public function disableChequeActiveChequeInjection(int $office_bank_id, int $cheque_number){
        $chequeNumberIsActiveInjection = $this->chequeNumberIsActiveInjection($office_bank_id, $cheque_number);
        $chequeInjectionWriteBuilder = $this->write_db->table('cheque_injection');

        if($chequeNumberIsActiveInjection == true){
            $data['cheque_injection_is_active'] = 0;
            $chequeInjectionWriteBuilder->where(['fk_office_bank_id' => $office_bank_id, 'cheque_injection_number' => $cheque_number]);
            $chequeInjectionWriteBuilder->update($data);
        }
    }
}
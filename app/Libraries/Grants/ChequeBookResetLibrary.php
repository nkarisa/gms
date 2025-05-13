<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ChequeBookResetModel;
class ChequeBookResetLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $model;

    function __construct()
    {
        parent::__construct();

        $this->model = new ChequeBookResetModel();

        $this->table = 'cheque_book_reset';
    }

    function actionBeforeInsert($post_array): array
    {
        $office_bank_id = $post_array['header']['fk_office_bank_id'];
        $chequeBookResetReadBuilder = $this->read_db->table('cheque_book_reset');
        $chequeBookLibrary = new \App\Libraries\Grants\ChequeBookLibrary();

        $chequeBookResetReadBuilder->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_reset_is_active' => 1));
        $ctive_cheque_book_reset_obj = $chequeBookResetReadBuilder->get();

        if ($ctive_cheque_book_reset_obj->getNumRows() > 0) {
            $this->deactivateChequeBookReset($office_bank_id);
        }

        $chequeBookLibrary->deactivateChequeBook($office_bank_id);

        return $post_array;
    }

    public function showListEditAction(array $record,  array $dependancyData = []): bool {
        if(!isset($record['cheque_book_reset_is_active']) || $record['cheque_book_reset_is_active'] == 0){
            return false;
        }
        return true;
    }

    public function listTableVisibleColumns(): array {
        return [
            'cheque_book_reset_track_number',
            'office_bank_name',
            'cheque_book_reset_serial',
            'cheque_book_reset_is_active',
            'cheque_book_reset_created_date'
        ];
    }

    function edit_visible_columns()
    {
        return [
            'office_bank_name',
            'cheque_book_reset_serial',
            'item_reason_name'
        ];
    }

    function deactivateChequeBookReset($office_bank_id)
    {
        $chequeBookResetWriteBuilder = $this->write_db->table('cheque_book_reset');

        $chequeBookResetWriteBuilder->where(array('fk_office_bank_id' => $office_bank_id));
        $data['cheque_book_reset_is_active'] = 0;
        $chequeBookResetWriteBuilder->update($data);
    }

    public function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void {
        if(!$this->session->system_admin){
            $queryBuilder->join('bank','bank.bank_id=office_bank.fk_bank_id');
            $queryBuilder->where(['bank.fk_account_system_id' => $this->session->user_account_system_id]);
        }
    }

    public function singleFormAddVisibleColumns(): array {
        return [
            'office_bank_name',
            'cheque_book_reset_serial',
            'item_reason_name'
        ];
    }

    public function lookupValues(): array
    {
        $lookup_values = parent::lookupValues();

        $itemReasonReadBuilder = $this->read_db->table('item_reason');

        $itemReasonReadBuilder->join('approve_item', 'approve_item.approve_item_id=item_reason.fk_approve_item_id');
        $itemReasonReadBuilder->where(array('approve_item_name' => 'cheque_book_reset'));
        $itemReasonObj = $itemReasonReadBuilder->get();

        if($itemReasonObj->getNumRows() > 0){
            $lookup_values['item_reason'] = $itemReasonObj->getResultArray();
        }

        // Only show office bank of the offices in your hierarchy
        if(!$this->session->system_admin){
            $officeBankReadBuilder = $this->read_db->table('office_bank');

            $officeBankReadBuilder->select(array('office_bank_id','office_bank_name'));
            $officeBankReadBuilder->whereIn('office_bank.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
            $office_banks_obj = $officeBankReadBuilder->get();

            if($office_banks_obj->getNumRows() > 0){
                $lookup_values['office_bank'] = $office_banks_obj->getResultArray();
            }
        }

        return $lookup_values;
    }
   
}
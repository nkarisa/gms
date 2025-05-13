<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CancelChequeModel;

class CancelChequeLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $cancelchequeModel;

    function __construct()
    {
        parent::__construct();

        $this->cancelchequeModel = new CancelChequeModel();

        $this->table = 'cancel_cheque';
    }

    /**
     *getValidCheques(): Returns the valid cheques.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return array 
     * @param int $office_bank_id
     */
    public function getValidCheques(int $office_bank_id): array
    {
        $chequeBookLibrary = new ChequeBookLibrary();

        //Get remaining chqs; voucher cancelled chqs and cancelled chqs that were cancelled using cancel cheque feature
        $leaves = $chequeBookLibrary->getRemainingUnusedChequeLeaves($office_bank_id, true);
        $voucher_cancelled_chqs = $this->voucherCancelledCheques($office_bank_id);
        $cancelled_chqs_using_cancel_feature = $this->getCancelledCheques($office_bank_id);

        //Loop and array search the value in the voucher cancelled chq and unset to remove them in the remaing chqs
        foreach ($leaves as $key => $leave) {
            $value = -$leave['cheque_id'];
            //Remove the chqs cancelled in the voucher
            $found_value_in_voucher_cancelled_cheques = in_array($value, array_map(function ($elem) {
                return abs($elem);
            }, $voucher_cancelled_chqs));

            if ($found_value_in_voucher_cancelled_cheques !== false) {
                unset($leaves[$key]);
            }
            //Remove the chqs cancelled using cancel cheque feature
            $found_value_in_cancelled_chqs_using_cancel_feature = in_array(abs($value), $cancelled_chqs_using_cancel_feature);

            if ($found_value_in_cancelled_chqs_using_cancel_feature !== false) {
                unset($leaves[$key]);
            }
        }

        return $leaves;
    }

    /**
     *voucherCancelledCheques(): Returns cancelled cheques in the voucher side.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access private
     * @return array 
     * @param int $office_bank_id
     */
    private function voucherCancelledCheques(int $office_bank_id): array
    {

        $cancelled_voucher_numbers = [];

        //Get the  active chequebooks
        $chequebook_id = $this->getActiveChequebook($office_bank_id);

        //If the active chq books , get the cancelled chqs in voucher table of the active chequebook.
        $builder = $this->read_db->table("voucher");
        $builder->select('voucher_cheque_number');
        $builder->distinct();
        $builder->where(['fk_cheque_book_id' => $chequebook_id]);
        $builder->like('voucher_cheque_number', '-', 'both');
        $cancelled_voucher_numbers = $builder->get()->getResultArray();

        return array_column($cancelled_voucher_numbers, 'voucher_cheque_number');

    }

    /**
     *getCancelledCheques(): Returns cancelled cheques .
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access private
     * @return array 
     */
    private function getCancelledCheques(int $office_bank_id): array
    {
        //Get cancelled cheques that are cancelled using cancel cheques feature
        $builder = $this->read_db->table("cancel_cheque");
        $builder->select(['cancel_cheque_number']);
        $builder->join('cheque_book', 'cheque_book.cheque_book_id=cancel_cheque.fk_cheque_book_id');
        $builder->where(['fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1]);
        $cancel_cheque = $builder->get()->getResultArray();

        return array_column($cancel_cheque, 'cancel_cheque_number');
    }

    /**
     *getActiveChequebook():This method gets to pass active chequebook.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return int 
     * @param int $office_bank_id
     */
    public function getActiveChequebook(int $office_bank_id): int
    {

        $cheque_book_id = 0;
        $builder = $this->read_db->table("cheque_book");
        $builder->select(['cheque_book_id']);
        $builder->where(['cheque_book_is_active' => 1, 'fk_office_bank_id' => $office_bank_id]);
        $result_obj = $builder->get();

        if ($result_obj->getNumRows() > 0) {
            $cheque_book_id = $result_obj->getRow()->cheque_book_id;
        }

        return $cheque_book_id;
    }

    public function getBankAccounts(): array
    {
        $userLibrary = new \App\Libraries\Core\UserLibrary();
        $officeBankReadBuilder = $this->read_db->table('office_bank');

        //User hierachy offices
        $user_office_ids = $userLibrary->userHierarchyOffices($this->session->user_id);
        $office_ids = array_column($user_office_ids, 'office_id');

        //Get the bank accounts
        $officeBankReadBuilder->select(['office_bank_id', 'office_bank_name']);
        $officeBankReadBuilder->whereIn('fk_office_id', $office_ids);
        $office_banks = $officeBankReadBuilder->get()->getResultArray();

        //bank accounts ids and bank names
        $bank_ids = array_column($office_banks, 'office_bank_id');
        $bank_names = array_column($office_banks, 'office_bank_name');
        $bank_ids_and_names = array_combine($bank_ids, $bank_names);

        return $bank_ids_and_names;
    }

    /**
     *get_cancel_cheque_reason(): Returns returns cancel chq reasons.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return array 
     */
    public function getCancelChequeReason(): array
    {
        $itemReasonReadBuilder = $this->read_db->table('item_reason');

        $itemReasonReadBuilder->select(['item_reason_id', 'item_reason_name']);
        $itemReasonReadBuilder->where(['fk_approve_item_id' => 144, 'item_reason_is_active' => 1]);
        $result = $itemReasonReadBuilder->get()->getResultArray();

        $reason_ids = array_column($result, 'item_reason_id');
        $reason_names = array_column($result, 'item_reason_name');

        return array_combine($reason_ids, $reason_names);
    }

    /**
     *getChequeBookRange(): Returns the range of cheques as an arrau.
     * @param int $cancelled_cheques_id
     *@return array
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     */
    public function getChequeBookRange(int $cancelled_cheques_id): array
    {

        $builder = $this->read_db->table('cheque_book');
        $builder->select(['cheque_book_start_serial_number', 'cheque_book_count_of_leaves']);
        $builder->join('cancel_cheque', 'cancel_cheque.fk_cheque_book_id=cheque_book.cheque_book_id');
        $builder->where(['cancel_cheque_id' => $cancelled_cheques_id]);
        $result = $builder->get()->getResultArray();

        return $result;
    }

    public function listTableVisibleColumns(): array {
        return [
            'cancel_cheque_track_number',
            'cancel_cheque_number',
            // 'cheque_book_start_serial_number',
            'voucher_number',
            'cancel_cheque_created_date'
        ];
    }

    public function singleFormAddVisibleColumns(): array {
        // Please do not remove this method though useless, it prevents a Kint error issue
        return [
            'cancel_cheque_number'
        ];
    }
}
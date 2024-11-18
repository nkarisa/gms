<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ChequeBookModel;

class ChequeBookLibrary extends GrantsLibrary {
    protected $table;
    protected $chequeBookModel;

    function __construct()
    {
        parent::__construct();

        $this->chequeBookModel = new ChequeBookModel();

        $this->table = 'cheque_book';
    }

    function allowSkippingOfChequeLeaves()
    {
        $is_skipping_of_cheque_leaves_allowed = true;
        if (service("settings")->get("GrantsConfig.allow_skipping_of_cheque_leaves") == false) {
            $is_skipping_of_cheque_leaves_allowed = false;
        }
        return $is_skipping_of_cheque_leaves_allowed;
    }

    function getRemainingUnusedChequeLeaves($office_bank_id, $cheque_numbers_only = true)
    {
        $all_cheque_leaves = $this->getAllApprovedActiveChequeBooksLeaves($office_bank_id, $cheque_numbers_only);

        $leaves = [];

        if (!empty($all_cheque_leaves)) {

            $used_cheque_leaves = $this->getUsedChequeLeaves($office_bank_id);
            $cancelled_cheque_numbers = $this->cancelledChequeNumbers($office_bank_id);

            foreach ($all_cheque_leaves as $cheque_number) {
                // Remove cancelled cheques from the pool of cheques
                if (in_array($cheque_number, $cancelled_cheque_numbers)) {
                    unset($all_cheque_leaves[array_search($cheque_number, $all_cheque_leaves)]);
                }
            }

            foreach ($all_cheque_leaves as $cheque_number) {
                // Removed used cheques from the pool of cheques
                if (in_array($cheque_number, $used_cheque_leaves)) {
                    unset($all_cheque_leaves[array_search($cheque_number, $all_cheque_leaves)]);
                }
            }

            $keyed_cheque_leaves = [];
            $cnt = 0;

            $all_cheque_leaves = array_unique($all_cheque_leaves);

            foreach ($all_cheque_leaves as $cheque_leaf) {
                $keyed_cheque_leaves[$cnt]['cheque_id'] = $cheque_leaf;
                $keyed_cheque_leaves[$cnt]['cheque_number'] = $cheque_leaf;
                if (!$this->allowSkippingOfChequeLeaves()) {
                    break;
                }
                $cnt++;
            }
            $leaves = $keyed_cheque_leaves;
        }

        return  $leaves;
    }

    function  getAllApprovedActiveChequeBooksLeaves($office_bank_id, $cheque_numbers_only = true)
    {

        // You can only have 1 approved active cheque book
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $max_status_ids = $statusLibrary->getMaxApprovalStatusId('cheque_book');
        $chequeInjectionLibrary = new \App\Libraries\Grants\ChequeInjectionLibrary();
        $injected_cheque_leaves = $chequeInjectionLibrary->getInjectedChequeLeaves($office_bank_id);
        $unused_reused_cheques = $this->getUnusedReusedCheques($office_bank_id, $cheque_numbers_only);

        $builder = $this->read_db->table('cheque_book');
        $builder->whereIn('cheque_book.fk_status_id', $max_status_ids);
        $builder->select(array('cheque_book_start_serial_number', 'cheque_book_count_of_leaves'));
        $builder->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1));
        $all_chqbooks_inactive_and_active = $builder->get()->getResult();
       
        $builder = $this->read_db->table('cheque_book');
        $builder->whereIn('cheque_book.fk_status_id', $max_status_ids);
        $builder->select(array('cheque_book_start_serial_number', 'cheque_book_count_of_leaves'));
        $builder->where(array('fk_office_bank_id' => $office_bank_id,'cheque_book_is_active'=>1));
        $cheque_book = $builder->get();

        $all_cheque_leaves = [];

        $reorganized_all_cheque_leaves = [];

        if (!empty($all_chqbooks_inactive_and_active)) {
            // Count of leaves in the active cheque book
            foreach ($all_chqbooks_inactive_and_active as $chqbook) {

                $sum_leaves_count_for_all_books = $chqbook->cheque_book_count_of_leaves;
                $cheque_book_start_serial_number = $chqbook->cheque_book_start_serial_number;
                $last_leaf = $cheque_book_start_serial_number + ($sum_leaves_count_for_all_books - 1);
                $all_cheque_leaves = range($cheque_book_start_serial_number, $last_leaf);

                foreach ($all_cheque_leaves as $leave) {
                    $reorganized_all_cheque_leaves[] = $leave;
                }
            }
        }

        if (count($injected_cheque_leaves) > 0) {
            $reorganized_all_cheque_leaves = array_merge($reorganized_all_cheque_leaves, $injected_cheque_leaves);
        }

        if (count($unused_reused_cheques) > 0) {
            $reorganized_all_cheque_leaves = array_merge($reorganized_all_cheque_leaves, $unused_reused_cheques);
        }

        sort($reorganized_all_cheque_leaves);

        return array_unique($reorganized_all_cheque_leaves);
    }

    function getUsedChequeLeaves($office_bank_id)
    {

        $opening_outstanding_cheques_used_cheque_leaves = $this->openingOutstandingChequesUsedChequeLeaves($office_bank_id);

        $builder = $this->read_db->table('voucher');
        $builder->select(array('voucher_cheque_number'));
        $builder->whereIn('voucher_type_effect_code', ['expense', 'bank_contra', 'bank_to_bank_contra']);
        $builder->where(array('fk_office_bank_id' => $office_bank_id, 'voucher_type_account_code' => 'bank', 'voucher_cheque_number > ' => 0));
        $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $builder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $builder->orderBy('voucher_cheque_number ASC');
        $used_cheque_leaves_obj = $builder->get();

        $used_cheque_leaves = [];

        if ($used_cheque_leaves_obj->getNumRows() > 0) {
            $used_cheque_leaves = array_column($used_cheque_leaves_obj->getResultArray(), 'voucher_cheque_number');
        }

        // Add the opening outstanding cheques to the list of used cheque leaves
        if (!empty($opening_outstanding_cheques_used_cheque_leaves)) {
            $used_cheque_leaves = array_merge($used_cheque_leaves, $opening_outstanding_cheques_used_cheque_leaves);
        }
        // log_message('error', json_encode($used_cheque_leaves));
        return $used_cheque_leaves;
    }

    function openingOutstandingChequesUsedChequeLeaves($office_bank_id)
    {
        $post = $this->request->getPost();
        $opening_outstanding_cheques_array = [];
        $builder = $this->read_db->table('opening_outstanding_cheque');
        $builder->select(array('opening_outstanding_cheque_number'));
        $builder->where(array('opening_outstanding_cheque.fk_office_bank_id' => $office_bank_id));
        $opening_outstanding_cheques_obj = $builder->get();

        if ($opening_outstanding_cheques_obj->getNumRows() > 0) {
            $opening_outstanding_cheques = $opening_outstanding_cheques_obj->getResultArray();
            $opening_outstanding_cheques_array = array_column($opening_outstanding_cheques, 'opening_outstanding_cheque_number');
        }

        return $opening_outstanding_cheques_array;
    }

    function cancelledChequeNumbers(int $office_bank_id)
    {
        // Only one cheque number is -ve
        $cancelled_cheque_numbers = [];

        $sql = "SELECT voucher_cheque_number, COUNT(*) FROM voucher ";
        $sql .= "WHERE fk_office_bank_id = " . $office_bank_id . " AND voucher_cheque_number < 0 ";
        $sql .= "GROUP BY voucher_cheque_number HAVING COUNT(*) = 1";

        $cancelled_cheque_numbers_obj = $this->read_db->query($sql);

        if ($cancelled_cheque_numbers_obj->getNumRows() > 0) {
            $cancelled_cheque_numbers = array_column($cancelled_cheque_numbers_obj->getResultArray(), 'voucher_cheque_number');
            $cancelled_cheque_numbers = array_map([$this, 'makeUnsignedValues'], $cancelled_cheque_numbers);
        }
       
        return $cancelled_cheque_numbers;
    }

    function getUnusedReusedCheques($office_bank_id, $cheque_numbers_only = true)
    {
        $all_reused_cheques = $this->getReusedCheques($office_bank_id, $cheque_numbers_only);
        $used_reused_cheques = $this->getUsedReusedCheques($office_bank_id, $cheque_numbers_only);

        $unused_reused_cheques = [];

        if (count($all_reused_cheques) > 0 && count($used_reused_cheques)>0) {
             // Array diff has proved to be inaccurate for reasons not known therefore loop was used
            foreach($all_reused_cheques as $reused_cheque){
                if(!in_array($reused_cheque, $used_reused_cheques)){
                    $unused_reused_cheques[] = $reused_cheque;
                }
            }
        }
    
        return $unused_reused_cheques;
    }

    function getReusedCheques($office_bank_id, $cheque_numbers_only = true)

    {
        // two cheque numbers are -ve

        $reused_cheque_numbers = [];

        $sql = "SELECT voucher_cheque_number, COUNT(*) FROM voucher ";
        if($cheque_numbers_only){
            $sql .= "JOIN voucher_type ON voucher.fk_voucher_type_id=voucher_type.voucher_type_id ";
            $sql .= "WHERE voucher_type_is_cheque_referenced = 1 ";
        }else{
            $sql .= "WHERE voucher_type_is_cheque_referenced = 0 ";
        }
        $sql .= "AND fk_office_bank_id = " . $office_bank_id . " AND voucher_cheque_number < 0 ";
        $sql .= "GROUP BY voucher_cheque_number HAVING COUNT(*) IN (2,4,6,8,10) "; // This means a cheque leaf can be resused 5 times maximum after which it wont appear in the pool of cheque leaves when raising a voucher

        $reused_cheque_numbers_obj = $this->read_db->query($sql);

        if ($reused_cheque_numbers_obj->getNumRows() > 0) {
            $reused_cheque_numbers = array_column($reused_cheque_numbers_obj->getResultArray(), 'voucher_cheque_number');
            $reused_cheque_numbers = array_map([$this, 'makeUnsignedValues'], $reused_cheque_numbers);
        }

        return $reused_cheque_numbers;
    }

    function makeUnsignedValues($signed_cheque_number)
    {
        return abs($signed_cheque_number);
    }

    function getUsedReusedCheques($office_bank_id, $cheque_numbers_only = true)
    {
        // three cheque leaves
        $used_reused_cheque_numbers = [];

        $sql = "SELECT abs(voucher_cheque_number) as voucher_cheque_number, COUNT(*) as count FROM voucher ";
        if($cheque_numbers_only){
            $sql .= "JOIN voucher_type ON voucher.fk_voucher_type_id=voucher_type.voucher_type_id ";
            $sql .= "WHERE voucher_type_is_cheque_referenced = 1 ";
        }else{
            $sql .= "WHERE voucher_type_is_cheque_referenced = 0 ";
        }
        $sql .= "AND voucher_cheque_number REGEXP '^[-+]?[0-9]+$' AND fk_office_bank_id = " . $office_bank_id . " ";
        $sql .= "AND (voucher_cheque_number > 0 OR voucher_cheque_number < 0) ";
        $sql .= "GROUP BY abs(voucher_cheque_number) HAVING COUNT(*) IN (3,5,7,9,11) ";

        $used_reused_cheque_numbers_obj = $this->read_db->query($sql);

        if ($used_reused_cheque_numbers_obj->getNumRows() > 0) {
            $used_reused_cheque_numbers = array_column($used_reused_cheque_numbers_obj->getResultArray(), 'voucher_cheque_number');
            $used_reused_cheque_numbers = array_map([$this, 'makeUnsignedValues'], $used_reused_cheque_numbers);
        }

        return $used_reused_cheque_numbers;
    }
}
<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ChequeBookModel;

class ChequeBookLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface {
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

    function deactivateChequeBook($office_bank_id)
    {

        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        
        $success = true;
        $max_status_id = $statusLibrary->getMaxApprovalStatusId('Cheque_book');
        $condition = array('fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1);

        $builder = $this->read_db->table("cheque_book");
        $builder->where($condition);
        $max_approved_active_book_count = $builder->get()->getNumRows();

        if ($max_approved_active_book_count > 0) {
            $data['cheque_book_is_active'] = 0;
            $data['fk_status_id'] = $max_status_id[0]; // Make all deactivated book be fully approved automatically
            
            $builder = $this->write_db->table("cheque_book");
            $builder->where($condition);
            $builder->update($data);

            if ($this->write_db->affectedRows() > 0) {
                $success = true;
            }
        }

        return $success;
    }

    function checkActiveChequeBookForOfficeBankExist($office_bank_id)
    {
        $builder = $this->read_db->table("cheque_book");
        $builder->where(array('cheque_book.fk_office_bank_id' => $office_bank_id, 'cheque_book.cheque_book_is_active' => 1));
        return $builder->get();
    }

     /**
     * Gets the cheque book id of a given cheque number for cheque books in a given office bank
     * @author Nicodemus Karisa Mwambire
     * @date 18th March 2024
     * @param int cheque_number - Provide cheque number
     * @param int office_bank_id - Given office bank
     * @return int Cheque Book Id
     * @source master-record-cheque-id
     * @version v24.3.0.1
     */
    public function getChequeBookIdForChequeNumber(int $cheque_number, int $office_bank_id):int{
        $chequeInjectionLibrary = new ChequeInjectionLibrary();
        $cheque_book_id = 0;
        $is_injected_cheque_number = $chequeInjectionLibrary->isInjectedChequeNumber($office_bank_id, $cheque_number);
        
        $builder = $this->read_db->table("cheque_book");
        $builder->select(['cheque_book_id', 'cheque_book_start_serial_number', 'cheque_book_count_of_leaves']);
        $builder->where(array('fk_office_bank_id' => $office_bank_id));
        $office_bank_cheque_books_obj = $builder->get();
       
        if($office_bank_cheque_books_obj->getNumRows() > 0){
            $cheque_books = $office_bank_cheque_books_obj->getResultArray();
 
            foreach($cheque_books as $cheque_book){
                $cheque_book_pages = range($cheque_book['cheque_book_start_serial_number'], $cheque_book['cheque_book_start_serial_number'] + ($cheque_book['cheque_book_count_of_leaves'] - 1));
                if(in_array($cheque_number, $cheque_book_pages)){
                    $cheque_book_id = $cheque_book['cheque_book_id'];
                    break;
                }
            }
        }

        // We only get to this independent if clause if the leaf is injected and missing in all the books e.g. Bank Slips
        if($cheque_book_id == 0 && $is_injected_cheque_number == true){
            $builder = $this->read_db->table("cheque_book");
            $builder->where(array('fk_office_bank_id' => $office_bank_id));
            $builder->limit(1);
            $builder->orderBy('cheque_book_id desc');
            $cheque_book_id = $builder->get()->getRow()->cheque_book_id;
        }
        
        return $cheque_book_id;
    }

    public function lookup_tables()
    {
        return array('office_bank');
    }

    public function action_before_edit($post_array)
    {

        // Disallow edit when the first leaf of a cheque book has already been used

        // $this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'voucher_cheque_number >=' => $cheque_book_start_serial_number));
        // $count_initial_voucher_for_cheque_book = $this->read_db->get('voucher')->num_rows();


        $office_bank_id = $post_array['header']['fk_office_bank_id'];
        $cheque_book_start_serial_number = $post_array['header']['cheque_book_start_serial_number'];

        $query_builder = $this->read_db->table('voucher')
            ->where(array('fk_office_bank_id' => $office_bank_id, 'voucher_cheque_number >=' => $cheque_book_start_serial_number));

        $count_initial_voucher_for_cheque_book = $query_builder->get()->getNumRows();

        if ($count_initial_voucher_for_cheque_book > 0) {
            return ['error' => get_phrase('edit_used_cheque_book_not_allowed', 'You can\'t edit a chequebook that has atleast one of it\'s leaf used in a transaction')];
        }

        return $post_array;



    }

    public function action_before_insert($post_array)
    {

        $office_bank_id = $post_array['header']['fk_office_bank_id'];

        $count_remaining_unused_cheque_leaves = count($this->get_remaining_unused_cheque_leaves($office_bank_id));

        $chequebook_exemption_expiry_date = $this->deactivate_chequebook_exemption_expiry_date($office_bank_id);

        if ($count_remaining_unused_cheque_leaves == 0) {

            $this->deactivate_cheque_book($office_bank_id);
        }

        // Check if we have an active cheque book reset and deactivate it
        $active_cheque_book_reset = $this->get_active_cheque_book_reset($office_bank_id);

        if (!empty($active_cheque_book_reset)) {
            $this->cheque_book_reset_model->deactivate_cheque_book_reset($office_bank_id);
            $this->deactivate_cheque_book($office_bank_id);
        }

        return $post_array;
    }
    /**
     * Gets the cheque book id of a given cheque number for cheque books in a given office bank
     * @author Nicodemus Karisa Mwambire
     * @date 18th March 2024
     * @param int cheque_number - Provide cheque number
     * @param int office_bank_id - Given office bank
     * @return int Cheque Book Id
     * @source master-record-cheque-id
     * @version v24.3.0.1
     */
    public function get_cheque_book_id_for_cheque_number(int $cheque_number, int $office_bank_id): int
    {

        $cheque_book_id = 0;

        $chequeInjectionLibrary = new ChequeInjectionLibrary();

        $is_injected_cheque_number = $chequeInjectionLibrary->isInjectedChequeNumber($office_bank_id, $cheque_number);

        // $this->read_db->select(['cheque_book_id', 'cheque_book_start_serial_number', 'cheque_book_count_of_leaves']);
        // $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
        // $office_bank_cheque_books_obj = $this->read_db->get('cheque_book');

        $query_builder = $this->read_db->table('cheque_book')
            ->select(['cheque_book_id', 'cheque_book_start_serial_number', 'cheque_book_count_of_leaves'])
            ->where(array('fk_office_bank_id' => $office_bank_id));

        $office_bank_cheque_books_obj = $query_builder->get();

        if ($office_bank_cheque_books_obj->getNumRows() > 0) {
            $cheque_books = $office_bank_cheque_books_obj->getResultArray();

            foreach ($cheque_books as $cheque_book) {
                $cheque_book_pages = range($cheque_book['cheque_book_start_serial_number'], $cheque_book['cheque_book_start_serial_number'] + ($cheque_book['cheque_book_count_of_leaves'] - 1));
                // log_message('error', json_encode($cheque_book_pages));
                if (in_array($cheque_number, $cheque_book_pages)) {
                    $cheque_book_id = $cheque_book['cheque_book_id'];
                    break;
                }
            }
        }

        // We only get to this independent if clause if the leaf is injected and missing in all the books e.g. Bank Slips
        if ($cheque_book_id == 0 && $is_injected_cheque_number == true) {

            // $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
            //$this->read_db->limit(1);
            // $this->read_db->order_by('cheque_book_id desc');
            //$cheque_book_id = $this->read_db->get('cheque_book')->row()->cheque_book_id;


            $query = $this->read_db->table('cheque_book')
                ->where(array('fk_office_bank_id' => $office_bank_id))
                ->limit(1)
                ->orderBy('cheque_book_id desc');
            $cheque_book_id = $query->get()->getRow()->cheque_book_id;

        }

        return $cheque_book_id;
    }

    /**
     * deactivate_chequebook_exemption_expiry_date
     *
     * Deactive expiration date for chequebook exemption
     *
     * @author Nicodemus Karisa Mwambire
     * @reviewed_by None
     * @reviewed_date None
     * @access private
     *
     * @params int $office_bank_id - Office Bank Id
     *
     * @return void
     */

    private function deactivate_chequebook_exemption_expiry_date($office_bank_id): void
    {
        // $this->write_db->where(array('office_bank_id' => $office_bank_id));
        // $this->write_db->update('office_bank', ['office_bank_book_exemption_expiry_date' => null]);

        $query_builder = $this->write_db->table('office_bank')
            ->where(array('office_bank_id' => $office_bank_id))
            ->update(array('office_bank_book_exemption_expiry_date' => null));
    }

    /**
     * is_first_cheque_book(): checks if first cheque book
     * @author  Livingstone Onduso
     * @access public
     * @return void
     * @param int $office_bank_id
     */

    public function is_first_cheque_book(int $office_bank_id): bool
    {
        // $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));

        // $office_bank_cheque_books_obj = $this->read_db->get('cheque_book');

        $query = $this->read_db->table('cheque_book')
            -> where(array('fk_office_bank_id' => $office_bank_id));
        $office_bank_cheque_books_obj = $query->get();

        $is_first_cheque_book = true;

        if ($office_bank_cheque_books_obj->getNumRows() >= 1) {
            $is_first_cheque_book = false;
        }

        return $is_first_cheque_book;
    }
    public function get_active_chequebooks($office_bank_id)
    {
        // $this->read_db->select(array('cheque_book_id'));
        // $this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1));

        // return $this->read_db->get('cheque_book')->num_rows();

        $query_builder = $this->read_db->table('cheque_book')
            ->select(array('cheque_book_id'))
            ->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1));

        return $query_builder->get()->getNumRows();
    }
    public function get_office_chequebooks($office_bank_id)
    {

        //$this->read_db->select(array('cheque_book_id'));
        //$this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
        //return $this->read_db->get('cheque_book')->num_rows();

        $query = $this->read_db->table('cheque_book')
            ->select(array('cheque_book_id'))
            ->where(array('fk_office_bank_id' => $office_bank_id));
        return $query->get()->getNumRows();
    }
    /**
     * get_max_status_office_cheque_book(): will return the max_status_
     * @param int $office_bank_id
     *@return array
     * @author  Livingstone Onduso
     * @access public
     */
    public function get_max_status_office_cheque_book($office_bank_id)
    {
        //$this->read_db->select(['fk_status_id']);
        //$this->read_db->where(['fk_office_bank_id' => $office_bank_id]);
        //$status = $this->read_db->get('cheque_book')->result_array();

        $query = $this->read_db->table('cheque_book')
            ->select(['fk_status_id'])
            ->where(['fk_office_bank_id' => $office_bank_id]);
        $status = $query->get()->getResultArray();

        return $status;

    }

    public function retrieve_office_bank(array $office_ids)
    {

        //$this->read_db->select(array('office_bank_id', 'office_bank_name'));
        //$this->read_db->where_in('fk_office_id', $office_ids);
        //$this->read_db->where(['office_bank_is_active' => 1]);
        //$office_banks = $this->read_db->get('office_bank')->result_array();

        $query_builder = $this->read_db->table('office_bank')
            ->select(array('office_bank_id', 'office_bank_name'))
            ->whereIn('fk_office_id', $office_ids)
            ->where(['office_bank_is_active' => 1]);
        $office_banks = $query_builder->get()->getResultArray();

        //Get bank_office_ids
        $office_bank_id = array_column($office_banks, 'office_bank_id');
        $office_bank_name = array_column($office_banks, 'office_bank_name');

        $office_bank_ids_and_names = array_combine($office_bank_id, $office_bank_name);

        return $office_bank_ids_and_names;
    }

    public function get_active_cheque_book_reset($office_bank_id)
    {

        $get_active_cheque_book_reset = [];

        //$this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_reset_is_active' => 1));
        //$cheque_book_reset = $this->read_db->get('cheque_book_reset');

        $query = $this->read_db->table('cheque_book_reset')
            ->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_reset_is_active' => 1));
        $cheque_book_reset = $query->get();

        if ($cheque_book_reset->num_rows() > 0) {
            $get_active_cheque_book_reset = $cheque_book_reset->row();
        }

        return $get_active_cheque_book_reset;
    }

    public function transaction_validate_duplicates_columns()
    {
        return [
            'fk_office_bank_id',
            'cheque_book_is_active',
            'fk_status_id',
            'cheque_book_start_serial_number',
            'cheque_book_count_of_leaves',
        ];
    }

    public function transaction_validate_by_computation_flag($cheque_book_data)
    {
        //$cheque_book_data['fk_status_id']
        $initial_status = $this->grants_model->initial_item_status('cheque_book');
        $this->read_db->where(array('fk_status_id' => $initial_status, 'fk_office_bank_id' => $cheque_book_data['fk_office_bank_id']));
        $initial_cheque_book_status_count = $this->read_db->get('cheque_book')->num_rows();

        // log_message('error', json_encode($initial_status));

        if ($initial_cheque_book_status_count > 0) {
            return VALIDATION_ERROR;
        } else {
            return VALIDATION_SUCCESS;
        }
    }

    public function officeBankLastChequeSerialNumber($office_bank_id)
    {

        $last_cheque_book_max_leaf = 0;

        // $this->read_db->order_by('cheque_book_start_serial_number DESC');
//        $this->read_db->order_by('cheque_book_id DESC');
//        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));

        $query = $this->read_db->table('cheque_book')
            ->orderBy('cheque_book_id', 'DESC')
            ->where(array('fk_office_bank_id' => $office_bank_id));
        $cheque_book_obj = $query->get();

        if ($cheque_book_obj->getNumRows() > 0) {
            $last_cheque_book = $cheque_book_obj->getRow(0);
            $count_of_leaves = $last_cheque_book->cheque_book_count_of_leaves;
            $last_cheque_book_first_leaf = $last_cheque_book->cheque_book_start_serial_number;
            $last_cheque_book_max_leaf = $last_cheque_book_first_leaf + ($count_of_leaves - 1);
        }

        return $last_cheque_book_max_leaf;
    }

    public function office_bank_start_cheque_serial_number($office_bank_id)
    {

        $min_serial_number = 0;

        $this->read_db->select_min("cheque_book_start_serial_number");
        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
        $min_serial_number_obj = $this->read_db->get('cheque_book');

        if ($min_serial_number_obj->num_rows() > 0) {
            $min_serial_number = $min_serial_number_obj->row()->cheque_book_start_serial_number;
        }

        return $min_serial_number;
    }

    public function single_form_add_visible_columns()
    {
        return [
            'office_bank_name', 'cheque_book_start_serial_number', 'cheque_book_count_of_leaves',
            'cheque_book_use_start_date',
        ];
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

    public function cancelled_cheque_numbers(int $office_bank_id)
    {

        // Only one cheque number is -ve

        $cancelled_cheque_numbers = [];

        $sql = "SELECT voucher_cheque_number, COUNT(*) FROM voucher ";
        $sql .= "WHERE fk_office_bank_id = " . $office_bank_id . " AND voucher_cheque_number < 0 ";
        $sql .= "GROUP BY voucher_cheque_number HAVING COUNT(*) = 1";

        $cancelled_cheque_numbers_obj = $this->read_db->query($sql);

        if ($cancelled_cheque_numbers_obj->num_rows() > 0) {
            $cancelled_cheque_numbers = array_column($cancelled_cheque_numbers_obj->result_array(), 'voucher_cheque_number');
            $cancelled_cheque_numbers = array_map([$this, 'make_unsigned_values'], $cancelled_cheque_numbers);
        }

        return $cancelled_cheque_numbers;
    }

    public function get_used_reused_cheques($office_bank_id, $cheque_numbers_only = true)
    {
        // three cheque leaves

        $used_reused_cheque_numbers = [];

        $sql = "SELECT abs(voucher_cheque_number) as voucher_cheque_number, COUNT(*) as count FROM voucher ";
        if ($cheque_numbers_only) {
            $sql .= "JOIN voucher_type ON voucher.fk_voucher_type_id=voucher_type.voucher_type_id ";
            $sql .= "WHERE voucher_type_is_cheque_referenced = 1 ";
        } else {
            $sql .= "WHERE voucher_type_is_cheque_referenced = 0 ";
        }
        $sql .= "AND voucher_cheque_number REGEXP '^[-+]?[0-9]+$' AND fk_office_bank_id = " . $office_bank_id . " ";
        $sql .= "AND (voucher_cheque_number > 0 OR voucher_cheque_number < 0) ";
        $sql .= "GROUP BY abs(voucher_cheque_number) HAVING COUNT(*) IN (3,5,7,9,11) ";

        $used_reused_cheque_numbers_obj = $this->read_db->query($sql);

        if ($used_reused_cheque_numbers_obj->num_rows() > 0) {

            $used_reused_cheque_numbers = array_column($used_reused_cheque_numbers_obj->result_array(), 'voucher_cheque_number');
            $used_reused_cheque_numbers = array_map([$this, 'make_unsigned_values'], $used_reused_cheque_numbers);
        }

        return $used_reused_cheque_numbers;
    }

    public function get_unused_reused_cheques($office_bank_id, $cheque_numbers_only = true)
    {
        $all_reused_cheques = $this->get_reused_cheques($office_bank_id, $cheque_numbers_only);
        $used_reused_cheques = $this->get_used_reused_cheques($office_bank_id, $cheque_numbers_only);

        $unused_reused_cheques = [];

        if (count($all_reused_cheques) > 0 && count($used_reused_cheques) > 0) {
            // Array diff has proved to be inaccurate for reasons not known therefore loop was used

            // $unused_reused_cheques = array_diff($all_reused_cheques,  $used_reused_cheques);
            foreach ($all_reused_cheques as $reused_cheque) {
                if (!in_array($reused_cheque, $used_reused_cheques)) {
                    $unused_reused_cheques[] = $reused_cheque;
                }
            }
        }

        // log_message('error', json_encode(['unused_reused_cheques' => $unused_reused_cheques,'all_reused_cheques' => $all_reused_cheques, 'used_reused_cheques' => $used_reused_cheques ]));

        return $unused_reused_cheques;
    }

    /**
     * This method counts the number of reuesed of cancalled cheque transactions.
     * The name needs to be update since its misleading
     */
    public function getReusedChequeCount($office_bank_id, $cheque_number, $reusing_and_cancel_eft_or_chq = '')
    {

        // log_message('error', json_encode([$office_bank_id,$cheque_number]));
        $cheque_number = is_int($cheque_number) && $cheque_number > 0 ? -$cheque_number : 0;
        $cancelled_cheque_numbers = [];

        $count = 0;

        if ($cheque_number != 0) {
            $voucherReadBuilder = $this->read_db->table('voucher');
            $voucherReadBuilder->select('voucher_cheque_number');
            //Added by Onduso on 26th May 2023
            $voucherReadBuilder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');

            if ($reusing_and_cancel_eft_or_chq == 'cheque') {

                $voucherReadBuilder->where(array('voucher_type_is_cheque_referenced' => 1)); //get Only chq numbers and NOT Eft numbers
            } else if ($reusing_and_cancel_eft_or_chq == 'eft') {
                $voucherReadBuilder->where(array('voucher_type_is_cheque_referenced' => 0)); //get Only eft numbers and NOT chq numbers
            }

            //End
            $voucherReadBuilder->where(array('voucher_cheque_number' => $cheque_number, 'fk_office_bank_id' => $office_bank_id));
            $cancelled_cheque_numbers_obj = $voucherReadBuilder->get();

            // $cancelled_cheque_numbers_obj = $this->read_db->query($sql);

            if ($cancelled_cheque_numbers_obj->getNumRows() > 0) {
                $cancelled_cheque_numbers = array_column($cancelled_cheque_numbers_obj->result_array(), 'voucher_cheque_number');
                $cancelled_cheque_numbers = array_map([$this, 'make_unsigned_values'], $cancelled_cheque_numbers);

                $count = count($cancelled_cheque_numbers);
            }
        }

        // log_message('error', json_encode($count));

        return $count;
    }

    public function get_reused_cheques($office_bank_id, $cheque_numbers_only = true)
    {
        // two cheque numbers are -ve

        $reused_cheque_numbers = [];

        $sql = "SELECT voucher_cheque_number, COUNT(*) FROM voucher ";
        if ($cheque_numbers_only) {
            $sql .= "JOIN voucher_type ON voucher.fk_voucher_type_id=voucher_type.voucher_type_id ";
            $sql .= "WHERE voucher_type_is_cheque_referenced = 1 ";
        } else {
            $sql .= "WHERE voucher_type_is_cheque_referenced = 0 ";
        }
        $sql .= "AND fk_office_bank_id = " . $office_bank_id . " AND voucher_cheque_number < 0 ";
        $sql .= "GROUP BY voucher_cheque_number HAVING COUNT(*) IN (2,4,6,8,10) "; // This means a cheque leaf can be resused 5 times maximum after which it wont appear in the pool of cheque leaves when raising a voucher

        // $this->read_db->where(['fk_office_bank_id'=>$office_bank_id]);
        // $reused_cheque_numbers_obj = $this->read_db->get('re_used_cheques');

        $reused_cheque_numbers_obj = $this->read_db->query($sql);

        // log_message('error', json_encode($reused_cheque_numbers_obj->result_array()));

        if ($reused_cheque_numbers_obj->num_rows() > 0) {
            $reused_cheque_numbers = array_column($reused_cheque_numbers_obj->result_array(), 'voucher_cheque_number');
            $reused_cheque_numbers = array_map([$this, 'make_unsigned_values'], $reused_cheque_numbers);
        }

        return $reused_cheque_numbers;
    }

    public function get_cancelled_cheques($office_bank_id)
    {
        // two cheque numbers are -ve

        $cancelled_cheque_numbers = [];

        $sql = "SELECT voucher_cheque_number, COUNT(*) FROM voucher ";
        $sql .= "JOIN voucher_type ON voucher_type.voucher_type_id=voucher.fk_voucher_type_id ";
        $sql .= "WHERE fk_office_bank_id = " . $office_bank_id . " AND voucher_cheque_number < 0 " . " AND voucher_type_is_cheque_referenced = 1 ";
        $sql .= "GROUP BY voucher_cheque_number HAVING COUNT(*) IN (1,3,5,7,9) "; // This means a cheque leaf can be cancelled 5 times maximum after which it wont be injectable

        $cancelled_cheque_numbers_obj = $this->read_db->query($sql);

        if ($cancelled_cheque_numbers_obj->num_rows() > 0) {
            $cancelled_cheque_numbers = array_column($cancelled_cheque_numbers_obj->result_array(), 'voucher_cheque_number');
            $cancelled_cheque_numbers = array_map([$this, 'make_unsigned_values'], $cancelled_cheque_numbers);
        }

        return $cancelled_cheque_numbers;
    }
    /**
     *already_injected(): Checks if the cheque has been injected
     * @author Livingstone Onduso: Dated 20-06-2023
     * @access public
     * @return int - echo already_injected string
     * @param int $office_bank_id, $cheque_number
     */
    public function injected_cheque_exists(int $office_bank_id, int $cheque_number): int
    {
        $this->read_db->select(array('cheque_injection_number'));
        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_injection_number' => $cheque_number));

        $cheque_no = $this->read_db->get('cheque_injection')->row_array();

        return $cheque_no ? 1 : 0;
    }
    /**
     *count_of_cancelled_chqs_more_than_three(): Checks count of cancelled cheques
     * @author Livingstone Onduso: Dated 12-06-2023
     * @access public
     * @return int - returns count of cancelled chqs
     * @param int $office_bank_id, $cheque_number
     */
    public function is_leaf_cancelled_chqs_more_than_threshold(int $office_bank_id, int $cheque_number): int
    {
        //Get number of canceled checks
        $sql = "SELECT voucher_cheque_number, COUNT(*) AS count_chqs FROM voucher ";
        $sql .= "JOIN voucher_type ON voucher_type.voucher_type_id=voucher.fk_voucher_type_id ";
        $sql .= "WHERE fk_office_bank_id = " . $office_bank_id . " AND voucher_cheque_number =-$cheque_number " . " AND voucher_type_is_cheque_referenced = 1 ";
        $sql .= "GROUP BY voucher_cheque_number HAVING COUNT(*)  IN (1,3,5,7,9) ";

        $cancelled_cheque_numbers_obj = $this->read_db->query($sql);

        $cancelled_chqs = $cancelled_cheque_numbers_obj->row_array();

        $count_cancelled_chqs = 0;

        if ($cancelled_chqs) {
            $count_cancelled_chqs = $cancelled_chqs['count_chqs'];
        }

        return $count_cancelled_chqs >= 3 ? true : false;
    }

    public function cheque_to_be_injected_exists_in_range(int $office_bank_id, int $cheque_number_to_inject)
    {
        $message = "You can\'t inject the cheque number " . $cheque_number_to_inject . " due to the following reasons: \n";
        // Check if cheque is used/opening outstanding - Should not Inject a Used Leaf
        $used_cheque_leaves = $this->get_used_cheque_leaves($office_bank_id);
        // Check if cheque is cancelled - Should inject a cancelled leaf
        $cancelled_cheque_numbers = $this->cancelled_cheque_numbers($office_bank_id);
        // Check if reused cheque leaf - Should not inject reused cheque leaf
        $all_reused_cheques = $this->get_reused_cheques($office_bank_id);
        // Cancelled beyond threshold
        $count_of_chqs_greater_than_threshold = $this->is_leaf_cancelled_chqs_more_than_threshold($office_bank_id, $cheque_number_to_inject);

        $this->load->model('cancel_cheque_model');
        $unused_cheque_leaves = array_column($this->cancel_cheque_model->get_valid_cheques($office_bank_id), 'cheque_number');

        $is_injectable = true;

        if (
            in_array($cheque_number_to_inject, $all_reused_cheques)
        ) {
            $message .= " -> The cheque number is marked for reuse\n";
            $is_injectable = false;
        }

        // log_message('error', json_encode($unused_cheque_leaves));
        if (in_array($cheque_number_to_inject, $unused_cheque_leaves)) {
            $message .= " -> The cheque number provided is yet to be used within the current cheque leaves pool \n";
            $is_injectable = false;
        }

        if (
            in_array($cheque_number_to_inject, $used_cheque_leaves) &&
            !in_array($cheque_number_to_inject, $cancelled_cheque_numbers)
        ) {
            $message .= " -> The cheque number is already used and is not cancelled \n";
            $is_injectable = false;
        }

        if ($count_of_chqs_greater_than_threshold) {
            $message .= " -> The cheque number has been cancelled above the required threshold \n";
            $is_injectable = false;
        }

        $response = ['is_injectable' => $is_injectable, 'message' => $message];

        if ($is_injectable) {
            $message = '';
            $response = ['is_injectable' => $is_injectable, 'message' => $message];
        }

        return $response;
    }

    /**
     *cheque_to_be_injected_exists_in_range(): Finds the cheques in a range of existing cheque books
     * @author Livingstone Onduso: Dated 08-06-2023
     * @access public
     * @return int - Returns and integer
     * @param int $office_bank_id, int $cheque_number
     */

    // public function cheque_to_be_injected_exists_in_range(int $office_bank_id, int $cheque_number_to_inject)
    // {

    //     //Get the cheques in the range
    //     $this->read_db->select(array('cheque_book_start_serial_number', 'cheque_book_count_of_leaves'));

    //     $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));

    //     $cheque_books_obj = $this->read_db->get('cheque_book');
    //     $cheque_numbers = [];

    //     if ($cheque_books_obj->num_rows() > 0) {

    //         $cheque_books = $cheque_books_obj->result_array();

    //         foreach ($cheque_books as $cheque_book) {

    //             $cheque_numbers[] = range($cheque_book['cheque_book_start_serial_number'], $cheque_book['cheque_book_start_serial_number'] + ($cheque_book['cheque_book_count_of_leaves'] - 1));
    //         }
    //     }

    //     //If that cheque number is the voucher and has been cancelled should be removed from range
    //     $this->read_db->select(['voucher_cheque_number']);
    //     $this->read_db->where(['voucher_cheque_number' => -$cheque_number_to_inject, 'fk_office_bank_id' => $office_bank_id, 'voucher_type_is_cheque_referenced' => 1]);
    //     $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');

    //     $chq_number_result = $this->read_db->get('voucher')->result_array();

    //     //Remove the cheque number value

    //     if (!empty($chq_number_result)) {

    //         $cheque_range_with_cancelled_chq = [];

    //         foreach ($cheque_numbers as $key => $cheque_number) {

    //             $abs_chq_number=abs($chq_number_result[0]['voucher_cheque_number']);

    //             $key_value=array_search($abs_chq_number,$cheque_number);

    //             //Unset the cancelled cheque and store the array after unseting and break the loop
    //             if($key_value>0 && $key_value!=null){

    //                 unset($cheque_number[$key_value]);

    //                 unset($cheque_numbers[$key]);

    //                 $cheque_range_with_cancelled_chq =$cheque_number;

    //                 break;
    //             }
    //         }

    //     }
    //     //Return back the array that we removed cancelled chq value
    //     $cheque_numbers[]=array_values($cheque_range_with_cancelled_chq);

    //     //Outstanding Opening Chqs and group them
    //    $this->read_db->select(['opening_outstanding_cheque_number']);
    //     $this->read_db->where(['fk_office_bank_id' => $office_bank_id]);
    //     $outstanding_chqs = $this->read_db->get('opening_outstanding_cheque')->result_array();

    //     $regroup_chqs = [];

    //     if ($outstanding_chqs) {

    //         foreach ($outstanding_chqs as $outstanding_chq_arr) {
    //             $regroup_chqs[] = [(int)$outstanding_chq_arr['opening_outstanding_cheque_number']];
    //         }
    //     }
    //     //Merge Outstanding chqs with chqs in the range

    //     $cheque_numbers = array_merge($cheque_numbers, $regroup_chqs);

    //     $found_chq_number = 0;

    //     foreach ($cheque_numbers as $cheque_number) {

    //         if (in_array($cheque_number_to_inject, $cheque_number)) {
    //             $found_chq_number = 1;
    //             break;
    //         }
    //     }

    //     return $found_chq_number;
    // }

    /**
     *negate_cheque_number(): Updates the voucher record by negating the cancelled cheque
     * @author Livingstone Onduso: Dated 17-06-2023
     * @access public
     * @return int - returns 1 when update or 0 when not
     * @param int $office_bank_id, $cheque_number
     */
    public function negate_cheque_number(int $office_bank_id, int $cheque_number): int
    {

        //Get the check exists in voucher
        $this->read_db->select(['voucher_id']);
        $this->read_db->where(['fk_office_bank_id' => $office_bank_id, 'voucher_cheque_number' => $cheque_number, 'voucher_type_is_cheque_referenced' => 1]);
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $record_to_update = $this->read_db->get('voucher')->result_array();

        if (count($record_to_update) > 0) {

            $data['voucher_cheque_number'] = -$cheque_number;

            //$this->write_db->where($cheque_condition);
            $this->write_db->where(['voucher_id' => $record_to_update[0]['voucher_id']]);
            $this->write_db->update('voucher', $data);

            return 1;
        }
        return 0;
    }

    public function make_unsigned_values($signed_cheque_number)
    {
        return  is_int($signed_cheque_number) || is_float($signed_cheque_number) ? abs($signed_cheque_number) : '';
    }

    public function get_used_cheque_leaves($office_bank_id)
    {

        $opening_outstanding_cheques_used_cheque_leaves = $this->opening_outstanding_cheques_used_cheque_leaves($office_bank_id);

        $this->read_db->select(array('voucher_cheque_number'));
        $this->read_db->where_in('voucher_type_effect_code', ['expense', 'bank_contra', 'bank_to_bank_contra']);
        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'voucher_type_account_code' => 'bank', 'voucher_cheque_number > ' => 0));
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $this->read_db->order_by('voucher_cheque_number ASC');
        $used_cheque_leaves_obj = $this->read_db->get('voucher');

        $used_cheque_leaves = [];

        if ($used_cheque_leaves_obj->num_rows() > 0) {
            $used_cheque_leaves = array_column($used_cheque_leaves_obj->result_array(), 'voucher_cheque_number');
        }

        // Add the opening outstanding cheques to the list of used cheque leaves
        if (!empty($opening_outstanding_cheques_used_cheque_leaves)) {
            $used_cheque_leaves = array_merge($used_cheque_leaves, $opening_outstanding_cheques_used_cheque_leaves);
        }
        // log_message('error', json_encode($used_cheque_leaves));
        return $used_cheque_leaves;
    }

    public function checkIfPreviousBookIsApproved($office_bank_id)
    {
        $isPreviousBookApproved = true;

        $cheque_book_max_status = $this->general_model->get_max_approval_status_id('cheque_book');

        // log_message('error', json_encode($cheque_book_max_status)); ;;;

        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
        $this->read_db->where_not_in('fk_status_id', $cheque_book_max_status);
        $unapproved_books_count = $this->read_db->get('cheque_book')->num_rows();

        if ($unapproved_books_count > 0) {
            $isPreviousBookApproved = false;
        }

        return $isPreviousBookApproved;
    }

    public function get_all_approved_active_cheque_books_leaves($office_bank_id, $cheque_numbers_only = true)
    {

        // You can only have 1 approved active cheque book

        $max_status_ids = $this->general_model->get_max_approval_status_id('cheque_book');

        $injected_cheque_leaves = $this->cheque_injection_model->get_injected_cheque_leaves($office_bank_id);

        $unused_reused_cheques = $this->get_unused_reused_cheques($office_bank_id, $cheque_numbers_only);

        //NEW CODE: COMMENTED OUT WITH ONDUSO on 30th May 2023
        //if ($chequebk_status == 'inactive_book') {

        $this->read_db->where_in('cheque_book.fk_status_id', $max_status_ids);
        $this->read_db->select(array('cheque_book_start_serial_number', 'cheque_book_count_of_leaves'));
        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1));

        //$all_chqbooks_inactive_and_active = $this->read_db->get('cheque_book')->result();
        //}else if($chequebk_status == 'active_book'){

        //$this->read_db->where_in('cheque_book.fk_status_id', $max_status_ids);
        //$this->read_db->select(array('cheque_book_start_serial_number', 'cheque_book_count_of_leaves'));
        //$this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1));

        $all_chqbooks_inactive_and_active = $this->read_db->get('cheque_book')->result();
        //}

        //$this->read_db->where(array('fk_office_bank_id' => $office_bank_id ));
        // $all_chqbooks_inactive_and_active = $this->read_db->get('cheque_book')->result();
        $this->read_db->where_in('cheque_book.fk_status_id', $max_status_ids);
        $this->read_db->select(array('cheque_book_start_serial_number', 'cheque_book_count_of_leaves'));
        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1));
        $cheque_book = $this->read_db->get('cheque_book');

        $all_cheque_leaves = [];

        $reorganized_all_cheque_leaves = [];

        if (!empty($all_chqbooks_inactive_and_active)) {
            // Count of leaves in the active cheque book

            //$all_chqbooks_inactive_and_active=$cheque_book->result_array();
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

        // if(count($reorganized_all_cheque_leaves)>0){
        //     sort($reorganized_all_cheque_leaves);
        // }

        sort($reorganized_all_cheque_leaves);

        return array_unique($reorganized_all_cheque_leaves);

        //END OF NEW CODE

        /*OLD CODE: COMMENTED OUT WITH ONDUSO on 30th May 2023

    $this->read_db->where_in('cheque_book.fk_status_id', $max_status_ids);
    $this->read_db->select(array('cheque_book_start_serial_number', 'cheque_book_count_of_leaves'));
    $this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1));
    $cheque_book = $this->read_db->get('cheque_book');

    //return $cheque_book->result_array();
    // Sum of all pages - Just incase we have more than i active cheque book
    $sum_leaves_count_for_all_books = array_sum(array_column($cheque_book->result_array(), 'cheque_book_count_of_leaves'));

    $all_cheque_leaves = [];

    if ($cheque_book->num_rows() > 0) {
    // Count of leaves in the active cheque book
    $sum_leaves_count_for_all_books = $cheque_book->row(0)->cheque_book_count_of_leaves;

    // Just incase we have more than 1 active cheque book, get the first one
    $cheque_book_start_serial_number = $cheque_book->row(0)->cheque_book_start_serial_number;

    $last_leaf = $cheque_book_start_serial_number + ($sum_leaves_count_for_all_books - 1);
    $all_cheque_leaves = range($cheque_book_start_serial_number, $last_leaf);
    }

    // Add inject cheques to the list of all cheques OLD CODE
    if (count($injected_cheque_leaves)>0) {
    $all_cheque_leaves = array_merge($all_cheque_leaves, $injected_cheque_leaves);
    }

    if(count($unused_reused_cheques)>0){

    // Add inject cheques to the list of all cheques
    if (!empty($injected_cheque_leaves)) {
    $all_cheque_leaves = array_merge($all_cheque_leaves, $injected_cheque_leaves);
    }

    if (!empty($unused_reused_cheques)) {
    $all_cheque_leaves = array_merge($all_cheque_leaves, $unused_reused_cheques);
    }

    sort($all_cheque_leaves);

    return $all_cheque_leaves;*/
    }

    public function get_remaining_unused_cheque_leaves($office_bank_id, $cheque_numbers_only = true)
    {
        $all_cheque_leaves = $this->get_all_approved_active_cheque_books_leaves($office_bank_id, $cheque_numbers_only);

        // log_message('error', json_encode($all_cheque_leaves));

        $leaves = [];

        if (!empty($all_cheque_leaves)) {

            $used_cheque_leaves = $this->get_used_cheque_leaves($office_bank_id);
            $cancelled_cheque_numbers = $this->cancelled_cheque_numbers($office_bank_id);

            // log_message('error', json_encode(['all_cheque_leaves' => $all_cheque_leaves,'used_cheque_leaves' => $used_cheque_leaves, 'cancelled_cheque_numbers' => $cancelled_cheque_numbers]));

            foreach ($all_cheque_leaves as $cheque_number) {

                // $is_injected_cheque_number = $this->cheque_injection_model->is_injected_cheque_number($office_bank_id, $cheque_number);

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
                //if(in_array($cheque_leaf,$opening_outstanding_cheques_used_cheque_leaves)) continue;
                $keyed_cheque_leaves[$cnt]['cheque_id'] = $cheque_leaf;
                $keyed_cheque_leaves[$cnt]['cheque_number'] = $cheque_leaf;

                if (!$this->allow_skipping_of_cheque_leaves()) {
                    break;
                }

                $cnt++;
            }

            $leaves = $keyed_cheque_leaves;
        }

        return $leaves;
    }

    public function allow_skipping_of_cheque_leaves()
    {

        $is_skipping_of_cheque_leaves_allowed = true;

        if ($this->config->item("allow_skipping_of_cheque_leaves") == false
            //|| $this->get_cheque_book_account_system_setting('allow_skipping_of_cheque_leaves') == 0
        ) {
            $is_skipping_of_cheque_leaves_allowed = false;
        }

        return $is_skipping_of_cheque_leaves_allowed;
    }

    // function get_cheque_book_account_system_setting($setting_key)
    // {
    //     $account_system_setting = $this->cheque_book_account_system_setting();

    //     return isset($account_system_setting[$setting_key]) ? $account_system_setting[$setting_key] : [];
    // }

    // function cheque_book_account_system_setting()
    // {

    //     $account_system_setting = [];

    //     $this->read_db->select(["account_system_setting_name", "account_system_setting_value"]);
    //     $this->read_db->where(["approve_item_name" => "cheque_book", 'fk_account_system_id' => $this->session->user_account_system_id]);
    //     $this->read_db->join("approve_item", "approve_item.approve_item_id=account_system_setting.fk_approve_item_id");
    //     $account_system_setting_obj = $this->read_db->get('account_system_setting');

    //     if ($account_system_setting_obj->num_rows() > 0) {
    //         $account_system_setting_array = $account_system_setting_obj->result_array();

    //         $account_system_setting_name = array_column($account_system_setting_array, "account_system_setting_name");
    //         $account_system_setting_value = array_column($account_system_setting_array, "account_system_setting_value");

    //         $account_system_setting = array_combine($account_system_setting_name, $account_system_setting_value);
    //     }

    //     return $account_system_setting;
    // }

    public function opening_outstanding_cheques_used_cheque_leaves($office_bank_id)
    {
        $post = $this->input->post();

        $opening_outstanding_cheques_array = [];

        $this->read_db->select(array('opening_outstanding_cheque_number'));
        $this->read_db->where(array('opening_outstanding_cheque.fk_office_bank_id' => $office_bank_id));
        $opening_outstanding_cheques_obj = $this->read_db->get('opening_outstanding_cheque');

        if ($opening_outstanding_cheques_obj->num_rows() > 0) {
            $opening_outstanding_cheques = $opening_outstanding_cheques_obj->result_array();

            $opening_outstanding_cheques_array = array_column($opening_outstanding_cheques, 'opening_outstanding_cheque_number');
        }

        return $opening_outstanding_cheques_array;
    }

    public function get_max_id_cheque_book_for_office($office_bank_id)
    {

        $this->read_db->select_max('cheque_book_id');
        $this->read_db->join('office_bank', 'office_bank_id=fk_office_bank_id');
        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
        $max_id = $this->read_db->get('cheque_book')->row_array();

        return $max_id['cheque_book_id'];
    }

    public function post_cheque_book($data)
    {

        $rs = $this->action_before_insert($data);

        if (!empty($data)) {
            $this->write_db->insert('cheque_book', $data['header']);
            $insert_id = $this->write_db->insert_id();

            return $insert_id;
        }
    }

    /**
     * get_current_status(): returns the  status id
     * @author Livingstone;
     * @access public
     * @return int
     */
    public function get_current_status(int $chequeBook_id):int
    {

        $this->write_db->select(['fk_status_id']);

        $this->write_db->where(['cheque_book_id' => $chequeBook_id]);

        return $this->write_db->get('cheque_book')->row()->fk_status_id;

    }

    public function list_table_where()
    {
        // Use the Office hierarchy for the logged user if not system admin
        if (!$this->session->system_admin) {
            $office_ids = array_column($this->session->hierarchy_offices, 'office_id');
            $this->read_db->where_in('fk_office_id', $office_ids);
        }
    }

    public function list_table_visible_columns()
    {
        return [
            'cheque_book_track_number',
            'office_bank_name',
            'cheque_book_use_start_date',
            'cheque_book_is_active',
            'cheque_book_start_serial_number',
            'cheque_book_count_of_leaves',
        ];
    }

    public function edit_visible_columns()
    {
        $has_voucher_create_permission = $this->user_model->check_role_has_permissions('Voucher', 'create');
        $cheque_book_is_active = !$has_voucher_create_permission ? 'cheque_book_is_active' : '';

        return [
            'office_bank_name',
            'cheque_book_use_start_date',
            'cheque_book_start_serial_number',
            'cheque_book_count_of_leaves',
            $cheque_book_is_active,
        ];
    }

    // public function redirect_to_voucher_after_approval()
    // {
    //     $cheque_book_id = hash_id($this->id, 'decode');

    //     $this->write_db->where(array('cheque_book_id' => $cheque_book_id));
    //     $current_status_id = $this->write_db->get('cheque_book')->row()->fk_status_id;

    //     $has_voucher_create_permission = $this->user_model->check_role_has_permissions('Voucher', 'create');
    //     $max_cheque_book_status_ids = $this->general_model->get_max_approval_status_id('Cheque_book');
    //     $next_status_id = $this->general_model->next_status($current_status_id);

    //     $is_next_status_full_approval = in_array($next_status_id,$max_cheque_book_status_ids) ? true : false;

    //     if($has_voucher_create_permission && $is_next_status_full_approval){
    //         $redirect_to_voucher_form = base_url() . 'voucher/multi_form_add';

    //         header("Location:" . $redirect_to_voucher_form);
    //     }

    // }

    public function post_approval_action_event($payload)
    {
        $cheque_book_id = $payload['post']['item_id'];

        $max_cheque_book_status_ids = $this->general_model->get_max_approval_status_id('Cheque_book');

        // Update the cheque_book_is_active to 1
        if (in_array($payload['post']['next_status'], $max_cheque_book_status_ids)) {
            $data['cheque_book_is_active'] = 1;
            $this->write_db->where(array('cheque_book_id' => $cheque_book_id));
            $this->write_db->update('cheque_book', $data);
        }

    }

    public function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        if (!$this->session->system_admin) {
            $hierarchy_offices = array_column($this->session->hierarchy_offices, 'office_id');

            $this->read_db->select(array('office_bank_id', 'office_bank_name'));
            $this->read_db->where_in('fk_office_id', $hierarchy_offices);
            $lookup_values['office_bank'] = $this->read_db->get('office_bank')->result_array();
        }

        return $lookup_values;
    }

    public function check_active_cheque_book_for_office_bank_exist($office_bank_id)
    {

        //select * from cheque_book JOIN office_bank ON (office_bank.office_bank_id=cheque_book.fk_office_bank_id)
        //JOIN office ON(office.office_id=office_bank.fk_office_id) where office.office_id=19

        //$this->read_db->join('office_bank','office_bank.office_bank_id=cheque_book.fk_office_bank_id');
        //$this->read_db->join('office','office.office_id=office_bank.fk_office_id');
        $this->read_db->where(array('cheque_book.fk_office_bank_id' => $office_bank_id, 'cheque_book.cheque_book_is_active' => 1));
        return $this->read_db->get('cheque_book');

        // return ['cheque_bk'=>1];

        //echo json_encode($response);
    }

    public function deactivate_non_default_office_bank_cheque_books($office_id)
    {

        $cheque_book_ids = [];

        $this->read_db->select(array('cheque_book_id'));
        $this->read_db->where(array('office_bank_is_default' => 0, 'cheque_book_is_active' => 1, 'fk_office_id' => $office_id));
        $this->read_db->join('office_bank', 'office_bank.office_bank_id=cheque_book.fk_office_bank_id');
        $cheque_book_obj = $this->read_db->get('cheque_book');

        if ($cheque_book_obj->num_rows() > 0) {
            $cheque_book_ids = array_column($cheque_book_obj->result_array(), 'cheque_book_id');
        }

        $this->write_db->where_in('cheque_book_id', $cheque_book_ids);
        $data['cheque_book_is_active'] = 0;
        $this->write_db->update('cheque_book', $data);
    }

    public function deactivate_cheque_book($office_bank_id)
    {

        $success = true;

        $max_status_id = $this->general_model->get_max_approval_status_id('Cheque_book');

        // log_message('error', json_encode($max_status_id));

        $condition = array('fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1);

        $this->read_db->where($condition);
        // $this->read_db->where_in('fk_status_id', $max_status_id);
        $max_approved_active_book_count = $this->read_db->get('cheque_book')->num_rows();

        if ($max_approved_active_book_count > 0) {
            $data['cheque_book_is_active'] = 0;
            $data['fk_status_id'] = $max_status_id[0]; // Make all deactivated book be fully approved automatically
            $this->write_db->where($condition);
            // $this->read_db->where_in('fk_status_id', $max_status_id);
            $this->write_db->update('cheque_book', $data);

            if ($this->write_db->affected_rows() > 0) {
                $success = true;
            }
        }

        return $success;
    }

    public function getActiveChequeBookReset($office_bank_id)
    {

        $get_active_cheque_book_reset = [];

        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_reset_is_active' => 1));
        $cheque_book_reset = $this->read_db->get('cheque_book_reset');

        if ($cheque_book_reset->num_rows() > 0) {
            $get_active_cheque_book_reset = $cheque_book_reset->row();
        }

        return $get_active_cheque_book_reset;
    }

}
<?php 

namespace App\Libraries\Grants;

use App\Libraries\Core\StatusLibrary;
use App\Libraries\Core\UserLibrary;
use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ChequeBookModel;
use Config\Session;

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
        $statusLibrary = new StatusLibrary();
        $max_status_ids = $statusLibrary->getMaxApprovalStatusId('cheque_book');
        $chequeInjectionLibrary = new \App\Libraries\Grants\ChequeInjectionLibrary();
        $injected_cheque_leaves = $chequeInjectionLibrary->getInjectedChequeLeaves($office_bank_id);
        $unused_reused_cheques = $this->getUnusedReusedCheques($office_bank_id, $cheque_numbers_only);

        $builder = $this->read_db->table('cheque_book');
        $builder->whereIn('cheque_book.fk_status_id', $max_status_ids);
        $builder->select(array('cheque_book_start_serial_number', 'cheque_book_count_of_leaves'));
        // 'cheque_book_is_active' => 1 was removed when resolving bug DE4583 where reverted leaves of cheques that are closed were missing 
        // in the voucher and cancel cheque leaves pool 
        $builder->where(array('fk_office_bank_id' => $office_bank_id));
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

        $statusLibrary = new StatusLibrary();
        
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

    public function actionBeforeInsert($post_array): array
    {

        $chequeBookResetLibrary = new \App\Libraries\Grants\ChequeBookResetLibrary();
        $office_bank_id = $post_array['header']['fk_office_bank_id'];
        $count_remaining_unused_cheque_leaves = count($this->getRemainingUnusedChequeLeaves($office_bank_id));
        $this->deactivateChequebookExemptionExpiryDate($office_bank_id);
        
        // Check if we have an active cheque book reset and deactivate it
        $active_cheque_book_reset = $this->getActiveChequeBookReset($office_bank_id);

        if ($count_remaining_unused_cheque_leaves == 0 || !empty($active_cheque_book_reset)) {
            $this->deactivateChequebook($office_bank_id);

            if(!empty($active_cheque_book_reset)){
                $chequeBookResetLibrary->deactivateChequeBookReset($office_bank_id);
            }
        }else{
            return ['flag' => false, 'message' => get_phrase('active_cheque_book_present', 'All cheque books MUST be used up to allow creating a new cheque book')];
        }

        return $post_array;
    }

    public function actionBeforeEdit($post_array): array
    {

        // Disallow edit when the first leaf of a cheque book has already been used
        $office_bank_id = $post_array['header']['fk_office_bank_id'];
        $cheque_book_start_serial_number = $post_array['header']['cheque_book_start_serial_number'];

        $voucherReadBuilder = $this->read_db->table('voucher');

        $voucherReadBuilder->where(array('fk_office_bank_id' => $office_bank_id, 'voucher_cheque_number >=' => $cheque_book_start_serial_number));
        $count_initial_voucher_for_cheque_book = $voucherReadBuilder->get()->getNumRows();

        if ($count_initial_voucher_for_cheque_book > 0) {
            return ['error' => get_phrase('edit_used_cheque_book_not_allowed', 'You can\'t edit a chequebook that has atleast one of it\'s leaf used in a transaction')];
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
            $query = $this->read_db->table('cheque_book');
            $query->where(array('fk_office_bank_id' => $office_bank_id));
            $query->limit(1);
            $query->orderBy('cheque_book_id desc');
            $cheque_book_id = $query->get()->getRow()->cheque_book_id;

        }

        return $cheque_book_id;
    }

    

    /**
     * deactivateChequebookExemptionExpiryDate
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

    private function deactivateChequebookExemptionExpiryDate($office_bank_id): void
    {
        $query_builder = $this->write_db->table('office_bank');
        $query_builder->where(array('office_bank_id' => $office_bank_id));
        $query_builder->update(array('office_bank_book_exemption_expiry_date' => null));
    }

    /**
     * is_first_cheque_book(): checks if first cheque book
     * @author  Livingstone Onduso
     * @access public
     * @return void
     * @param int $office_bank_id
     */

    public function isFirstChequeBook(int $office_bank_id): bool
    {
        $query = $this->read_db->table('cheque_book');
        $query-> where(array('fk_office_bank_id' => $office_bank_id));
        $office_bank_cheque_books_obj = $query->get();

        $is_first_cheque_book = true;

        if ($office_bank_cheque_books_obj->getNumRows() > 0) {
            $is_first_cheque_book = false;
        }

        return $is_first_cheque_book;
    }

    public function postChequeBook($data)
    {
        $postArray  = $this->actionBeforeInsert($data);

        $chequeBookWriteBuilder = $this->write_db->table('cheque_book');

        if(array_key_exists('flag', $postArray) && !$postArray['flag']){
            return false;
        }

        if (!empty($data)) {
            $chequeBookWriteBuilder->insert( $data['header']);
            $insertId = $this->write_db->insertID();
            return $insertId;
        }
        return null;
    }

    public function getActiveChequeBooks($office_bank_id)
    {
        $chequeBookReadBuilder = $this->read_db->table('cheque_book');

        $chequeBookReadBuilder->select(array('cheque_book_id'));
        $chequeBookReadBuilder->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_is_active' => 1));
        return $chequeBookReadBuilder->get()->getNumRows();
    }

    public function getMaxIdChequeBookForOffice($office_bank_id)
    {

        $chequeBookReadBuilder = $this->read_db->table('cheque_book');

        $chequeBookReadBuilder->selectMax('cheque_book_id');
        $chequeBookReadBuilder->join('office_bank', 'office_bank_id=fk_office_bank_id');
        $chequeBookReadBuilder->where(array('fk_office_bank_id' => $office_bank_id));
        $max_id = $chequeBookReadBuilder->get()->getRowArray();

        return $max_id['cheque_book_id'];
    }

    public function getOfficeChequeBooks($office_bank_id)
    {

        $query = $this->read_db->table('cheque_book');
        $query->select(array('cheque_book_id'));
        $query->where(array('fk_office_bank_id' => $office_bank_id));
        return $query->get()->getNumRows();
    }

    public function get_max_status_office_cheque_book($office_bank_id)
    {

        $query = $this->read_db->table('cheque_book');
        $query->select(['fk_status_id']);
        $query->where(['fk_office_bank_id' => $office_bank_id]);
        $status = $query->get()->getResultArray();

        return $status;
    }

    public function retrieveOfficeBank(array $office_ids)
    {

        $query_builder = $this->read_db->table('office_bank');
        $query_builder->select(array('office_bank_id', 'office_bank_name'));
        $query_builder->whereIn('fk_office_id', $office_ids);
        $query_builder->where(['office_bank_is_active' => 1]);
        $office_banks = $query_builder->get()->getResultArray();

        //Get bank_office_ids
        $office_bank_id = array_column($office_banks, 'office_bank_id');
        $office_bank_name = array_column($office_banks, 'office_bank_name');

        $office_bank_ids_and_names = array_combine($office_bank_id, $office_bank_name);

        return $office_bank_ids_and_names;
    }

    public function transactionValidateDuplicatesColumns(): array
    {
        return [
            'fk_office_bank_id',
            'cheque_book_is_active',
            'fk_status_id',
            'cheque_book_start_serial_number',
            'cheque_book_count_of_leaves',
        ];
    }

    public function transactionValidateByComputationFlag($cheque_book_data)
    {
        $grantsLibrary = new GrantsLibrary();
        $initial_status = $grantsLibrary->initialItemStatus('cheque_book');
        $builder = $this->read_db->table('cheque_book');
        $builder->where(array('fk_status_id' => $initial_status, 'fk_office_bank_id' => $cheque_book_data['fk_office_bank_id']));
        $initial_cheque_book_status_count = $builder->get()->getNumRows();

        if ($initial_cheque_book_status_count > 0) {
            return VALIDATION_ERROR;
        } else {
            return VALIDATION_SUCCESS;
        }
    }

    public function officeBankLastChequeSerialNumber($office_bank_id)
    {
        $last_cheque_book_max_leaf = 0;
        $chequeBookReadBuilder = $this->read_db->table('cheque_book');

        $chequeBookReadBuilder->orderBy('cheque_book_id DESC');
        $chequeBookReadBuilder->where(array('fk_office_bank_id' => $office_bank_id));
        $cheque_book_obj = $chequeBookReadBuilder->get();

        if ($cheque_book_obj->getNumRows() > 0) {
            $last_cheque_book = $cheque_book_obj->getRow();
            $count_of_leaves = $last_cheque_book->cheque_book_count_of_leaves;
            $last_cheque_book_first_leaf = $last_cheque_book->cheque_book_start_serial_number;
            $last_cheque_book_max_leaf = $last_cheque_book_first_leaf + ($count_of_leaves - 1);
        }

        return $last_cheque_book_max_leaf;
    }

    public function office_bank_start_cheque_serial_number($office_bank_id)
    {

        $min_serial_number = 0;
        $builder = $this->read_db->table('cheque_book');
        $builder->selectMin('cheque_book_start_serial_number');
        $builder->where(array('fk_office_bank_id' => $office_bank_id));
        $min_serial_number_obj = $builder->get();

        if ($min_serial_number_obj->getNumRows() > 0) {
            $min_serial_number = $min_serial_number_obj->getRow()->cheque_book_start_serial_number;
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

    /**
     * This method counts the number of reused of cancelled cheque transactions.
     * The name needs to be update since its misleading
     */
    public function getReusedChequeCount($office_bank_id, $cheque_number, $reusing_and_cancel_eft_or_chq = '')
    {

        // log_message('error', json_encode([$office_bank_id,$cheque_number]));
        $cheque_number = is_int($cheque_number) && $cheque_number > 0 ? -$cheque_number : 0;
        $cancelled_cheque_numbers = [];

        $count = 0;

        if ($cheque_number != 0) {
            //$this->read_db->select('voucher_cheque_number');
            //Added by Onduso on 26th May 2023
            //$this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');

            $builder = $this->read_db->table('voucher');
            $builder->select('voucher_cheque_number');
            $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');

            if ($reusing_and_cancel_eft_or_chq == 'cheque') {

                //$this->read_db->where(array('voucher_type_is_cheque_referenced' => 1)); //get Only chq numbers and NOT Eft numbers

                $builder->where(array('voucher_type_is_cheque_referenced' => 1));

            } else if ($reusing_and_cancel_eft_or_chq == 'eft') {
                $builder->where(array('voucher_type_is_cheque_referenced' => 0));
            }

            $builder->where(array('voucher_cheque_number' => $cheque_number, 'fk_office_bank_id' => $office_bank_id));
            $cancelled_cheque_numbers_obj = $builder->get();

            if ($cancelled_cheque_numbers_obj->getNumRows() > 0) {
                $cancelled_cheque_numbers = array_column($cancelled_cheque_numbers_obj->getResultArray(), 'voucher_cheque_number');
                $cancelled_cheque_numbers = array_map([$this, 'makeUnsignedValues'], $cancelled_cheque_numbers);

                $count = count($cancelled_cheque_numbers);
            }
        }

        return $count;
    }

    public function getCancelledCheques($office_bank_id)
    {
        // two cheque numbers are -ve

        $cancelled_cheque_numbers = [];

        $sql = "SELECT voucher_cheque_number, COUNT(*) FROM voucher ";
        $sql .= "JOIN voucher_type ON voucher_type.voucher_type_id=voucher.fk_voucher_type_id ";
        $sql .= "WHERE fk_office_bank_id = " . $office_bank_id . " AND voucher_cheque_number < 0 " . " AND voucher_type_is_cheque_referenced = 1 ";
        $sql .= "GROUP BY voucher_cheque_number HAVING COUNT(*) IN (1,3,5,7,9) "; // This means a cheque leaf can be cancelled 5 times maximum after which it wont be injectable

        $cancelled_cheque_numbers_obj = $this->read_db->query($sql);

        if ($cancelled_cheque_numbers_obj->getNumRows() > 0) {
            $cancelled_cheque_numbers = array_column($cancelled_cheque_numbers_obj->getResultArray(), 'voucher_cheque_number');
            $cancelled_cheque_numbers = array_map([$this, 'makeUnsignedValues'], $cancelled_cheque_numbers);
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
    public function injectedChequeExists(int $office_bank_id, int $cheque_number): int
    {

        $builder = $this->read_db->table('cheque_injection');
        $builder->select(array('cheque_injection_number'));
        $builder->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_injection_number' => $cheque_number));
        $cheque_no = $builder->get()->getRowArray();

        return $cheque_no ? 1 : 0;
    }

     /**
     *count_of_cancelled_chqs_more_than_three(): Checks count of cancelled cheques
     * @author Livingstone Onduso: Dated 12-06-2023
     * @access public
     * @return int - returns count of cancelled chqs
     * @param int $office_bank_id, $cheque_number
     */
    public function isLeafCancelledChqsMoreThanThreshold(int $office_bank_id, int $cheque_number): int
    {
        //Get number of canceled checks
        $sql = "SELECT voucher_cheque_number, COUNT(*) AS count_chqs FROM voucher ";
        $sql .= "JOIN voucher_type ON voucher_type.voucher_type_id=voucher.fk_voucher_type_id ";
        $sql .= "WHERE fk_office_bank_id = " . $office_bank_id . " AND voucher_cheque_number =-$cheque_number " . " AND voucher_type_is_cheque_referenced = 1 ";
        $sql .= "GROUP BY voucher_cheque_number HAVING COUNT(*)  IN (1,3,5,7,9) ";

        $cancelled_cheque_numbers_obj = $this->read_db->query($sql);

        $cancelled_chqs = $cancelled_cheque_numbers_obj->getRowArray();

        $count_cancelled_chqs = 0;

        if ($cancelled_chqs) {
            $count_cancelled_chqs = $cancelled_chqs['count_chqs'];
        }

        return $count_cancelled_chqs >= 3 ? true : false;
    }

    public function chequeToBeInjectedExistsInRange(int $office_bank_id, int $cheque_number_to_inject)
    {
        $message = "You can\'t inject the cheque number " . $cheque_number_to_inject . " due to the following reasons: \n";
        // Check if cheque is used/opening outstanding - Should not Inject a Used Leaf
        $used_cheque_leaves = $this->getUsedChequeLeaves($office_bank_id);
        // Check if cheque is cancelled - Should inject a cancelled leaf
        $cancelled_cheque_numbers = $this->cancelledChequeNumbers($office_bank_id);
        // Check if reused cheque leaf - Should not inject reused cheque leaf
        $all_reused_cheques = $this->getReusedCheques($office_bank_id);
        // Cancelled beyond threshold
        $count_of_chqs_greater_than_threshold = $this->isLeafCancelledChqsMoreThanThreshold($office_bank_id, $cheque_number_to_inject);

        //$this->load->model('cancel_cheque_model');
        //$unused_cheque_leaves = array_column($this->cancel_cheque_model->get_valid_cheques($office_bank_id), 'cheque_number');

        $cancelChequeLibrary = new CancelChequeLibrary();
        $unused_cheque_leaves = array_column($cancelChequeLibrary->getValidCheques($office_bank_id), 'cheque_number');

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
     *negateChequeNumber(): Updates the voucher record by negating the cancelled cheque
     * @author Livingstone Onduso: Dated 17-06-2023
     * @access public
     * @return int - returns 1 when update or 0 when not
     * @param int $office_bank_id, $cheque_number
     */
    public function negateChequeNumber(int $office_bank_id, int $cheque_number): int
    {

        // Get the check exists in voucher
        $builder= $this->read_db->table('voucher');
        $builder->select(['voucher_id']);
        $builder->where(['fk_office_bank_id' => $office_bank_id, 'voucher_cheque_number' => $cheque_number, 'voucher_type_is_cheque_referenced' => 1]);
        $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $record_to_update = $builder->get()->getResultArray();

        if (count($record_to_update) > 0) {

            $data['voucher_cheque_number'] = -$cheque_number;
            $writer_builder = $this->write_db->table('voucher');
            $writer_builder->where(['voucher_id' => $record_to_update[0]['voucher_id']]);
            $writer_builder->update($data);

            return 1;
        }
        return 0;
    }
    public function checkIfPreviousBookIsApproved($office_bank_id)
    {
        $statusLibrary = new StatusLibrary();
        $isPreviousBookApproved = true;

        $cheque_book_max_status = $statusLibrary->getMaxApprovalStatusId('cheque_book');

        $builder = $this->read_db->table('cheque_book');
        $builder->where(array('fk_office_bank_id' => $office_bank_id));
        $builder->whereNotIn('fk_status_id', $cheque_book_max_status);
        $unapproved_books_count = $builder->get()->getNumRows();

        if ($unapproved_books_count > 0) {
            $isPreviousBookApproved = false;
        }

        return $isPreviousBookApproved;
    }

    public function allow_skipping_of_cheque_leaves()
    {

        $is_skipping_of_cheque_leaves_allowed = true;

        if ($this->config->item("allow_skipping_of_cheque_leaves") == false
        ) {
            $is_skipping_of_cheque_leaves_allowed = false;
        }

        return $is_skipping_of_cheque_leaves_allowed;
    }

    public function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void
    {
        // Use the Office hierarchy for the logged user if not system admin
        if (!$this->session->get('system_admin')) {
            $office_ids = array_column($this->session->get('hierarchy_offices'), 'office_id');
            $queryBuilder->whereIn('fk_office_id', $office_ids);
        }
    }

    public function listTableVisibleColumns(): array
    {
        return [
            'cheque_book_track_number',
            'office_bank_name',
            'cheque_book_use_start_date',
            'cheque_book_is_active',
            'cheque_book_start_serial_number',
            'cheque_book_count_of_leaves',
            'status_name'
        ];
    }

    public function showListEditAction(array $record, array $dependancyData = []): bool {
        return false;
    }

    public function postApprovalActionEvent($payload): void
    {
        $cheque_book_id = $payload['post']['item_id'];
        $statusLibrary = new StatusLibrary();
        $max_cheque_book_status_ids = $statusLibrary->getMaxApprovalStatusId('Cheque_book');

        // Update the cheque_book_is_active to 1
        if (in_array($payload['post']['next_status'], $max_cheque_book_status_ids)) {
            $data['cheque_book_is_active'] = 1;
            $builder = $this->write_db->table('cheque_book');
            $builder->where(array('cheque_book_id' => $cheque_book_id));
            $builder->update($data);
        }

    }

    public function deactivate_non_default_office_bank_cheque_books($office_id)
    {

        $cheque_book_ids = [];

        $read_query = $this->read_db->table('cheque_book');
        $read_query->select(array('cheque_book_id'));
        $read_query->where(array('office_bank_is_default' => 0, 'cheque_book_is_active' => 1, 'fk_office_id' => $office_id));
        $read_query->join('office_bank', 'office_bank.office_bank_id=cheque_book.fk_office_bank_id');
        $cheque_book_obj = $read_query->get();

        if ($cheque_book_obj->getNumRows() > 0) {
            $cheque_book_ids = array_column($cheque_book_obj->getResultArray(), 'cheque_book_id');
        }

        $write_query = $this->write_db->table('cheque_book');
        $write_query->whereIn('cheque_book_id', $cheque_book_ids);
        $data['cheque_book_is_active'] = 0;
        $write_query->update($data);
    }

    

    public function getActiveChequeBookReset($office_bank_id)
    {

        $get_active_cheque_book_reset = [];

        $query_builder = $this->read_db->table('cheque_book_reset')
            ->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_reset_is_active' => 1));
        $cheque_book_reset = $query_builder->get();

        if ($cheque_book_reset->getNumRows() > 0) {
            $get_active_cheque_book_reset = $cheque_book_reset->getRow();
        }

        return $get_active_cheque_book_reset;
    }


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


    public function getCurrentStatus(int $chequeBook_id):int
    {
        $builder = $this->write_db->table('cheque_book');
        $builder->where(['cheque_book_id' => $chequeBook_id]);
        return $builder->get()->getRow()->fk_status_id;
    }

    public function editVisibleColumns(): array
    {
        $userLibrary = new UserLibrary();
        $has_voucher_create_permission = $userLibrary->checkRoleHasPermissions('Voucher', 'create');
        $cheque_book_is_active = !$has_voucher_create_permission ? 'cheque_book_is_active' : '';

        return [
            'office_bank_name',
            'cheque_book_use_start_date',
            'cheque_book_start_serial_number',
            'cheque_book_count_of_leaves',
            $cheque_book_is_active,
        ];
    }
}
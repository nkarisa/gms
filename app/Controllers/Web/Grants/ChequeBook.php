<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Libraries\System\GrantsLibrary;
use App\Libraries\Grants\ChequeBookLibrary;

class ChequeBook extends WebController
{


    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }



    function index(){}

    function validate_start_serial_number(){

        $chequeBookLibrary = new ChequeBookLibrary();

        $post = $this->request->getPost();
        $validate_start_serial_number = 0;

        $last_cheque_serial_number = $chequeBookLibrary->officeBankLastChequeSerialNumber($post['office_bank_id']);

        $next_new_cheque_book_start_serial = $last_cheque_serial_number + 1;

        if(($next_new_cheque_book_start_serial != $post['start_serial_number']) && $last_cheque_serial_number > 0){
            $validate_start_serial_number = $next_new_cheque_book_start_serial;
        }

        echo $validate_start_serial_number;
    }

    // function get_active_cheque_book_reset($office_bank_id){

    //   $get_active_cheque_book_reset = [];

    //   $this->read_db->where(array('fk_office_bank_id'=>$office_bank_id,'cheque_book_reset_is_active'=>1));
    //   $cheque_book_reset = $this->read_db->get('cheque_book_reset');

    //   if($cheque_book_reset->num_rows() > 0){
    //     $get_active_cheque_book_reset = $cheque_book_reset->row();
    //   }

    //   return $get_active_cheque_book_reset;
    // }

    function new_cheque_book_start_serial(){

        $chequeBookLibrary = new ChequeBookLibrary();

        $post = $this->request->getPost();

        $office_bank_id = $post['office_bank_id'];

        $last_cheque_serial_number = $chequeBookLibrary->officeBankLastChequeSerialNumber($office_bank_id);

        $next_new_cheque_book_start_serial = 0;

        $active_cheque_book_reset = $chequeBookLibrary->getActiveChequeBookReset($post['office_bank_id']);
        $is_active_cheque_book_reset = 0;//$active_cheque_book_reset->cheque_book_reset_status;
        $reset_start_serial_number = 0;//$active_cheque_book_reset->cheque_book_reset_serial;

        if(!empty($active_cheque_book_reset)){
            $is_active_cheque_book_reset = $active_cheque_book_reset->cheque_book_reset_is_active;
            $reset_start_serial_number = $active_cheque_book_reset->cheque_book_reset_serial;
        }

        if($last_cheque_serial_number > 0 && $is_active_cheque_book_reset == 0){
            $next_new_cheque_book_start_serial = $last_cheque_serial_number + 1;
        }else{
            $next_new_cheque_book_start_serial = $reset_start_serial_number;
        }

        echo $next_new_cheque_book_start_serial;
    }
    function get_active_chequebooks($office_bank_id){

        $chequeBookLibrary = new ChequeBookLibrary();

        $active_cheque_book_exists = 0;

        // Deactivate active cheque book that were not deactivated when creating reset since they were not fully approved.
        // This is a workaround for a legacy error but has been resolved by uptdating the method deactivate_cheque_book in cheque_book_model

        // First check if we have any active cheque book reset before doing the deactivate
        //$this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_reset_is_active' => 1));
        $active_cheque_book_reset_obj = $this->read_db->get('cheque_book_reset');

        $query_builder = $this->read_db->table('cheque_book_reset')
            ->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_reset_is_active' => 1));
        $active_cheque_book_reset_obj = $query_builder->get();


        if($active_cheque_book_reset_obj->getNumRows() > 0){

            $chequeBookLibrary = new ChequeBookLibrary();

            //$this->load->model('cheque_book_model');
            //$this->cheque_book_model->deactivate_cheque_book($office_bank_id);

            $this->$chequeBookLibrary->deactivateChequeBook($office_bank_id);
        }

        $active_cheque_book_exists=$chequeBookLibrary->getActivechequebooks($office_bank_id);

        echo json_encode($active_cheque_book_exists);
    }

    function get_office_chequebooks($office_bank_id){

        $cheque_books=$this->cheque_book_model->get_office_chequebooks($office_bank_id);

        echo json_encode($cheque_books);
    }

    function get_max_id_cheque_book_for_office($office_bank_id){
        echo hash_id($this->cheque_book_model->get_max_id_cheque_book_for_office($office_bank_id),'encode');
    }

    function status_change($change_type = 'approve'){

        if (method_exists($this->cheque_book_model, 'post_approve_action')) {
            $this->cheque_book_model->post_approve_action();
        }

        parent::status_change($change_type);

    }

    function result($id = 0, $parentTable=null){

        $result = [];

        if($this->action == 'list'){
            $columns = $this->columns();
            array_shift($columns);
            unset($columns[array_search('fk_account_system_id', $columns)]);
            $result['columns'] = $columns;
            $result['has_details_table'] = false;
            $result['has_details_listing'] = false;
            $result['is_multi_row'] = false;
            $result['show_add_button'] = true;
        }
        elseif($this->action=='single_form_add'){
            // $user_offices=$this->user_model->user_hierarchy_offices($this->session->user_id, true);

            // $user_context_office=[];

            // foreach( $user_offices as  $user_office){
            //   $user_context_office=$user_office;
            // }
            // $office_ids=array_column($user_context_office, 'office_id');
            // $this->load->model('office_bank_model');

            $office_ids = array_column($this->session->hierarchy_offices, 'office_id');
            //$result['offices']=   $office_ids;

            $result['office_banks']=$this->cheque_book_model->retrieve_office_bank($office_ids);
        }

        else{
            $result = parent::result($id);
        }

        return $result;
    }

    /**
     * get_cheque_book_size(): returns a json string carring cheque_book_size and is_first_cheque_book
     * @author Nicodemus Karisa; Modified by Livingstone Onduso
     * @access public
     * @return void
     * @param int $office_bank_id
     */

    public function get_cheque_book_size(int $office_bank_id):void
    {
        $this->load->model('office_bank_model');

        $is_first_cheque_book = $this->cheque_book_model->is_first_cheque_book($office_bank_id);

        $cheque_book_size = $this->office_bank_model->get_cheque_book_size($office_bank_id);

        echo json_encode(['cheque_book_size' => $cheque_book_size, 'is_first_cheque_book' => $is_first_cheque_book]);
    }

    // public function is_first_cheque_book($office_bank_id)
    // {

    //   $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
    //   $office_bank_cheque_books_obj = $this->read_db->get('cheque_book');

    //   $is_first_cheque_book = true;

    //   if ($office_bank_cheque_books_obj->num_rows() > 1) {
    //     $is_first_cheque_book = false;
    //   }

    //   return $is_first_cheque_book;
    // }

    function columns(){
        $columns = [
            'cheque_book_id',
            'cheque_book_track_number',
            'office_bank_name',
            'cheque_book_use_start_date',
            'cheque_book_is_active',
            'cheque_book_start_serial_number',
            'cheque_book_count_of_leaves',
            'status_name',
            'fk_account_system_id',
        ];

        return $columns;
    }

    // function checkIfPreviousBookIsApproved($office_bank_id){
    //   $isPreviousBookApproved = true;

    //   $cheque_book_max_status = $this->general_model->get_max_approval_status_id('cheque_book');

    //   // log_message('error', json_encode($cheque_book_max_status));

    //   $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
    //   $this->read_db->where_not_in('fk_status_id', $cheque_book_max_status);
    //   $unapproved_books_count = $this->read_db->get('cheque_book')->num_rows();

    //   if($unapproved_books_count > 0) {
    //     $isPreviousBookApproved = false;
    //   }

    //   return $isPreviousBookApproved;
    // }



    /**
     * post_cheque_book(): returns a void
     * @author Livingstone;
     * @access public
     * @return void
     */

    public function post_cheque_book(){

        $post=$this->request->getPost();

        // Check if the previous book is fully approved, if not deny creating a new book
        $chequeBookLibrary = new ChequeBookLibrary();
        $isPreviousBookApproved = $chequeBookLibrary->checkIfPreviousBookIsApproved($post['fk_office_bank_id']);

        $last_id = 0;

        if($isPreviousBookApproved){
            $data['header']['cheque_book_track_number']=$this->grants_model->generate_item_track_number_and_name('cheque_book')['cheque_book_track_number'];
            $data['header']['cheque_book_name']=$this->grants_model->generate_item_track_number_and_name('cheque_book')['cheque_book_name'];
            $data['header']['fk_office_bank_id']=$post['fk_office_bank_id'];
            $data['header']['cheque_book_is_active']=0;
            $data['header']['cheque_book_start_serial_number']=$post['cheque_book_start_serial_number'];
            $data['header']['cheque_book_count_of_leaves']=$post['cheque_book_count_of_leaves'];
            $data['header']['cheque_book_use_start_date']=$post['cheque_book_use_start_date'];
            // $data['cheque_book_start_serial_number']=$post['cheque_book_start_serial_number'];
            $data['header']['cheque_book_created_date']=date('Y-m-d');
            $data['header']['cheque_book_created_by']=$this->session->user_id;
            $data['header']['cheque_book_last_modified_by'] = $this->session->user_id;
            $data['header']['cheque_book_created_date'] = date('Y-m-d');
            $data['header']['fk_approval_id'] = $this->grants_model->insert_approval_record('cheque_book');
            $data['header']['fk_status_id'] = $this->grants_model->initial_item_status('cheque_book');

            $last_id = $this->cheque_book_model->post_cheque_book($data);
        }


        echo $last_id;
    }

    /**
     * redirect_to_voucher_after_approval(): returns void
     * @author Livingstone;
     * @access public
     * @return void
     */
    public function redirect_to_voucher_after_approval($cheque_book_id)
    {
        // $cheque_book_id = hash_id($this->id, 'decode');

        $redirect = false;

        $current_status_id = $this->cheque_book_model->get_current_status($cheque_book_id);

        $has_voucher_create_permission = $this->user_model->check_role_has_permissions('Voucher', 'create');
        $max_cheque_book_status_ids = $this->general_model->get_max_approval_status_id('Cheque_book');
        ///$next_status_id = $this->general_model->next_status($current_status_id);

        $is_next_status_full_approval = in_array($current_status_id,$max_cheque_book_status_ids) ? true : false;

        if($has_voucher_create_permission && $is_next_status_full_approval){
            // $redirect_to_voucher_form = base_url() . 'voucher/multi_form_add';
            // header("Location:" . $redirect_to_voucher_form);
            $redirect = true;
        }
        echo $redirect;
        //echo json_encode(['current_id'=>$current_status_id,'max_id'=>$max_cheque_book_status_ids]);
    }




    function get_cheque_books(){

        $columns = $this->columns();
        array_push($columns, 'status_id');
        array_push($columns, 'cheque_book_is_used');
        $search_columns = $columns;

        // Limiting records
        $start = intval($this->request->getPost('start'));
        $length = intval($this->request->getPost('length'));

        $query = $this->read_db->table('cheque_book')
            ->limit($length, $start);

        // Ordering records

        $order = $this->request->getPost('order');
        $col = '';
        $dir = 'desc';

        if(!empty($order)){
            $col = $order[0]['column'];
            $dir = $order[0]['dir'];
        }

        if( $col == ''){
            $query->orderBy('cheque_book_id', 'DESC');
        }else{
            $query->orderBy($columns[$col],$dir);
        }

        // Searching

        $search = $this->request->getPost('search');
        $value = $search['value'];

        array_shift($search_columns);

        if(!empty($value)){
            $query->groupStart();
            $column_key = 0;
            foreach($search_columns as $column){
                if($column_key == 0) {
                    $query->like($column,$value,'both');
                }else{
                    $query->orLike($column,$value,'both');
                }
                $column_key++;
            }
            $query->groupEnd();
        }

        if(!$this->session->system_admin){
            $query->whereIn('fk_office_id', array_column($this->session->hierarchy_offices,'office_id'));
        }

        $query->select($columns);
        $query->join('status','status.status_id=cheque_book.fk_status_id');
        $query->join('office_bank','office_bank.office_bank_id=cheque_book.fk_office_bank_id');
        $query->join('office','office.office_id=office_bank.fk_office_id');

        $result_obj = $query->get();

        $results = [];

        if($result_obj->getNumRows() > 0){
            $results = $result_obj->getResultArray();
        }

        return $results;
    }

    function count_cheque_books(){
        $session = session();

        $columns = $this->columns();
        $search_columns = $columns;

        // Searching

        $search = $this->request->getPost('search');
        $value = $search['value'];

        array_shift($search_columns);

        $query = $this->read_db->table('cheque_book');
        if(!empty($value)){
            $query->groupStart();
            $column_key = 0;
            foreach($search_columns as $column){
                if($column_key == 0) {
                    $query->like($column,$value,'both');
                }else{
                    $query->orLike($column,$value,'both');
                }
                $column_key++;
            }
            $query->groupEnd();
        }

        if(!$session->system_admin){
            $query->whereIn('fk_office_id', array_column($session->hierarchy_offices,'office_id'));
        }

        $query->join('status','status.status_id=cheque_book.fk_status_id');
        $query->join('office_bank','office_bank.office_bank_id=cheque_book.fk_office_bank_id');
        $query->join('office','office.office_id=office_bank.fk_office_id');


        $query->get();
        $count_all_results = $query->countAllResults();

        return $count_all_results;
    }

    function showList(): ResponseInterface
    {
        $grantsLibrary = new GrantsLibrary();
        $draw =intval($this->request->getPost('draw'));
        $cheque_books = $this->get_cheque_books();
        $count_cheque_books = $this->count_cheque_books();

        $result = [];

        $cnt = 0;
        foreach($cheque_books as $cheque_book){
            $status_data = $grantsLibrary->actionButtonData($this->controller, $cheque_book['fk_account_system_id']); // This has performance issues due to reading db on loops
            extract($status_data);
            $cheque_book_id = array_shift($cheque_book);
            $cheque_book_is_used = array_pop($cheque_book);
            $cheque_book_status = array_pop($cheque_book);

            $cheque_book_track_number = $cheque_book['cheque_book_track_number'];
            $cheque_book['cheque_book_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($cheque_book_id).'">'.$cheque_book_track_number.'</a>';
            $cheque_book['cheque_book_is_active'] = $cheque_book['cheque_book_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
            $row = array_values($cheque_book);

            $deactivate_action_buttons = $cheque_book_is_used ? true : false;
            $action = approval_action_button($this->controller, $item_status, $cheque_book_id, $cheque_book_status, $item_initial_item_status_id, $item_max_approval_status_ids, $deactivate_action_buttons);

            array_unshift($row, $action);

            $result[$cnt] = $row;

            $cnt++;
        }

        $response = [
            'draw'=>$draw,
            'recordsTotal'=>$count_cheque_books,
            'recordsFiltered'=>$count_cheque_books,
            'data'=>$result
        ];

//        echo json_encode($response);
        return $this->response->setJSON($response);
    }

    static function get_menu_list(){}
}

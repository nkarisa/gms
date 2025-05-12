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

    function validateStartSerialNumber(){

        $chequeBookLibrary = new ChequeBookLibrary();

        $post = $this->request->getPost();
        $validate_start_serial_number = 0;

        $last_cheque_serial_number = $chequeBookLibrary->officeBankLastChequeSerialNumber($post['office_bank_id']);

        $next_new_cheque_book_start_serial = $last_cheque_serial_number + 1;

        if(($next_new_cheque_book_start_serial != $post['start_serial_number']) && $last_cheque_serial_number > 0){
            $validate_start_serial_number = $next_new_cheque_book_start_serial;
        }

        return $this->response->setJSON(compact('validate_start_serial_number'));
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

    function newChequeBookStartSerial(){

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
        // log_message('error', compact('next_new_cheque_book_start_serial','active_cheque_book_reset','last_cheque_serial_number'));
        return $this->response->setJSON(compact('next_new_cheque_book_start_serial'));
    }
    function getActiveChequeBooks($office_bank_id){

        $active_cheque_book_exists = 0;
        $chequeBookResetBuilder = $this->read_db->table('cheque_book_reset');
        $chequeBookLibrary = new chequeBookLibrary();
    
        // Deactivate active cheque book that were not deactivated when creating reset since they were not fully approved. 
        // This is a workaround for a legacy error but has been resolved by uptdating the method deactivate_cheque_book in cheque_book_model
    
        // First check if we have any active cheque book reset before doing the deactivate
        $chequeBookResetBuilder->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_reset_is_active' => 1));
        $ctive_cheque_book_reset_obj = $chequeBookResetBuilder->get();
    
        if($ctive_cheque_book_reset_obj->getNumRows() > 0){
          $chequeBookLibrary->deactivateChequeBook($office_bank_id);
        }
    
        $active_cheque_book_exists = $chequeBookLibrary->getActiveChequeBooks($office_bank_id);
    
        return $this->response->setJSON($active_cheque_book_exists);
      }

    function getOfficeChequeBooks($office_bank_id){
        $chequeBookLibrary = new ChequeBookLibrary();
        $count_cheque_books = $chequeBookLibrary->getOfficeChequeBooks($office_bank_id);
        return $this->response->setJSON(compact('count_cheque_books'));
    }

    function getMaxIdChequeBookForOffice($office_bank_id){
        $chequeBookLibrary = new ChequeBookLibrary();
        $maxChequeBookId =  hash_id($chequeBookLibrary->getMaxIdChequeBookForOffice($office_bank_id),'encode');
        return $this->response->setJSON(compact('maxChequeBookId'));
    }

    function status_change($change_type = 'approve'){

        if (method_exists($this->cheque_book_model, 'post_approve_action')) {
            $this->cheque_book_model->post_approve_action();
        }

        parent::status_change($change_type);

    }

    function result($id = 0, $parentTable=null){

        $result = parent::result($id, $parentTable);
        $chequeBookLibrary = new ChequeBookLibrary();

        if($this->action == 'singleFormAdd'){
            $office_ids = array_column($this->session->hierarchy_offices, 'office_id');
            $result['office_banks']=$chequeBookLibrary->retrieveOfficeBank($office_ids);
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

    public function getChequeBookSize(int $office_bank_id): ResponseInterface
    {
        $chequeBookLibrary = new ChequeBookLibrary();
        $officeBankLibrary = new \App\Libraries\Grants\OfficeBankLibrary();

        $is_first_cheque_book = $chequeBookLibrary->isFirstChequeBook($office_bank_id);
        $cheque_book_size = $officeBankLibrary->getChequeBookSize($office_bank_id);
        return $this->response->setJSON(['cheque_book_size' => $cheque_book_size, 'is_first_cheque_book' => $is_first_cheque_book]);
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

    public function postChequeBook(): ResponseInterface{
        $post = $this->request->getPost();
        // Check if the previous book is fully approved, if not deny creating a new book
        $chequeBookLibrary = new ChequeBookLibrary();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
        $isPreviousBookApproved = $chequeBookLibrary->checkIfPreviousBookIsApproved($post['fk_office_bank_id']);
        $insertId = 0;

        $itemTrackNumberAndName = $this->libs->generateItemTrackNumberAndName('cheque_book');

        if($isPreviousBookApproved){
            $data['header']['cheque_book_track_number'] = $itemTrackNumberAndName['cheque_book_track_number'];
            $data['header']['cheque_book_name'] = $itemTrackNumberAndName['cheque_book_name'];
            $data['header']['fk_office_bank_id'] = $post['fk_office_bank_id'];
            $data['header']['cheque_book_is_active'] = 0;
            $data['header']['cheque_book_start_serial_number'] = $post['cheque_book_start_serial_number'];
            $data['header']['cheque_book_count_of_leaves'] = $post['cheque_book_count_of_leaves'];
            $data['header']['cheque_book_use_start_date'] = $post['cheque_book_use_start_date'];
            $data['header']['cheque_book_created_date'] = date('Y-m-d');
            $data['header']['cheque_book_created_by'] = $this->session->user_id;
            $data['header']['cheque_book_last_modified_by'] = $this->session->user_id;
            $data['header']['cheque_book_created_date'] = date('Y-m-d');
            $data['header']['fk_approval_id'] = $approvalLibrary->insertApprovalRecord('cheque_book');
            $data['header']['fk_status_id'] = $statusLibrary->initialItemStatus('cheque_book');

            $insertId = $chequeBookLibrary->postChequeBook($data);
        }


        return $this->response->setJSON(compact('insertId'));
    }

    /**
     * redirect_to_voucher_after_approval(): returns void
     * @author Livingstone;
     * @access public
     * @return void
     */
    public function redirectToVoucherAfterApproval($cheque_book_id)
    {
        $redirect = false;
        $chequeBookLibrary = new ChequeBookLibrary();
        $userLibrary = new \App\Libraries\Core\UserLibrary();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        $current_status_id = $chequeBookLibrary->getCurrentStatus($cheque_book_id);

        $has_voucher_create_permission = $userLibrary->checkRoleHasPermissions('Voucher', 'create');
        $max_cheque_book_status_ids = $statusLibrary->getMaxApprovalStatusId('Cheque_book');

        $is_next_status_full_approval = in_array($current_status_id,$max_cheque_book_status_ids) ? true : false;

        if($has_voucher_create_permission && $is_next_status_full_approval){
            $redirect = true;
        }

        return $this->response->setJSON(compact('redirect'));
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

//     function showList(): ResponseInterface
//     {
//         $grantsLibrary = new GrantsLibrary();
//         $draw =intval($this->request->getPost('draw'));
//         $cheque_books = $this->get_cheque_books();
//         $count_cheque_books = $this->count_cheque_books();

//         $result = [];

//         $cnt = 0;
//         foreach($cheque_books as $cheque_book){
//             $status_data = $grantsLibrary->actionButtonData($this->controller, $cheque_book['fk_account_system_id']); // This has performance issues due to reading db on loops
//             extract($status_data);
//             $cheque_book_id = array_shift($cheque_book);
//             $cheque_book_is_used = array_pop($cheque_book);
//             $cheque_book_status = array_pop($cheque_book);

//             $cheque_book_track_number = $cheque_book['cheque_book_track_number'];
//             $cheque_book['cheque_book_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($cheque_book_id).'">'.$cheque_book_track_number.'</a>';
//             $cheque_book['cheque_book_is_active'] = $cheque_book['cheque_book_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
//             $row = array_values($cheque_book);

//             $deactivate_action_buttons = $cheque_book_is_used ? true : false;
//             $action = approval_action_button($this->controller, $item_status, $cheque_book_id, $cheque_book_status, $item_initial_item_status_id, $item_max_approval_status_ids, $deactivate_action_buttons);

//             array_unshift($row, $action);

//             $result[$cnt] = $row;

//             $cnt++;
//         }

//         $response = [
//             'draw'=>$draw,
//             'recordsTotal'=>$count_cheque_books,
//             'recordsFiltered'=>$count_cheque_books,
//             'data'=>$result
//         ];

// //        echo json_encode($response);
//         return $this->response->setJSON($response);
//     }

    static function get_menu_list(){}
}

<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ChequeBookReset extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function index(){}

    function result($id = 0, $parentTable = null){

        $result = [];

        if($this->action == 'list'){
            $columns = $this->columns();
            array_shift($columns);
            $result['columns'] = $columns;
            $result['has_details_table'] = false;
            $result['has_details_listing'] = false;
            $result['is_multi_row'] = false;
            $result['show_add_button'] = true;
        }else{
            $result = parent::result($id);
        }

        return $result;
    }

    function columns(){
        $columns = [
            'cheque_book_reset_id',
            'cheque_book_reset_track_number',
            'office_name',
            'office_bank_name',
            'cheque_book_reset_serial',
            'cheque_book_reset_is_active',
            'item_reason_name',
            'cheque_book_reset_created_date'
        ];

        return $columns;
    }


    function get_cheque_book_resets(){

        $columns = $this->columns();
        $search_columns = $columns;

        // Limiting records
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));

        $this->read_db->limit($length, $start);

        // Ordering records

        $order = $this->input->post('order');
        $col = '';
        $dir = 'desc';

        if(!empty($order)){
            $col = $order[0]['column'];
            $dir = $order[0]['dir'];
        }

        if( $col == ''){
            $this->read_db->order_by('cheque_book_reset_id DESC');
        }else{
            $this->read_db->order_by($columns[$col],$dir);
        }

        // Searching

        $search = $this->input->post('search');
        $value = $search['value'];

        array_shift($search_columns);

        if(!empty($value)){
            $this->read_db->group_start();
            $column_key = 0;
            foreach($search_columns as $column){
                if($column_key == 0) {
                    $this->read_db->like($column,$value,'both');
                }else{
                    $this->read_db->or_like($column,$value,'both');
                }
                $column_key++;
            }
            $this->read_db->group_end();
        }

        if(!$this->session->system_admin){
            //$this->read_db->where_in(array('office.office_id'=>$this->session->hierarchy_offices));

            $this->read_db->where_in('fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
        }

        $this->read_db->select($columns);
        $this->read_db->join('office_bank','office_bank.office_bank_id=cheque_book_reset.fk_office_bank_id');
        $this->read_db->join('office','office.office_id=office_bank.fk_office_id');
        $this->read_db->join('item_reason','cheque_book_reset.fk_item_reason_id=item_reason.item_reason_id');

        $result_obj = $this->read_db->get('cheque_book_reset');

        $results = [];

        if($result_obj->num_rows() > 0){
            $results = $result_obj->result_array();
        }

        return $results;
    }

    function count_cheque_book_resets(){

        $columns = $this->columns();
        $search_columns = $columns;

        // Searching

        $search = $this->input->post('search');
        $value = $search['value'];

        array_shift($search_columns);

        if(!empty($value)){
            $this->read_db->group_start();
            $column_key = 0;
            foreach($search_columns as $column){
                if($column_key == 0) {
                    $this->read_db->like($column,$value,'both');
                }else{
                    $this->read_db->or_like($column,$value,'both');
                }
                $column_key++;
            }
            $this->read_db->group_end();
        }

        if(!$this->session->system_admin){
            //$this->read_db->where_in(array('office.office_id'=>$this->session->hierarchy_offices));
            $this->read_db->where_in('fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
        }

        $this->read_db->select($columns);
        $this->read_db->join('office_bank','office_bank.office_bank_id=cheque_book_reset.fk_office_bank_id');
        $this->read_db->join('office','office.office_id=office_bank.fk_office_id');
        $this->read_db->join('item_reason','cheque_book_reset.fk_item_reason_id=item_reason.item_reason_id');

        $this->read_db->from('cheque_book_reset');
        $count_all_results = $this->read_db->count_all_results();

        return $count_all_results;
    }

    function showList(): ResponseInterface
    {

        $draw =intval($this->input->post('draw'));
        $cheque_book_resets = $this->get_cheque_book_resets();
        $count_cheque_book_resets = $this->count_cheque_book_resets();

        $result = [];

        $cnt = 0;
        foreach($cheque_book_resets as $cheque_book_reset){
            $cheque_book_reset_id = array_shift($cheque_book_reset);
            $cheque_book_reset_track_number = $cheque_book_reset['cheque_book_reset_track_number'];
            $cheque_book_reset['cheque_book_reset_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($cheque_book_reset_id).'">'.$cheque_book_reset_track_number.'</a>';
            $cheque_book_reset['cheque_book_reset_is_active'] = $cheque_book_reset['cheque_book_reset_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
            $row = array_values($cheque_book_reset);

            $result[$cnt] = $row;

            $cnt++;
        }

        $response = [
            'draw'=>$draw,
            'recordsTotal'=>$count_cheque_book_resets,
            'recordsFiltered'=>$count_cheque_book_resets,
            'data'=>$result
        ];

        echo json_encode($response);
        return
    }

    function validate_cheque_book_reset_timeframe($office_bank_id){
        // echo $office_bank_id;
        $this->read_db->select_max('cheque_book_reset_created_date');
        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
        $max_created_date_obj = $this->read_db->get('cheque_book_reset');

        $is_valid = true;

        if($max_created_date_obj){
            $today_date = date('Y-m-d');
            $last_date = $max_created_date_obj->row()->cheque_book_reset_created_date;

            $last_date = strtotime($last_date);
            $today_date = strtotime($today_date);

            $sec_diff = $today_date -  $last_date;

            $days_diff = $sec_diff/86400;

            $has_cheque_reset_constraint_permission = $this->user_model->check_role_has_permissions('cheque_book_reset_constraint', 'update');

            if($days_diff < $this->config->item('cheque_book_reset_limit_days') && !$this->session->system_admin && !$has_cheque_reset_constraint_permission){
                $is_valid = false;
            }
        }

        echo $is_valid;
    }

    static function get_menu_list(){}
}

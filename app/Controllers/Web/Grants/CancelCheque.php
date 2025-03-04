<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CancelCheque extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    public static function get_menu_list()
    {}

    /**
     *get_active_chequebook():This method gets to pass active chequebook to Ajax on the client.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return void
     */
    public function get_active_chequebook(int $office_bank_id): void
    {

        $this->load->model('office_model');
        $this->load->model('voucher_type_model');

        $active_chequebook = $this->cancel_cheque_model->get_active_chequebook($office_bank_id);
        $office = $this->office_model->get_office_by_office_bank_id($office_bank_id);
        $this->voucher_type_model->create_missing_void_hidden_voucher_types($office['account_system_id']);

        echo json_encode($active_chequebook);

    }

    /**
     *columns(): Returns and array of columns.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return array
     */
    public function columns():array
    {
        $columns = [
            'cancel_cheque_id',
            'cancel_cheque_track_number',
            'cancel_cheque_number',
            'item_reason_name',
            'other_reason',
            'voucher_number',
            'office_bank_name',
            'fk_account_system_id',
            // 'status_name',
            //'voucher_number',

        ];

        return $columns;
    }

    /**
     *result():Returns and array of result.
     * @author Livingstone Onduso: Dated 06-05-2024.
     * @access public
     * @return array
     * @param $id
     */
    public function result($id = 0, $parentTable = null)
    {

        $result = [];

        if ($this->action == 'list') {
            $columns = $this->columns();
            unset($columns[array_search('fk_account_system_id', $columns)]);
            array_shift($columns);
            $result['columns'] = $columns;
            $result['has_details_table'] = false;
            $result['has_details_listing'] = false;
            $result['is_multi_row'] = false;
            $result['show_add_button'] = true;

        } elseif ($this->action == 'single_form_add') {

            $result['office_banks'] = $this->cancel_cheque_model->get_bank_accounts();
            $result['cheque_cancel_reason'] = $this->cancel_cheque_model->get_cancel_cheque_reason();

        } else {
            $result = parent::result($id);
            $result['master']['action_labels']['show_label_as_button'] = false;
        }

        return $result;
    }

    /**
     *get_valid_cheques(): Returns the valid cheques.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return void
     * @param int $office_bank_id
     */
    public function get_valid_cheques(int $office_bank_id): void
    {
        $chq_numbers = $this->cancel_cheque_model->get_valid_cheques($office_bank_id);

        echo json_encode($chq_numbers);
    }
    /**
     *count_cancelled_cheques(): Returns count of cheques.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     */
    private function count_cancelled_cheques()
    {

        $columns = $this->columns();
        $search_columns = $columns;

        // Searching

        $search = $this->input->post('search');
        $value = $search['value'];

        array_shift($search_columns);

        if (!empty($value)) {
            $this->read_db->group_start();
            $column_key = 0;
            foreach ($search_columns as $column) {
                if ($column_key == 0) {
                    $this->read_db->like($column, $value, 'both');
                } else {
                    $this->read_db->or_like($column, $value, 'both');
                }
                $column_key++;
            }
            $this->read_db->group_end();
        }

        if (!$this->session->system_admin) {
            $this->read_db->where_in('fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        }

        $this->read_db->join('status', 'status.status_id=cancel_cheque.fk_status_id');
        $this->read_db->join('cheque_book', 'cheque_book.cheque_book_id=cancel_cheque.fk_cheque_book_id');
        $this->read_db->join('office_bank', 'office_bank.office_bank_id=cheque_book.fk_office_bank_id');
        $this->read_db->join('office', 'office.office_id=office_bank.fk_office_id');

        $this->read_db->from('cancel_cheque');
        $count_all_results = $this->read_db->count_all_results();

        return $count_all_results;

    }

    /**
     *get_cancelled_cheques(): Returns cancelled chqs .
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access private
     * @return array
     */
    private function get_cancelled_cheques():array
    {

        $columns = $this->columns();
        array_push($columns, 'cheque_book_name');
        array_push($columns, 'fk_voucher_id');
        $search_columns = $columns;

        // Limiting records
        $start = intval($this->input->post('start'));
        $length = intval($this->input->post('length'));

        $this->read_db->limit($length, $start);

        // Ordering records

        $order = $this->input->post('order');
        $col = '';
        $dir = 'desc';

        if (!empty($order)) {
            $col = $order[0]['column'];
            $dir = $order[0]['dir'];
        }

        if ($col == '') {
            $this->read_db->order_by('cancel_cheque_id DESC');
        } else {
            $this->read_db->order_by($columns[$col], $dir);
        }

        // Searching

        $search = $this->input->post('search');
        $value = $search['value'];

        //array_shift($search_columns);

        if (!empty($value)) {
            $this->read_db->group_start();
            $column_key = 0;
            foreach ($search_columns as $column) {
                if ($column_key == 0) {
                    $this->read_db->like($column, $value, 'both');
                } else {
                    $this->read_db->or_like($column, $value, 'both');
                }
                $column_key++;
            }
            $this->read_db->group_end();
        }

        if (!$this->session->system_admin) {
            $this->read_db->where_in('office_bank.fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        }

        $this->read_db->select($columns);
        $this->read_db->join('item_reason', 'item_reason.item_reason_id=cancel_cheque.fk_item_reason_id');
        $this->read_db->join('voucher','voucher.voucher_id=cancel_cheque.fk_voucher_id');
        $this->read_db->join('status', 'status.status_id=cancel_cheque.fk_status_id');
        $this->read_db->join('cheque_book', 'cheque_book.cheque_book_id=cancel_cheque.fk_cheque_book_id');
        $this->read_db->join('office_bank', 'office_bank.office_bank_id=cheque_book.fk_office_bank_id');

        $this->read_db->join('office', 'office.office_id=office_bank.fk_office_id');

        $result_obj = $this->read_db->get('cancel_cheque');

        $results = [];

        if ($result_obj->num_rows() > 0) {
            $results = $result_obj->result_array();
        }

        return $results;
    }

    /**
     *get_cheque_book_range(): Returns the range of cheques.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return void
     * @param int $cancelled_chqs_id
     */
    private function get_cheque_book_range(int $cancelled_chqs_id):array {

        return $this->cancel_cheque_model->get_cheque_book_range($cancelled_chqs_id);

    }


    /**
     *show_list(): Returns list of cancelled chq using server side loading.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return void
     */
    public function show_list()
    {

        $draw = intval($this->input->post('draw'));
        $cancelled_cheques = $this->get_cancelled_cheques();
        $count_cancelled_cheques = $this->count_cancelled_cheques();

        $result = [];

        $cnt = 0;

        //log_message('error',$cancelled_cheques);

        foreach ($cancelled_cheques as $cancelled_cheque) {
            $status_data = $this->general_model->action_button_data($this->controller, $cancelled_cheque['fk_account_system_id']);
            extract($status_data);

            $cancel_cheque_id = array_shift($cancelled_cheque);
            // $cancelled_cheque_status = array_pop($cancelled_cheque);

            $cheque_book_track_number = $cancelled_cheque['cancel_cheque_track_number'];
            //$voucher_number = $cancelled_cheque['voucher_number'];
            $cancelled_cheque['cancel_cheque_track_number'] = '<a href="' . base_url() . $this->controller . '/view/' . hash_id($cancel_cheque_id) . '">' . $cheque_book_track_number . '</a>';

            // $cancelled_cheque['voucher_number'] = '<a href="' . base_url() . $this->controller . '/view/' . $cancelled_cheque['fk_voucher_id'] . '">' . $voucher_number . '</a>';

            $row = array_values($cancelled_cheque);

            //$action = approval_action_button($this->controller, $item_status, $cancel_cheque_id, $cancelled_cheque_status, $item_initial_item_status_id, $item_max_approval_status_ids);

            //Get start serial number
            $cheque_book_start_serial_number=$this->get_cheque_book_range($cancel_cheque_id)[0]['cheque_book_start_serial_number'];

            $number_of_leaves=$this->get_cheque_book_range($cancel_cheque_id)[0]['cheque_book_count_of_leaves'];

            //Total leaves of the cheque book
            $last_serial_number=($cheque_book_start_serial_number+$number_of_leaves)-1;
            array_splice($row, 1, 0, $cheque_book_start_serial_number.' - '.$last_serial_number);


            $result[$cnt] = $row;

            $cnt++;
        }

        $response = [
            'draw' => $draw,
            'recordsTotal' => $count_cancelled_cheques,
            'recordsFiltered' => $count_cancelled_cheques,
            'data' => $result,
        ];

        echo json_encode($response);
    }

    /**
     *save_cancelled_cheques(): Store cancelled cheques in database.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return void
     */
    public function save_cancelled_cheques():void
    {
        $insert_status = 1;

        //Collect Form values using post and they pick specific ones
        $post = $this->input->post();

        $this->write_db->trans_start();

        $cheque_numbers = $post['cancel_cheque_number'];

        $cheque_book_id= $post['fk_cheque_book_id'];

        $office_bank_id= $post['office_bank_id'];

        //Get office_id
        $this->read_db->select(['fk_office_id']);
        $this->read_db->where(['office_bank_id'=>$office_bank_id]);
        $office_id=$this->read_db->get('office_bank')->row()->fk_office_id;

        //log_message('error',json_encode((float)$office_id));
        //Get the voucher_date
        $this->read_db->select(['voucher_date']);
        $this->read_db->where(['fk_office_id'=>(float)$office_id]);
        $voucher_date=$this->read_db->get('voucher')->row_array();

        //log_message('error',json_encode($voucher_date));

        $reason_id=$post['fk_item_reason_id'];

        $other_reason=$post['other_reason'];

        //Loop to store the several cheque numbers that you have selected to cancel
        $cnt = 1;
        foreach ($cheque_numbers as $cheque_number) {
            //log_message('error',$cheque_number);
            $data['fk_cheque_book_id'] = $cheque_book_id;

            $data['cancel_cheque_number'] = $cheque_number;

            $data['fk_item_reason_id']=$reason_id;

            $data['other_reason']=$other_reason;

            $data['cancel_cheque_name'] = $this->grants_model->generate_item_track_number_and_name('cancel_cheque')['cancel_cheque_name'];

            $track = $this->grants_model->generate_item_track_number_and_name('cancel_cheque');

            $data['cancel_cheque_track_number'] = $track['cancel_cheque_track_number'];

            $data['cancel_cheque_created_date'] = date('Y-m-d');

            $data['cancel_cheque_created_by'] = $this->session->user_id;

            $data['fk_status_id'] = $this->grants_model->initial_item_status('cancel_cheque');

            //Create voucher record with zero amount
            $last_voucher_id=$this->voucher_model->insert_zero_amount_voucher($cheque_number, $cheque_book_id, $office_bank_id, $cnt);

            //Insert Data :cheque number records
            $data['fk_voucher_id']=$last_voucher_id;

            $this->write_db->insert('cancel_cheque', $data);

            $cnt++;
        }
        //Insert Data
        // $this->write_db->insert_batch('cancel_cheque', $batch_of_data);

        $this->write_db->trans_complete();

        if ($this->write_db->trans_status() == false) {

            $insert_status = 0;
        }

        echo $insert_status;




    }
}

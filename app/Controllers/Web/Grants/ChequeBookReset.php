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

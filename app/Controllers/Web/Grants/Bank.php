<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Bank extends WebController
{
    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function result($id = null, $parentTable = null)
    {

        $result = [];

        if ($this->action == 'list') {
            $columns = $this->columns();
            array_shift($columns);
            $result['columns'] = $columns;
            $result['has_details_table'] = false;
            $result['has_details_listing'] = false;
            $result['is_multi_row'] = false;
            $result['show_add_button'] = true;
        } else {
            $result = parent::result($id, $parentTable);
        }

        return $result;
    }

    function columns()
    {
        $columns = [
            'bank_id',
            'bank_track_number',
            'bank_name',
            'bank_swift_code',
            'bank_is_active',
            'account_system_name'
        ];

        return $columns;
    }


    function get_banks()
    {

        $bankReadBuilder = $this->read_db->table('bank');
        $post = $this->request->getPost();
        $columns = $this->columns();
        $search_columns = $columns;

        // Limiting records
        $start = intval($post['start']);
        $length = intval($post['length']);

        $bankReadBuilder->limit($length, $start);

        // Ordering records

        $order = $post['order']??'';
        $col = '';
        $dir = 'desc';

        if (!empty($order)) {
            $col = $order[0]['column'];
            $dir = $order[0]['dir'];
        }

        if ($col == '') {
            $bankReadBuilder->orderBy('bank_id DESC');
        } else {
            $bankReadBuilder->orderBy($columns[$col], $dir);
        }

        // Searching

        $search = $post['search'];
        $value = $search['value'];

        array_shift($search_columns);

        if (!empty($value)) {
            $bankReadBuilder->groupStart();
            $column_key = 0;
            foreach ($search_columns as $column) {
                if ($column_key == 0) {
                    $bankReadBuilder->like($column, $value, 'both');
                } else {
                    $bankReadBuilder->orLike($column, $value, 'both');
                }
                $column_key++;
            }
            $bankReadBuilder->groupEnd();
        }

        if (!$this->session->system_admin) {
            $bankReadBuilder->where(array('bank.fk_account_system_id' => $this->session->user_account_system_id));
        }

        $bankReadBuilder->select($columns);
        $bankReadBuilder->join('status', 'status.status_id=bank.fk_status_id');
        $bankReadBuilder->join('account_system', 'account_system.account_system_id=bank.fk_account_system_id');

        $result_obj = $bankReadBuilder->get();

        $results = [];

        if ($result_obj->getNumRows() > 0) {
            $results = $result_obj->getResultArray();
        }

        return $results;
    }

    function count_banks()
    {
        $bankReadBuilder = $this->read_db->table('bank');
        $post = $this->request->getPost();
        $columns = $this->columns();
        $search_columns = $columns;

        // Searching

        $search = $post['search'];
        $value = $search['value'];

        array_shift($search_columns);

        if (!empty($value)) {
            $bankReadBuilder->groupStart();
            $column_key = 0;
            foreach ($search_columns as $column) {
                if ($column_key == 0) {
                    $bankReadBuilder->like($column, $value, 'both');
                } else {
                    $bankReadBuilder->oRlike($column, $value, 'both');
                }
                $column_key++;
            }
            $bankReadBuilder->groupEnd();
        }

        if (!$this->session->system_admin) {
            $bankReadBuilder->where(array('bank.fk_account_system_id' => $this->session->user_account_system_id));
        }

        $bankReadBuilder->join('status', 'status.status_id=bank.fk_status_id');
        $bankReadBuilder->join('account_system', 'account_system.account_system_id=bank.fk_account_system_id');


        $count_all_results = $bankReadBuilder->countAllResults();

        return $count_all_results;
    }

    function showList(): ResponseInterface
    {
        $post = $this->request->getPost();
        $draw = intval($post['draw']);
        $banks = $this->get_banks();
        $count_banks = $this->count_banks();

        $result = [];

        $cnt = 0;
        foreach ($banks as $bank) {
            $bank_id = array_shift($bank);
            $bank_track_number = $bank['bank_track_number'];
            $bank['bank_track_number'] = '<a href="' . base_url() . $this->controller . '/view/' . hash_id($bank_id) . '">' . $bank_track_number . '</a>';
            $bank['bank_is_active'] = $bank['bank_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
            $row = array_values($bank);

            $result[$cnt] = $row;

            $cnt++;
        }

        $response = [
            'draw' => $draw,
            'recordsTotal' => $count_banks,
            'recordsFiltered' => $count_banks,
            'data' => $result
        ];

        return $this->response->setJSON($response);
    }
}

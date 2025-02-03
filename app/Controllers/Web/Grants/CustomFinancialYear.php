<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Grants;


class CustomFinancialYear extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->library = new Grants\CustomFinancialYearLibrary();

    }

    function index(){}

    function result($id = "",  $parentTable = null){

        $result = parent::result($id, $parentTable);
    
        if($this->action == 'list'){
          $columns = alias_columns($this->library->listTableVisibleColumns());
          array_shift($columns);
          $result['columns'] = array_column($columns,'list_columns');
          $result['has_details_table'] = false; 
          $result['has_details_listing'] = false;
          $result['is_multi_row'] = false;
          $result['show_add_button'] = true;
        }
    
        return $result;
      }

      function showList():ResponseInterface{

        $draw =intval($this->request->getPost('draw'));
        $custom_financial_years = $this->getCustomFinancialYear();
        $count_custom_financial_years = $this->countCustomFinancialYear();
    
        $builder=$this->read_db->table('month');
        $builder->select(array('month_number', 'month_name'));
        $months=$builder->get()->getResultArray();
    
        $month_numbers = array_column($months, 'month_number');
        $month_names = array_column($months, 'month_name');
    
        $months = array_combine($month_numbers, $month_names);
    
        $result = [];
    
        $cnt = 0;
        foreach($custom_financial_years as $year){
          $year_id = array_shift($year);
          $year_track_number = $year['track_number'];
          $year['track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($year_id).'">'.$year_track_number.'</a>';
          $year['is_active'] = $year['is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
          $year['is_default'] = $year['is_default'] == 1 ? get_phrase('yes') : get_phrase('no');
          $year['start_month'] = $months[$year['start_month']];
          $row = array_values($year);
    
          $result[$cnt] = $row;
    
          $cnt++;
        }
    
        $response = [
          'draw'=>$draw,
          'recordsTotal'=>$count_custom_financial_years,
          'recordsFiltered'=>$count_custom_financial_years,
          'data'=>$result
        ];
        
       // echo json_encode($response);

        return $this->response->setJSON($response);
      }

      private function countCustomFinancialYear(){

        $columns = $this->columns();
        $search_columns = $search_columns = array_column(alias_columns($columns),'query_columns'); // $columns;
    
        // Searching
    
        $search = $this->request->getPost('search');
        $value = $search['value'];
    
        array_shift($search_columns);
    
        $query = $this->read_db->table('custom_financial_year');
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
          $query->where(array('office.fk_account_system_id'=>$this->session->user_account_system_id));
        }
    
        $query->select($columns);
        $query->join('office','office.office_id=custom_financial_year.fk_office_id');
        $query->join('account_system','account_system.account_system_id=office.fk_account_system_id');    
    
        //$query->from('custom_financial_year');
        $count_all_results = $query->countAllResults();
    
        return $count_all_results;
      }


      private function getCustomFinancialYear(){

        $columns = $this->columns();
        $search_columns = $search_columns = array_column(alias_columns($columns),'query_columns'); // $columns;
    
        // Limiting records
        $start = intval($this->request->getPost('start'));
        $length = intval($this->request->getPost('length'));

        $query = $this->read_db->table('custom_financial_year')->limit($length, $start);
    
        // Ordering records
    
        $order = $this->request->getPost('order');
        $col = '';
        $dir = 'desc';
        
        if(!empty($order)){
          $col = $order[0]['column'];
          $dir = $order[0]['dir'];
        }
              
        if( $col == ''){
          $query->orderBy('custom_financial_year_id', 'DESC');
        }else{
          $query->orderBy($columns[$col],$dir); 
        }
    
        // Searching
    
        $search =$this->request->getPost('search');
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
          $query->where('office.fk_account_system_id',$this->session->user_account_system_id);
        }
    
        $query->select($columns);
        $query->join('office','office.office_id=custom_financial_year.fk_office_id');
        $query->join('account_system','account_system.account_system_id=office.fk_account_system_id');
        $query->whereIn('custom_financial_year.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    
        $result_obj = $query->get();
        
        $results = [];
    
        if($result_obj->getNumRows() > 0){
          $results = $result_obj->getResultArray();
        }
    
        return $results;
      }

      private function columns(){
        $columns = [
          'custom_financial_year_id',
          'custom_financial_year_track_number as track_number',
          'office_name',
          'custom_financial_year_start_month as start_month',
          'custom_financial_year_reset_date as reset_date',
          'custom_financial_year_is_active as is_active',
          'custom_financial_year_is_default as is_default',
          'custom_financial_year_created_date as created_date',
          // 'custom_financial_year_last_modified_date as modified_date'
        ];
    
        return $columns;
      }

      

}

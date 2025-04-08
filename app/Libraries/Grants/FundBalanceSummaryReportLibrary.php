<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\FundBalanceSummaryReportModel;
class FundBalanceSummaryReportLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;
    private $selected_account_system_id = 0;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new FundBalanceSummaryReportModel();

        $this->table = 'grants';
    }

    public function loggedCashBalanceReport(String $period, int $offset, int $limit, String $order_col, String $order_dir, String $search_value, String $search_column){
        $this->setSelectedAccountSystemId();
  
        $session_office_ids = array_column($this->session->hierarchy_offices,'office_id');
        $period = date('Y-m-01', strtotime($period));
  
        $financial_report_ids = $this->financialReportIds($session_office_ids, $period, 'closing_total_cash_balance_data' ,$order_col ,$order_dir, $search_value, 'cash_breakdown');
        $office_ids = array_keys(array_slice($financial_report_ids, $offset, $limit,true)); // Get only the offices that have a report for th month specified
  
        $records = [
          'records' => [],
          'accounts' => []
        ];
  
        if(empty($office_ids)){
          return $records;
        }
  
        $builder = $this->read_db->table('financial_report');
        $builder->select(array('financial_report_id','financial_report_month as voucher_month','office_id','office_code', 'closing_total_cash_balance_data'));
        $builder->whereIn('fk_office_id', $office_ids);
        $builder->where(array('fk_account_system_id' => $this->selected_account_system_id, 'fk_context_definition_id' => 1));
        $builder->where(array('financial_report_month' => $period));
        $builder->like('closing_total_cash_balance_data','cash_breakdown','both');
        $builder->join('office','office.office_id=financial_report.fk_office_id');
        $obj = $builder->get();

        $records['office_ids'] = $financial_report_ids;
  
        if($obj->getNumRows() > 0){
            $recs = $obj->getResultArray();        
            foreach($recs as $row){
                $records['records'][$row['office_code']][$row['financial_report_id']] = json_decode($row['closing_total_cash_balance_data'],true);
            }
        }
  
        return $records;
  
      }

      function setSelectedAccountSystemId(){
		$this->selected_account_system_id = session()->get('user_account_system_id');

		if($this->request->getPost('account_system_id') > 0 && $this->session->system_admin){
			$this->selected_account_system_id = $this->request->getPost('account_system_id');
		}
	}

    function financialReportIds(array $office_ids, String $period, $data_column, $order_col = 'office_id', $order_dir = 'desc', $search_value = '', $data_column_value = ''): array {

        $start_month_date = date('Y-m-01', strtotime($period));
  
        $builder = $this->read_db->table('financial_report');
        $builder->select(array('fk_office_id as office_id','financial_report_id','office_code'));
        $builder->whereIn('fk_office_id', $office_ids);
        $builder->where(array('financial_report_month' => $start_month_date, 'financial_report_is_submitted' => 1));
        $builder->join('office','office.office_id=financial_report.fk_office_id');
        if($order_col == 'office_id'){
            $builder->orderBy('office_code',$order_dir); 
        }

        if($data_column_value != ""){
            $builder->like($data_column, $data_column_value,'both'); 
        }
    
        if($search_value){
        $builder->like('office_code',$search_value,'both'); 
        }
        $obj = $builder->get();

        $records = [];
    
        if($obj->getNumRows() > 0){
        $records_raw = $obj->getResultArray();

        $office_codes = array_column($records_raw,'office_code');
        // log_message('error', json_encode($office_codes));

        $o_ids = array_column($records_raw,'office_id');
        $financial_report_ids = array_column($records_raw,'financial_report_id');

        $records = array_combine($o_ids, $financial_report_ids);

        }

        return $records;
        
      }

      public function loggedFundBalanceReport(String $period, bool $show_income_account_balance, int $offset, int $limit, String $order_col, String $order_dir, String $search_value, String $search_column)
      {
  
        $this->setSelectedAccountSystemId();
  
        $session_office_ids = array_column(session()->get('hierarchy_offices'),'office_id');
  
        $period = date('Y-m-01', strtotime($period));
        // log_message('error', json_encode($period));
        $records = [
          'records' => [],
          'accounts' => []
        ];
  
        // log_message('error', json_encode($show_income_account_balance));
  
        // log_message('error', json_encode($show_income_account_balance));
        $data_column = $show_income_account_balance ? 'closing_fund_balance_data' : 'closing_project_balance_data';
        $summary_type = $show_income_account_balance ? ['closing_fund_balance_data <>' => NULL] : ['closing_project_balance_data <>' => NULL];
  
        $financial_report_ids = $this->financialReportIds($session_office_ids, $period, $data_column, $order_col ,$order_dir, $search_value);
        $office_ids = array_keys(array_slice($financial_report_ids, $offset, $limit,true)); // Get only the offices that have a report for th month specified
        
        // log_message('error', json_encode($office_ids));
  
        // Give an empty return if no financial reports found for the period
        if(empty($office_ids)){
          return $records;
        }
        
        $builder = $this->read_db->table('financial_report');
        $builder->select(array('financial_report_id','financial_report_month as voucher_month','office_id','office_code', $data_column));
        $builder->whereIn('fk_office_id', $office_ids);
        $builder->where(array('fk_account_system_id' => $this->selected_account_system_id, 'fk_context_definition_id' => 1));
        $builder->where(array('financial_report_month' => $period));
        $builder->where($summary_type);
        $builder->join('office','office.office_id=financial_report.fk_office_id');

        if($search_column > 0){
            if($show_income_account_balance){
              $builder->like('closing_fund_balance_data', '"'.$search_column.'"', 'both'); 
            }else{
              $builder->like('closing_project_balance_data', '"'.$search_column.'"', 'both'); 
            }
        }
  
        $obj = $builder->get();
        
        $records['office_ids'] = $financial_report_ids;
  
        if($obj->getNumRows() > 0){
            $recs = $obj->getResultArray();        
            foreach($recs as $row){
                $records['records'][$row['office_code']][$row['financial_report_id']] = !is_null($row[$data_column]) ? json_decode($row[$data_column],true) : [];
            }
        }
  
        return $records;


        // $this->read_db->select(array('financial_report_id','financial_report_month as voucher_month','office_id','office_code', $data_column));
  
        // $this->read_db->where_in('fk_office_id', $office_ids);
        // $this->read_db->where(array('fk_account_system_id' => $this->selected_account_system_id, 'fk_context_definition_id' => 1));
        // $this->read_db->where(array('financial_report_month' => $period));
        // $this->read_db->where($summary_type);
        // $this->read_db->join('office','office.office_id=financial_report.fk_office_id');
  
        // if($search_column > 0){
        //     if($show_income_account_balance){
        //       $this->read_db->like('closing_fund_balance_data', '"'.$search_column.'"', 'both'); 
        //     }else{
        //       $this->read_db->like('closing_project_balance_data', '"'.$search_column.'"', 'both'); 
        //     }
        // }
  
        // $obj = $this->read_db->get('financial_report');
        
        // $records['office_ids'] = $financial_report_ids;
  
        // if($obj->num_rows() > 0){
        //     $recs = $obj->result_array();        
        //     foreach($recs as $row){
        //         $records['records'][$row['office_code']][$row['financial_report_id']] = !is_null($row[$data_column]) ? json_decode($row[$data_column],true) : [];
        //     }
        // }
  
        // return $records;
    }

   
}
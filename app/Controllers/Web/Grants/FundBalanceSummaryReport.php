<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Grants\OfficeCashLibrary;
use App\Libraries\Grants\FundBalanceSummaryReportLibrary;

class FundBalanceSummaryReport extends WebController
{
    private $civ_accounts = [];
	private $income_accounts = [];
	private $cols = [];
	private $period = null;
    private $office_cash_library;
    private $fund_balance_summary_report_library;

	private $selected_account_system_id = 0;

	function __construct(){
		$this->office_cash_library = new OfficeCashLibrary();
        $this->fund_balance_summary_report_library = new FundBalanceSummaryReportLibrary();
	}

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function result($id = '', $parentId = null){

        $result = parent::result($id, $parentId);

		if($this->action == 'list'){
			$result['columns'] = $this->getFundColumns();
			$result['accounting_system'] = $this->getAccountingSystems();
			$result['month'] = date('Y-m-01', strtotime('last day of previous month'));
			$result['has_details_table'] = false;
			$result['has_details_listing'] = false;
			$result['is_multi_row'] = false;
			$result['show_add_button'] = false;
		}else{
			$result = parent::result($id);
		}

		return $result;
	}

    function getFundColumns(){
		$this->setSelectedAccountSystemId();
		$fund_income_accounts = $this->getFundIncomeAccounts();

		$accounts = $this->formatDatatableHeaders($fund_income_accounts);

		return $accounts;
	}

    function setSelectedAccountSystemId(){
		$this->selected_account_system_id = session()->get('user_account_system_id');

		if($this->request->getPost('account_system_id') > 0 && session()->get('system_admin')){
			$this->selected_account_system_id = $this->request->getPost('account_system_id');
		}
	}

    function getAccountingSystems(){
		$accounting_systems = [];
        $builder = $this->read_db->table('account_system');
        $builder->select(array('account_system_id','account_system_name','office_name'));
        $builder->where(array('office.fk_context_definition_id' => 4));
        $builder->join('office','office.fk_account_system_id=account_system.account_system_id');
        $accounting_systems_obj = $builder->get();

        if($accounting_systems_obj->getNumRows() > 0){
			$accounting_systems_raw = $accounting_systems_obj->getResultArray();

			foreach($accounting_systems_raw as $office){
				$accounting_systems[$office['account_system_id']] = $office['office_name'];
			}
		}

		return $accounting_systems;
	}

    function getFundIncomeAccounts(){
		return $this->accountSystemIncomeAccounts($this->selected_account_system_id, true);
	}

    function formatDatatableHeaders($header_cols, $show_total_columns = true){
		$rst = [];
		// log_message('error', json_encode($header_cols));
		$rst[0]['data'] = 'office_code';
		$rst[0]['title'] = get_phrase('office_code');

		$i = 1;
		foreach($header_cols as $key => $col){
			$rst[$i]['data'] = $col;
			$rst[$i]['title'] = $col;
			$rst[$i]['id'] = $key;
			$i++;
		}

		if($show_total_columns){
			$rst[$i]['data'] = 'totals';
			$rst[$i]['title'] = get_phrase('totals');
		}

		return $rst;
	}

    function accountSystemIncomeAccounts($selected_account_system_id, $show_income_account_balance){
		$income_accounts = [];

		$columns = array('income_account_id as account_id','income_account_code as account_code');

		if(!$show_income_account_balance){
			$columns = array('project_id as account_id','project_code as account_code');
		}
        $builder=$this->read_db->table('income_account');
        $builder->select($columns);
		// $this->read_db->select($columns);
		if(!session()->get('system_admin')){
			$builder->where(array('fk_account_system_id' => $this->selected_account_system_id));
		}

		if(!$show_income_account_balance){
			$builder->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
			$builder->join('project','project.project_id=project_income_account.fk_project_id');
			$builder->where('project_end_date IS NOT NULL AND project_end_date <> "0000-00-00"');
		}

		$account_id = $this->request->getPost('accounts');

		// log_message('error', json_encode($account_id));

		if($account_id > 0){
			if($show_income_account_balance){
				$builder->where(array('income_account_id' => $account_id));
			}else{
				$builder->where(array('project_id' => $account_id));
			}
		}

		$obj = $builder->get();

		if($obj->getNumRows() > 0){
			$income_accounts_raw = $obj->getResultArray();

			$ids = array_column($income_accounts_raw,'account_id');
			$codes = array_column($income_accounts_raw,'account_code');

			$income_accounts = array_combine($ids, $codes);
		}

		return $income_accounts;
	}

    function fundColumns($report_category){

		$result = [];

		switch($report_category){
			case "month_cash_balance":{
				$result['columns'] = $this->getCashColumns();
				$result['accounts'] = $this->getCashAccountColumns();
				// log_message('error', json_encode($result['accounts']));
				break;
			}
			case "month_expense":{
				$result['columns'] = $this->getMonthExpenseReportDatatableColumns();
				break;
			}
			case "month_income":{
				$result['columns'] = $this->getMonthIncomeReportDatatableColumns();
				//$result['accounts'] = $result['columns'];
				break;
			}

			case "fund_balance_trial":{
				$result['columns'] = $this->getFundBalanceDatatableColumns();
				$result['accounts'] = $this->getFundColumns();
				break;
			}

			default:{
				$result['columns'] = $this->getFundColumns();
				$result['accounts'] = $this->getFundColumns();
				// log_message('error', json_encode($result['accounts']));
			}
		}

		echo json_encode($result);


	}

    function getCashColumns(){
		$this->setSelectedAccountSystemId();

		$cash_balance_headers = ["opening",'income','expense','closing'];

		$columns = $this->formatDatatableHeaders($cash_balance_headers, false);

		return $columns;
	}

    function getCashAccountColumns(){
		$this->setSelectedAccountSystemId();
		
		$office_cash_accounts = $this->office_cash_library->getActiveOfficeCash($this->selected_account_system_id);

		$office_cash_ids = array_column($office_cash_accounts, 'office_cash_id');
		$office_cash_names = array_column($office_cash_accounts, 'office_cash_name');

		$accounts = array_combine($office_cash_ids, $office_cash_names);

		$columns = $this->formatDatatableHeaders($accounts, false);

		return $columns;
	}

    function getMonthExpenseReportDatatableColumns(): array
	{
		$this->setSelectedAccountSystemId();

        $builder = $this->read_db->table('income_account');
        $builder->select('income_account_code, income_account_name, income_account_id');
        $builder->where('fk_account_system_id', $this->selected_account_system_id);
        $fund_income_accounts = $builder->get()->getResultArray();

		$user_context_definition_level = $this->session->context_definition['context_definition_level'];
		$columns_to_append = $this->determineColumnsByContext($user_context_definition_level);

		// Initialize columns with the first column (Office Code)
		$columns[] = [
			'data' => "office_code",
			'title' => get_phrase('office_code'),
			'visible' => true
		];


// Append additional columns based on the context
		foreach ($columns_to_append as $column) {
			$columns[] = [
				'data' => $column,
				'title' => ucfirst($column),
				'visible' => true
			];
		}

		$count = 1;

		// Add parent and child columns in the right order
		foreach ($fund_income_accounts as $income_account) {

			$income_account_code = $income_account['income_account_code'];
			$income_account_name = $income_account['income_account_name'];
			$income_account_id = $income_account['income_account_id'];

			// Get and add the child columns for each parent
			$expense_accounts = $this->getExpensesAccounts($income_account_id);

			foreach ($expense_accounts as $expense_account) {


				$income_account_label = $income_account_code . ' - ' . $expense_account['expense_account_code'];
				$name = $expense_account['expense_account_name'];
				$columns[] = [
					'data' => $income_account_label,
					'title' => $income_account_label,
					'id' => $count,
					'name' => $name,
					'parent' => $income_account_code,
					'visible' => false, // Initially hidden child columns
					'is_parent' => false,
					'className' => 'text-right'
				];
				$count++;
			}

			$columns[] = [
				'is_parent' => true,
				'name' => $income_account_name,
				'data' => $income_account_code,
				'title' => $income_account_code,
				'id' => $income_account_code,
				'className' => 'text-right'
			];

		}

		$columns[] = [
			'data' => "totals",
			'title' => get_phrase('totals'),
			'className' => 'text-right',
			'visible' => true
		];

		return $columns;
	}

    function determineColumnsByContext($context_id):array{

		switch($context_id){
			case "6":{ //global
				$columns = ['region', 'country', 'cohort', 'cluster'];
				break;
			}
			case "5":{ //region
				$columns = ['region', 'country', 'cohort', 'cluster'];
				break;
			}
			case "4":{ //country
				$columns = ['country', 'cohort', 'cluster'];
				break;
			}
			case "3":{ //cohort
				$columns = ['cohort', 'cluster'];
				break;
			}
			case "2":{ //cluster
				$columns = ['cluster'];
				break;
			}
			case "1":{ //center
				$columns = [];
				break;
			}
			default:{
				$columns = [];
			}
		}
		return $columns;

	}

    function getExpensesAccounts($income_account_id){
        $builder = $this->read_db->table('expense_account');
        $builder->select('expense_account_code, expense_account_name');
        $builder->where('fk_income_account_id', $income_account_id);
        return $builder->get()->getResultArray();

	}

    function getMonthIncomeReportDatatableColumns(): array
	{
		$this->setSelectedAccountSystemId();
        $builder = $this->read_db->table('income_account');
        $builder->select('income_account_code, income_account_name, income_account_id ');
        $builder->where('fk_account_system_id', $this->selected_account_system_id);
        $fund_income_accounts = $builder->get()->getResultArray();

		// $fund_income_accounts = $this->read_db->select('income_account_code, income_account_name, income_account_id ')
		// 	->where('fk_account_system_id', $this->selected_account_system_id)
		// 	->get('income_account')->result_array();


		// Initialize columns with the first column (Office Code)
		$user_context_definition_level = $this->session->context_definition['context_definition_level'];
		$columns_to_append = $this->determineColumnsByContext($user_context_definition_level);

		// Initialize columns with the first column (Office Code)
		$columns[] = [
			'data' => "office_code",
			'title' => get_phrase('office_code'),
			'visible' => true
		];

// Append additional columns based on the context
		foreach ($columns_to_append as $column) {
			$columns[] = [
				'data' => $column,
				'title' => ucfirst($column),
				'visible' => true,
				'className' => 'text-right'
			];
		}

		$count = 1;

		// Add parent and child columns in the right order
		foreach ($fund_income_accounts as $income_account) {
			$income_account_code = $income_account['income_account_code'];
			$income_account_name = $income_account['income_account_name'];
			$income_account_id = $income_account['income_account_id'];

			$columns[] = [
				'is_parent' => true,
				'name' => $income_account_name,
				'data' => $income_account_code,
				'title' => $income_account_code,
				'id' => $income_account_code,
				'className' => 'text-right',
			];
		}

		$columns[] = [
			'data' => "totals",
			'title' => get_phrase('totals'),
			'className' => 'text-right',
			'visible' => true
		];

		return $columns;
	}

    function getFundBalanceDatatableColumns(): array
	{
		$this->setSelectedAccountSystemId();
        $builder = $this->read_db->table('income_account');
        $builder->select('income_account_id, income_account_code, income_account_name, income_account_id ');
        $builder->where('fk_account_system_id', $this->selected_account_system_id);
        $fund_income_accounts = $builder->get()->getResultArray();

		// $fund_income_accounts = $this->read_db->select('income_account_id, income_account_code, income_account_name, income_account_id ')
		// 	->where('fk_account_system_id', $this->selected_account_system_id)
		// 	->get('income_account')->result_array();


		// Initialize columns with the first column (Office Code)
		$user_context_definition_level = $this->session->context_definition['context_definition_level'];
		$columns_to_append = $this->determineColumnsByContext($user_context_definition_level);

		// Initialize columns with the first column (Office Code)
		$columns[] = [
			'data' => "office_code",
			'title' => get_phrase('office_code'),
			'visible' => true
		];


// Append additional columns based on the context
		foreach ($columns_to_append as $column) {
			$columns[] = [
				'data' => $column,
				'title' => ucfirst($column),
				'visible' => true
			];
		}

		$count = 1;

		// Add parent and child columns in the right order
		foreach ($fund_income_accounts as $income_account) {
			$income_account_code = $income_account['income_account_code'];
			$income_account_name = $income_account['income_account_name'];
			$income_account_id = $income_account['income_account_id'];

			$columns[] = [
				'is_parent' => true,
				'name' => $income_account_name,
				'data' => $income_account_code,
				'title' => $income_account_code,
				'id' => $income_account_id,
				'className' => 'text-right'
			];
		}

		$columns[] = [
			'data' => "totals",
			'title' => get_phrase('totals'),
			'className' => 'text-right',
			'visible' => true
		];

		return $columns;


	}

    function fundShowList($report_category){
		$this->setSelectedAccountSystemId();
		$draw =intval($this->request->getPost('draw'));

		$balances = [];
		$count_balances = 0;
		$response = [];

		switch($report_category){
			case "month_cash_balance":{
				$balances = $this->getCashBalances();
				$count_balances = $this->countCashBalances();
				$data = $balances['data'];

				$response = [
					"draw" => $draw,
					"recordsTotal" => $count_balances,
					"recordsFiltered" => $count_balances,
					"data" => $data
				];
				break;
			}
			case "month_income":{
				$response = $this->getMonthIncome();
				$response['draw'] = $draw;
				break;
			}
			case "month_expense":{
				$response = $this->getMonthExpenses();
				$response['draw'] = $draw;
				break;
			}
			case "fund_balance_trial":{
				$response = $this->getMonthFundBalances();
				$response['draw'] = $draw;
				break;
			}
			default:{
				$balances = $this->getFundBalances();

				$count_balances = $this->countFundBalances();

				$data = $balances['data'];

				$response = [
					"draw" => $draw,
					"recordsTotal" => $count_balances,
					"recordsFiltered" => $count_balances,
					"data" => $data
				];
			}
		}




		// log_message('error', json_encode($this->input->post('account_system_id')));



		echo json_encode($response);
	}

    function getCashBalances(){
		// $this->load->model('office_cash_model');

		$this->period = $this->request->getPost('date_range') != null ? $this->request->getPost('date_range') : date('Y-m-01');
		$result = [];
		$header_cols = [];
		$this->setSelectedAccountSystemId();

		// Limiting records
		$start = intval($this->request->getPost('start'));
		$length = intval($this->request->getPost('length'));

		$order = $this->request->getPost('order');
		$col = '';
		$dir = 'desc';

		if(!empty($order)){
			$col = $order[0]['column'];
			$dir = $order[0]['dir'];
		}

		if( $col == '' || $col == 0){
			$col = 'office_id';
		}


		//  $account_system_cash_accounts = $this->office_cash_model->get_active_office_cash($this->selected_account_system_id);
		$cash_balance_headers = ["opening",'income','expense','closing'];

		if (is_numeric($col) && $col > 0) {
			$col = array_values($account_system_income_accounts)[$col - 1];
		}

		$search = $this->request->getPost('search');
		$search_value = $search['value'] ?? '';

		$search_column = $this->request->getPost('accounts');

		//  log_message('error', json_encode($search_column));

		$cash_balance_summary = $this->fund_balance_summary_report_library->loggedCashBalanceReport($this->period, $start, $length, $col, $dir, $search_value, $search_column);

		//  log_message('error', json_encode($cash_balance_summary));

		$cnt = 0;

		foreach($cash_balance_summary['records'] as $office_code => $financial_report){
			foreach($financial_report as $financial_report_id => $balances){

				if(!is_array($balances) || !array_key_exists('cash_breakdown', $balances)) continue;

				$cash_at_hand_balances = $balances['cash_breakdown']['cash_at_hand'];

				//  log_message('error', json_encode(['search_column' => $search_column, 'cash_at_hand_balances' => $cash_at_hand_balances]));

				//  if($search_column > 0 &&  !array_key_exists($search_column, $cash_at_hand_balances)) continue;


				$result[$cnt]['office_code'] = '<a target="_blank" href="'.base_url().'financial_report/view/'.hash_id($financial_report_id,"encode").'">'.$office_code.'</a>';
				$header_cols['office_code'] = get_phrase('office_code');
				$innerCnt = 1;
				//  $sum = 0;

				foreach($cash_balance_headers as $cash_balance_header){
					// $result[$cnt][$cash_balance_header]
					$amount = 0;
					if(!empty($cash_at_hand_balances)){
						if($search_column > 0){
							$amount = $cash_at_hand_balances[$search_column][$cash_balance_header] ?? 0;
						}else{
							$amount = array_sum(array_column($cash_at_hand_balances,$cash_balance_header));
						}

					}

					$header_cols[$cash_balance_header] = $cash_balance_header;
					$result[$cnt][$cash_balance_header] = number_format($amount,2);
					$innerCnt++;
				}

			}
			$cnt++;
		}

		$rst = ['data' => $result, 'columns' => $header_cols];

		//  log_message('error', json_encode($rst));
		//  $rst['data'] = [];
		return $rst;
	}

    function countCashBalances(){
		$office_ids = $this->userOfficesWithSubmittedReportAndLoggedCashBalances(true);
		return  count($office_ids);
	}

    function userOfficesWithSubmittedReportAndLoggedCashBalances($show_income_account_balance){

		$account_id = $this->request->getPost('accounts');

        $builder = $this->read_db->table('financial_report');
        $builder->select(array('fk_office_id as office_id','office_code'));
        $builder->whereIn('office_id', array_column($this->session->hierarchy_offices,'office_id'));
        $builder->where(array('fk_account_system_id' => $this->selected_account_system_id, 'fk_context_definition_id' => 1));
        $builder->where(array('financial_report_is_submitted' => 1,'financial_report_month' => date('Y-m-01', strtotime($this->period))));
        $builder->like('closing_total_cash_balance_data','cash_breakdown','both');
        $builder->join('office','office.office_id=financial_report.fk_office_id');
        $offices_with_report = $builder->get();

        $offices = [];

		if($offices_with_report->getNumRows() > 0){
			$records = $offices_with_report->getResultArray();
			$office_ids = array_column($records,'office_id');
			$office_codes = array_column($records,'office_code');

			$offices = array_combine($office_ids, $office_codes);
		}

		return $offices;

	}

    function getMonthIncome(){

		$date_range =  $this->period = $this->request->getPost('date_range') != null ? $this->request->getPost('date_range') : date('Y-m-01');
		$start_date =  $this->period = $this->request->getPost('start_date') != null ? $this->request->getPost('start_date') : date('Y-m-01');
		$end_date =  $this->period = $this->request->getPost('end_date') != null ? $this->request->getPost('end_date') : date('Y-m-01');


		$result = [];
		$header_cols = [];

		$this->setSelectedAccountSystemId();

		// Limiting records
		$start = intval($this->request->getPost('start'));
		$length = intval($this->request->getPost('length'));

		$order = $this->request->getPost('order');
		$col = '';
		$dir = 'desc';

		if(!empty($order)){
			$col = $order[0]['column'];
			$dir = $order[0]['dir'];
		}

		if( $col == '' || $col == 0){
			$col = 'office.office_id';
		}

		$account_system_income_accounts = $this->getFundIncomeAccounts();


		if (is_numeric($col) && $col > 0) {
			$col = array_values($account_system_income_accounts)[$col - 1];
		}

		$search = $this->request->getPost('search');
		$search_value = $search['value'] ?? '';

		$search_column = $this->request->getPost('accounts');
		//  log_message('error', json_encode($search_column));
		$FCPData = $this->getFCPsWithMonthReport($start_date, $end_date, $start, $length, $col, $dir, $search_value);
		$FCPs = $FCPData['data'];

		$user_context_definition_level = $this->session->context_definition['context_definition_level'];
		$columns_to_append = $this->determineColumnsByContext($user_context_definition_level);

		$cnt = 0;

		foreach($FCPs as $FCP){

			$incomes = $this->getMonthIncomeByFCPId($start_date, $end_date,$FCP['fk_office_id']);


			$result[$cnt]['office_code'] = $FCP['office_code'];
			foreach ($columns_to_append as $column) {
				// Ensure the FCP array has the key for the column before adding
				if (isset($FCP[$column])) {
					$result[$cnt][$column] = $FCP[$column];
				}
			}

			$sum = 0;


			foreach($account_system_income_accounts as $income_account_id => $income_account_code){

				$totalIncome = 0;
				$html = '';
				if(array_key_exists($income_account_code, $incomes)){

					foreach($incomes as $incomeAccount => $amount){
						$header = $incomeAccount;
						$header_cols[$header] = $header;
						if($amount == ""){
							$amount = 0;
						}
						$totalIncome += floatval($amount);
						$result[$cnt][$header] = number_format(floatval($amount),2);
					}



				}

				$sum += $totalIncome;
				$result[$cnt][$income_account_code] = number_format(floatval($totalIncome),2);

			} //end foreach income account

			$result[$cnt]['totals'] = number_format(floatval($sum),2);
			$cnt++;
		}

		$rst = ['data' => $result, 'recordsTotal' => $FCPData['count'], 'recordsFiltered' => $FCPData['count']];

		// log_message('error', json_encode($rst));

		return $rst;
	}

    function getMonthExpenses(){

		$date_range =  $this->period = $this->request->getPost('date_range') != null ? $this->request->getPost('date_range') : date('Y-m-01');
		$start_date =  $this->period = $this->request->getPost('start_date') != null ? $this->request->getPost('start_date') : date('Y-m-01');
		$end_date =  $this->period = $this->request->getPost('end_date') != null ? $this->request->getPost('end_date') : date('Y-m-01');

		$result = [];
		$header_cols = [];

		$this->setSelectedAccountSystemId();

		// Limiting records
		$start = intval($this->request->getPost('start'));
		$length = intval($this->request->getPost('length'));

		$order = $this->request->getPost('order');
		$col = '';
		$dir = 'desc';

		if(!empty($order)){
			$col = $order[0]['column'];
			$dir = $order[0]['dir'];
		}

		if( $col == '' || $col == 0){
			$col = 'office.office_id';
		}

		$account_system_income_accounts = $this->getFundIncomeAccounts();

		if (is_numeric($col) && $col > 0) {
			$col = array_values($account_system_income_accounts)[$col - 1];
		}

		$search = $this->request->getPost('search');
		$search_value = $search['value'] ?? '';

		$search_column = $this->request->getPost('accounts');
		//  log_message('error', json_encode($search_column));
		$fcp_data = $this->getFCPsWithMonthReport($start_date, $end_date, $start, $length, $col, $dir, $search_value);
		$FCPs = $fcp_data['data'];
		$cnt = 0;

		$user_context_definition_level = $this->session->context_definition['context_definition_level'];
		$columns_to_append = $this->determineColumnsByContext($user_context_definition_level);

		foreach($FCPs as $FCP){


			$expenses = $this->getMonthExpensesByFCPId($start_date, $end_date, $FCP['fk_office_id']);


			$result[$cnt]['office_code'] = $FCP['office_code'];
			foreach ($columns_to_append as $column) {
				// Ensure the FCP array has the key for the column before adding
				if (isset($FCP[$column])) {
					$result[$cnt][$column] = $FCP[$column];
				}
			}


			$sum = 0;

			foreach($account_system_income_accounts as $income_account_id => $income_account_code){

				$totalExpense = 0;
				$html = '';
				if(array_key_exists($income_account_code, $expenses)){

					foreach($expenses[$income_account_code] as $expenseAccount => $amount){
						$header = $income_account_code.' - '.$expenseAccount;
						$header_cols[$header] = $header;
						if($amount == ""){
							$amount = 0;
						}
						$totalExpense += floatval($amount);
						$result[$cnt][$header] = number_format(floatval($amount),2);
					}



				}

				$sum += $totalExpense;

				$result[$cnt][$income_account_code] = number_format(floatval($totalExpense),2);

			} //end foreach income account

			$result[$cnt]['totals'] = number_format(floatval($sum),2);

			$cnt++;
		}

		$rst = ['data' => $result, 'recordsTotal' => $fcp_data['count'], 'recordsFiltered' => $fcp_data['count']];

		// log_message('error', json_encode($rst));

		return $rst;
	}

    public function getMonthFundBalances()
	{
		$date_range =  $this->period = $this->request->getPost('date_range') != null ? $this->request->getPost('date_range') : date('Y-m-01');
		$start_date =  $this->period = $this->request->getPost('start_date') != null ? $this->request->getPost('start_date') : date('Y-m-01');
		$end_date =  $this->period = $this->request->getPost('end_date') != null ? $this->request->getPost('end_date') : date('Y-m-01');

		$result = [];
		$header_cols = [];

		$this->setSelectedAccountSystemId();

		// Limiting records
		$start = intval($this->request->getPost('start'));
		$length = intval($this->request->getPost('length'));

		$order = $this->request->getPost('order');
		$col = '';
		$dir = 'desc';

		if(!empty($order)){
			$col = $order[0]['column'];
			$dir = $order[0]['dir'];
		}

		if( $col == '' || $col == 0){
			$col = 'office.office_id';
		}

		$account_filter = $this->request->getPost('accounts');

		$account_system_income_accounts = $this->getIncomeAccounts($account_filter);


		if (is_numeric($col) && $col > 0) {
			$col = array_values($account_system_income_accounts)[$col - 1];
		}

		$search = $this->request->getPost('search');
		$search_value = $search['value'] ?? '';

		$search_column = $this->request->getPost('accounts');
		//  log_message('error', json_encode($search_column));
		$fcp_data = $this->getFCPsWithMonthReport($start_date, $end_date, $start, $length, $col, $dir, $search_value);
		$FCPs = $fcp_data['data'];
		$cnt = 0;



		$user_context_definition_level = $this->session->context_definition['context_definition_level'];
		$columns_to_append = $this->determineColumnsByContext($user_context_definition_level);



		foreach($FCPs as $FCP){


			$balances = $this->getMonthFundBalanceByFCPId($start_date, $end_date, $FCP['fk_office_id']);

			//var_dump($balances);


			$result[$cnt]['office_code'] = $FCP['office_code'];
			foreach ($columns_to_append as $column) {
				// Ensure the FCP array has the key for the column before adding
				if (isset($FCP[$column])) {
					$result[$cnt][$column] = $FCP[$column];
				}
			}


			foreach($account_system_income_accounts as $income_account_id => $income_account_code){

				$totalIncome = 0;

				foreach($balances as $incomeAccount => $amount){
					$header = $incomeAccount;
					$header_cols[$header] = $header;
					if($amount == ""){
						$amount = 0;
					}
					$totalIncome += floatval($amount);
					$result[$cnt][$header] = number_format(floatval($amount),2);
				}


				//$sum += $totalExpense;
				//var_dump($totalExpense);
				$result[$cnt]['totals'] = number_format(floatval($totalIncome),2);
				//var_dump($income_account_code);
				if(array_key_exists($income_account_code, $balances)){
					$result[$cnt][$income_account_code] = number_format(floatval($totalIncome),2);
				} else{
					$result[$cnt][$income_account_code] = number_format(floatval(0),2);
				}


			} //end foreach income account


			//$header_cols['totals'] = 'Totals';
			$cnt++;
		}

		$rst = ['data' => $result, 'recordsTotal' => $fcp_data['count'], 'recordsFiltered' => $fcp_data['count']];

		// log_message('error', json_encode($rst));

		return $rst;
	}

    function getFundBalances(){
		$show_income_account_balance = true;
		$this->period = $this->request->getPost('date_range') != null ? $this->request->getPost('date_range') : date('Y-m-01');

		$result = [];
		$header_cols = [];

		$this->setSelectedAccountSystemId();

		// Limiting records
		$start = intval($this->request->getPost('start'));
		$length = intval($this->request->getPost('length'));

		$order = $this->request->getPost('order');
		$col = '';
		$dir = 'desc';

		if(!empty($order)){
			$col = $order[0]['column'];
			$dir = $order[0]['dir'];
		}

		if( $col == '' || $col == 0){
			$col = 'office_id';
		}

		$account_system_income_accounts = $this->getFundIncomeAccounts();

		if (is_numeric($col) && $col > 0) {
			$col = array_values($account_system_income_accounts)[$col - 1];
		}

		$search = $this->request->getPost('search');
		$search_value = $search['value'] ?? '';

		$search_column = $this->request->getPost('accounts');
		//  log_message('error', json_encode($search_column));
		$fund_balance_summary = $this->fund_balance_summary_report_library->loggedFundBalanceReport($this->period, $show_income_account_balance, $start, $length, $col, $dir, $search_value, $search_column);

		//\var_dump($fund_balance_summary);


		$cnt = 0;

		foreach($fund_balance_summary['records'] as $office_code => $financial_report){

			foreach($financial_report as $financial_report_id => $balances){
				if(!is_array($balances)) continue;
				$result[$cnt]['office_code'] = '<a target="_blank" href="'.base_url().'financial_report/view/'.hash_id($financial_report_id,"encode").'">'.$office_code.'</a>';
				$header_cols['office_code'] = get_phrase('office_code');
				$innerCnt = 1;
				$sum = 0;
				foreach($account_system_income_accounts as $income_account_id => $income_account_code){

					$closing_balance = 0;
					if(array_key_exists($income_account_id, $balances)){
						$closing_balance = $balances[$income_account_id];
					}

					// if($search_column > 0 && $closing_balance == 0) {
					//   unset($result[$cnt]);
					//   continue;
					// }

					$sum += $closing_balance;
					$header_cols[$income_account_code] = $income_account_code;
					$result[$cnt][$income_account_code] = number_format($closing_balance,2);
					$innerCnt++;
				}
				$result[$cnt]['totals'] = number_format($sum,2);
				$header_cols['totals'] = 'Totals';
			}
			$cnt++;
		}

		$rst = ['data' => $result, 'columns' => $header_cols];


		// log_message('error', json_encode($rst));

		return $rst;
	}

    function getIncomeAccounts($filter = null): array
	{
		if($filter != null){
            $builder = $this->read_db->table('income_account');
            $builder->select('income_account_id, income_account_name, income_account_code');
            $builder->where('fk_account_system_id', $this->selected_account_system_id);
            $builder->where('income_account_id', $filter);
			
		}
		$data = $builder->get()->getResultArray();
		$accounts = [];
		foreach ($data as $account){
			$accounts[$account['income_account_id']] = $account['income_account_code'];
		}
		return $accounts;
	}

    function countFundBalances(){
		$office_ids = $this->userOfficesWithSubmittedReportAndLoggedBalances(true);
		return  count($office_ids);
	}

    function userOfficesWithSubmittedReportAndLoggedBalances($show_income_account_balance){

		$account_id = $this->request->getPost('accounts');

		$summary_type = $show_income_account_balance ? ['closing_fund_balance_data <>' => NULL] : ['closing_project_balance_data <>' => NULL];

        $builder = $this->read_db->table('financial_report');
        $builder->select(array('fk_office_id as office_id','office_code'));
        $builder->whereIn('office_id', array_column($this->session->hierarchy_offices,'office_id'));
        $builder->where(array('fk_account_system_id' => $this->selected_account_system_id, 'fk_context_definition_id' => 1));
        $builder->where(array('financial_report_is_submitted' => 1,'financial_report_month' => date('Y-m-01', strtotime($this->period))));
        $builder->where($summary_type);
        $builder->join('office','office.office_id=financial_report.fk_office_id');

        if($account_id > 0){
			if($show_income_account_balance){
				$builder->like('closing_fund_balance_data', '"'.$account_id.'"', 'both');
			}else{
				$builder->like('closing_project_balance_data', '"'.$account_id.'"', 'both');
			}
		}

		$offices_with_report = $builder->get();

		$offices = [];

		if($offices_with_report->getNumRows() > 0){
			$records = $offices_with_report->getResultArray();
			$office_ids = array_column($records,'office_id');
			$office_codes = array_column($records,'office_code');

			$offices = array_combine($office_ids, $office_codes);
		}

		return $offices;

	}

    function getFCPsWithMonthReport($start_date, $end_date, $offset, $limit, $order_col, $order_dir, $search_value): array {
		$this->setSelectedAccountSystemId();
		$session_office_ids = array_column(session()->get('hierarchy_offices'), 'office_id');

		$count_query = $this->read_db->table('financial_report')->select('COUNT(DISTINCT office.office_code) AS total_count')
			->join('office', 'financial_report.fk_office_id = office.office_id')
			->join('context_center', 'office.office_id = context_center.fk_office_id', 'left')
			->join('context_cluster', 'context_cluster.context_cluster_id = context_center.fk_context_cluster_id', 'left')
			->join('context_cohort', 'context_cohort.context_cohort_id = context_cluster.fk_context_cohort_id', 'left')
			->join('context_country', 'context_country.context_country_id = context_cohort.fk_context_country_id', 'left')
			->join('context_region', 'context_region.context_region_id = context_country.fk_context_region_id', 'left')
			->join('office as get_cluster_name', 'context_cluster.fk_office_id = get_cluster_name.office_id', 'left')
			->join('office as get_cohort_name', 'context_cohort.fk_office_id = get_cohort_name.office_id', 'left')
			->join('office as get_country_name', 'context_country.fk_office_id = get_country_name.office_id', 'left')
			->join('office as get_region_name', 'context_region.fk_office_id = get_region_name.office_id', 'left')
			->where("financial_report.financial_report_month BETWEEN '$start_date' AND '$end_date'")
			->groupStart()
			->orLike('office.office_code', $search_value, 'both')
			->orLike('get_cluster_name.office_code', $search_value, 'both')
			->orLike('get_cohort_name.office_code', $search_value, 'both')
			->orLike('get_country_name.office_code', $search_value, 'both')
			->orLike('get_region_name.office_code', $search_value, 'both')
			->groupEnd();

		if (!$this->session->system_admin) {
			$count_query->whereIn('financial_report.fk_office_id', $session_office_ids);
		} else {
			$count_query->where(array('office.fk_account_system_id' => $this->selected_account_system_id));
		}
		$count_query->where('financial_report_is_submitted', 1);
		$data['count'] = $count_query->get()->getRow()->total_count;

		$data['data'] = $this->read_db->table('financial_report')->distinct()->select('financial_report.fk_office_id, office.office_code')
			->select("get_cluster_name.office_code AS cluster")
			->select("get_cohort_name.office_code AS cohort")
			->select("get_country_name.office_code AS country")
			->select("get_region_name.office_code AS region")

			->join('office', 'financial_report.fk_office_id = office.office_id')
			->join('context_center', 'office.office_id = context_center.fk_office_id', 'left')
			->join('context_cluster', 'context_cluster.context_cluster_id = context_center.fk_context_cluster_id', 'left')
			->join('context_cohort', 'context_cohort.context_cohort_id = context_cluster.fk_context_cohort_id', 'left')
			->join('context_country', 'context_country.context_country_id = context_cohort.fk_context_country_id', 'left')
			->join('context_region', 'context_region.context_region_id = context_country.fk_context_region_id', 'left')

			->join('office as get_cluster_name', 'context_cluster.fk_office_id = get_cluster_name.office_id', 'left')
			->join('office as get_cohort_name', 'context_cohort.fk_office_id = get_cohort_name.office_id', 'left')
			->join('office as get_country_name', 'context_country.fk_office_id = get_country_name.office_id', 'left')
			->join('office as get_region_name', 'context_region.fk_office_id = get_region_name.office_id', 'left')

			->where("financial_report.financial_report_month BETWEEN '$start_date' AND '$end_date'")

			->groupStart()
			->orLike('office.office_code', $search_value, 'both')
			->orLike('get_cluster_name.office_code', $search_value, 'both')
			->orLike('get_cohort_name.office_code', $search_value, 'both')
			->orLike('get_country_name.office_code', $search_value, 'both')
			->orLike('get_region_name.office_code', $search_value, 'both')
			->groupEnd()

			->orderBy($order_col, $order_dir);

		if(!$this->session->system_admin){
			$data['data']->whereIn('financial_report.fk_office_id', $session_office_ids);
		} else{
			$data['data']->where(array('office.fk_account_system_id' => $this->selected_account_system_id));
		}

		$data['data']->where('financial_report_is_submitted', 1);
        $data['data']->limit($limit, $offset);
		$data['data'] = $data['data']->get()->getResultArray();

		return $data;
	}

    /*function get_FCPs_with_month_report($start_date, $end_date, $offset, $limit, $order_col, $order_dir, $search_value): array {
		$this->set_selected_account_system_id();
		$session_office_ids = array_column($this->session->hierarchy_offices, 'office_id');

		$count_query = $this->read_db->select('COUNT(DISTINCT office.office_code) AS total_count')
			->join('office', 'financial_report.fk_office_id = office.office_id')
			->join('context_center', 'office.office_id = context_center.fk_office_id', 'left')
			->join('context_cluster', 'context_cluster.context_cluster_id = context_center.fk_context_cluster_id', 'left')
			->join('context_cohort', 'context_cohort.context_cohort_id = context_cluster.fk_context_cohort_id', 'left')
			->join('context_country', 'context_country.context_country_id = context_cohort.fk_context_country_id', 'left')
			->join('context_region', 'context_region.context_region_id = context_country.fk_context_region_id', 'left')
			->join('office as get_cluster_name', 'context_cluster.fk_office_id = get_cluster_name.office_id', 'left')
			->join('office as get_cohort_name', 'context_cohort.fk_office_id = get_cohort_name.office_id', 'left')
			->join('office as get_country_name', 'context_country.fk_office_id = get_country_name.office_id', 'left')
			->join('office as get_region_name', 'context_region.fk_office_id = get_region_name.office_id', 'left')
			->where("financial_report.financial_report_month BETWEEN '$start_date' AND '$end_date'")
			->group_start()
			->or_like('office.office_code', $search_value, 'both')
			->or_like('get_cluster_name.office_code', $search_value, 'both')
			->or_like('get_cohort_name.office_code', $search_value, 'both')
			->or_like('get_country_name.office_code', $search_value, 'both')
			->or_like('get_region_name.office_code', $search_value, 'both')
			->group_end();

		if (!$this->session->system_admin) {
			$count_query->where_in('financial_report.fk_office_id', $session_office_ids);
		} else {
			$count_query->where(array('office.fk_account_system_id' => $this->selected_account_system_id));
		}
		$count_query->where('financial_report_is_submitted', 1);
		$data['count'] = $count_query->get('financial_report')->row()->total_count;

		$data['data'] = $this->read_db->distinct()->select('financial_report.fk_office_id, office.office_code')
			->select("get_cluster_name.office_code AS cluster")
			->select("get_cohort_name.office_code AS cohort")
			->select("get_country_name.office_code AS country")
			->select("get_region_name.office_code AS region")

			->join('office', 'financial_report.fk_office_id = office.office_id')
			->join('context_center', 'office.office_id = context_center.fk_office_id', 'left')
			->join('context_cluster', 'context_cluster.context_cluster_id = context_center.fk_context_cluster_id', 'left')
			->join('context_cohort', 'context_cohort.context_cohort_id = context_cluster.fk_context_cohort_id', 'left')
			->join('context_country', 'context_country.context_country_id = context_cohort.fk_context_country_id', 'left')
			->join('context_region', 'context_region.context_region_id = context_country.fk_context_region_id', 'left')

			->join('office as get_cluster_name', 'context_cluster.fk_office_id = get_cluster_name.office_id', 'left')
			->join('office as get_cohort_name', 'context_cohort.fk_office_id = get_cohort_name.office_id', 'left')
			->join('office as get_country_name', 'context_country.fk_office_id = get_country_name.office_id', 'left')
			->join('office as get_region_name', 'context_region.fk_office_id = get_region_name.office_id', 'left')

			->where("financial_report.financial_report_month BETWEEN '$start_date' AND '$end_date'")

			->group_start()
			->or_like('office.office_code', $search_value, 'both')
			->or_like('get_cluster_name.office_code', $search_value, 'both')
			->or_like('get_cohort_name.office_code', $search_value, 'both')
			->or_like('get_country_name.office_code', $search_value, 'both')
			->or_like('get_region_name.office_code', $search_value, 'both')
			->group_end()

			->order_by($order_col, $order_dir);

		if(!$this->session->system_admin){
			$data['data']->where_in('financial_report.fk_office_id', $session_office_ids);
		} else{
			$data['data']->where(array('office.fk_account_system_id' => $this->selected_account_system_id));
		}

		$data['data']->where('financial_report_is_submitted', 1);

		$data['data'] = $data['data']->get('financial_report', $limit, $offset)->result_array();

		return $data;
	}
    */

    function getMonthFundBalanceByFCPId($start_date, $end_date, $fcp_id):array{
		$data = $this->read_db->query("SELECT 
					  office_code,
					  closing_fund_balance_data
					FROM 
					  financial_report fr
					JOIN office o ON fr.fk_office_id=o.office_id
					
					WHERE fr.financial_report_month BETWEEN '$start_date' AND '$end_date'
					AND fr.financial_report_is_submitted = 1
					AND fr.fk_office_id = '$fcp_id'");

		$results =  $data->get()->getRowArray();
		$json_closing_balances = json_decode($results['closing_fund_balance_data'], true);
		$closing_balances = [];
		foreach($json_closing_balances as $id => $balance){
			$incomeData = $this->getIncomeAccountData($id);
			$closing_balances[$incomeData['income_account_code']] = $balance;
		}
		return $closing_balances;

	}

    function getIncomeAccountData($id): array
	{
        $builder = $this->read_db->table('income_account');
        $builder->select('income_account_id, income_account_name, income_account_code');
        $builder->where('income_account_id', $id);
        $data = $builder->get()->getRowArray();

		return $data;
	}

    function getMonthExpensesByFCPId($start_date, $end_date, $fcp_id):array{

		$expenses = $this->read_db->query("SELECT 
					  office_code,
					  ia.income_account_id,
					  ia.income_account_code,
					  ia.income_account_name,
					  ea.expense_account_id,
					  ea.expense_account_code,
					  ea.expense_account_name,
					  SUM(jt.month_expense) AS month_expense
				
					FROM 
					  financial_report fr
					JOIN 
					  JSON_TABLE(
					  fr.closing_expense_report_data,
					  '$[*].expense_report[*]' COLUMNS(
						 expense_account_id INT PATH '$.expense_account_id',
						 month_expense DECIMAL(50,2) PATH '$.month_expense'
					)
					) as jt
					JOIN office o ON fr.fk_office_id=o.office_id
					JOIN expense_account ea ON jt.expense_account_id=ea.expense_account_id
					JOIN income_account ia ON ea.fk_income_account_id=ia.income_account_id
					WHERE fr.financial_report_month BETWEEN '$start_date' AND '$end_date'
					AND fr.fk_office_id = '$fcp_id' GROUP BY ea.expense_account_id");

		$results =  $expenses->getResultArray();
		$expense_array = [];
		foreach($results as $expense){
			$expense_array[$expense['income_account_code']][$expense['expense_account_code']] = $expense['month_expense'];
		}



		return $expense_array;

		//return json data for dataTables
	}

    function getMonthIncomeByFCPId($start_date, $end_date, $fcp_id):array{

		$incomes = $this->read_db->query("SELECT 
				  office_code,
				  ia.income_account_id,
				  ia.income_account_code,
				  ia.income_account_name,
				  SUM(jt.month_income) AS month_income
				FROM 
				  financial_report fr
				JOIN 
				  JSON_TABLE(
				  fr.month_fund_balance_report_data,
				  '$[*]' COLUMNS(
					 account_id INT PATH '$.account_id',
					 month_income DECIMAL(50,2) PATH '$.month_income'
				)
				) as jt
				JOIN office o ON fr.fk_office_id=o.office_id
				JOIN income_account ia ON jt.account_id=ia.income_account_id
				WHERE fr.financial_report_month BETWEEN '$start_date' AND '$end_date'
				AND fr.fk_office_id = '$fcp_id' GROUP BY ia.income_account_id");

		$results =  $incomes->getResultArray();
		$income_array = [];
		foreach($results as $income){
			$income_array[$income['income_account_code']] = $income['month_income'];
		}

		return $income_array;

	}

	function civColumns(){

		$this->setSelectedAccountSystemId();
		$civ_income_accounts = $this->getCivIncomeAccounts();

		$accounts = $this->formatDatatableHeaders($civ_income_accounts);

		$result['accounts'] = $accounts;
		$result['columns'] = $accounts;

		echo json_encode($result);
	}

    function getCivIncomeAccounts(){
		$civ_accounts = $this->accountSystemIncomeAccounts($this->selected_account_system_id, false);
		return $civ_accounts;
	}

	function civShowList(){
		$this->setSelectedAccountSystemId();
		$balances = $this->getCivBalances();
		$draw =intval($this->request->getPost('draw'));
		$count_balances = $this->countCivBalances();

		$data = $balances['data'];

		$response = [
			"draw" => $draw,
			"recordsTotal" => $count_balances,
			"recordsFiltered" => $count_balances,
			"data" => $data
		];

		echo json_encode($response);
	}

    function getCivBalances(){

		$show_income_account_balance = false;
		$this->period = $this->request->getPost('date_range') != null ? $this->request->getPost('date_range') : date('Y-m-01');

		//$logged_offices = $this->user_offices_with_submitted_report_and_logged_balances($show_income_account_balance);
		// $office_ids = []; //array_keys($logged_offices);


		$result = [];//['cols' => [], 'data' => []];
		$header_cols = [];

		//if(empty($office_ids)){
		//return ['data' => $result, 'columns' => $header_cols];
		//}

		$this->setSelectedAccountSystemId();

		// Limiting records
		$start = intval($this->request->getPost('start'));
		$length = intval($this->request->getPost('length'));

		$order = $this->request->getPost('order');
		$col = '';
		$dir = 'desc';

		if(!empty($order)){
			$col = $order[0]['column'];
			$dir = $order[0]['dir'];
		}

		if( $col == '' || $col == 0){
			$col = 'office_id';
		}

		//  log_message('error', json_encode([__METHOD__ => $this->input->post('accounts')]));
		$account_system_income_accounts = $this->getCivIncomeAccounts();
		if (is_numeric($col) && $col > 0) {
			$col = array_values($account_system_income_accounts)[$col - 1];
		}

		$search = $this->request->getPost('search');
		$search_value = $search['value'] ?? '';

		$search_column = $this->request->getPost('accounts');

		$fund_balance_summary = $this->fund_balance_summary_report_library->loggedFundBalanceReport($this->period, $show_income_account_balance, $start, $length, $col, $dir, $search_value, $search_column);

		// if(!empty($fund_balance_summary) && isset($fund_balance_summary['office_ids'])){
		//   $this->office_ids = $fund_balance_summary['office_ids'];
		// }

		// log_message('error', json_encode($fund_balance_summary));

		$cnt = 0;

		foreach($fund_balance_summary['records'] as $office_code => $financial_report){
			foreach($financial_report as $financial_report_id => $balances){
				if(!is_array($balances)) continue;
				$result[$cnt]['office_code'] = '<a target="_blank" href="'.base_url().'financial_report/view/'.hash_id($financial_report_id,"encode").'">'.$office_code.'</a>';
				$header_cols['office_code'] = get_phrase('office_code');
				$innerCnt = 1;
				$sum = 0;
				foreach($account_system_income_accounts as $income_account_id => $income_account_code){
					$closing_balance = 0;
					if(array_key_exists($income_account_id, $balances)){
						$closing_balance = $balances[$income_account_id];
					}

					// if($search_column > 0 && $closing_balance == 0) {
					//   unset($result[$cnt]);
					//   continue;
					// }

					$sum += $closing_balance;
					$header_cols[$income_account_code] = $income_account_code;
					$result[$cnt][$income_account_code] = number_format($closing_balance,2);
					$innerCnt++;
				}
				$result[$cnt]['totals'] = number_format($sum,2);
				$header_cols['totals'] = 'Totals';
			}
			$cnt++;
		}

		// log_message('error', json_encode($result));

		return ['data' => $result, 'columns' => $header_cols];
	}

    function countCivBalances(){
		$office_ids = $this->userOfficesWithSubmittedReportAndLoggedBalances(false);
		return  count($office_ids);
	}


}

/*
 * class 	Fund_balance_summary_report extends MY_Controller
{
	private $civ_accounts = [];
	private $income_accounts = [];
	private $cols = [];
	private $period = null;

	private $selected_account_system_id = 0;

	function __construct(){
		parent::__construct();
		$this->load->library('fund_balance_summary_report_library');

		$this->set_selected_account_system_id();
	}

	function index(){}

	function result($id = 0){

		$result = [];

		if($this->action == 'list'){
			$result['columns'] = $this->get_fund_columns();
			$result['accounting_system'] = $this->get_accounting_systems();
			$result['month'] = date('Y-m-01', strtotime('last day of previous month'));
			$result['has_details_table'] = false;
			$result['has_details_listing'] = false;
			$result['is_multi_row'] = false;
			$result['show_add_button'] = false;
		}else{
			$result = parent::result($id);
		}

		return $result;
	}

	function get_accounting_systems(){
		$accounting_systems = [];

		$this->read_db->select(array('account_system_id','account_system_name','office_name'));
		$this->read_db->where(array('office.fk_context_definition_id' => 4));
		$this->read_db->join('office','office.fk_account_system_id=account_system.account_system_id');
		$accounting_systems_obj = $this->read_db->get('account_system');

		if($accounting_systems_obj->num_rows() > 0){
			$accounting_systems_raw = $accounting_systems_obj->result_array();

			foreach($accounting_systems_raw as $office){
				$accounting_systems[$office['account_system_id']] = $office['office_name'];
			}
		}

		return $accounting_systems;
	}




	function get_fund_income_accounts(){
		return $this->account_system_income_accounts($this->selected_account_system_id, true);
	}

	function account_system_income_accounts($selected_account_system_id, $show_income_account_balance){
		$income_accounts = [];

		$columns = array('income_account_id as account_id','income_account_code as account_code');

		if(!$show_income_account_balance){
			$columns = array('project_id as account_id','project_code as account_code');
		}

		$this->read_db->select($columns);
		if(!$this->session->system_admin){
			$this->read_db->where(array('fk_account_system_id' => $this->selected_account_system_id));
		}

		if(!$show_income_account_balance){
			$this->read_db->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
			$this->read_db->join('project','project.project_id=project_income_account.fk_project_id');
			$this->read_db->where('project_end_date IS NOT NULL AND project_end_date <> "0000-00-00"');
		}

		$account_id = $this->input->post('accounts');

		// log_message('error', json_encode($account_id));

		if($account_id > 0){
			if($show_income_account_balance){
				$this->read_db->where(array('income_account_id' => $account_id));
			}else{
				$this->read_db->where(array('project_id' => $account_id));
			}
		}

		$obj = $this->read_db->get('income_account');

		if($obj->num_rows() > 0){
			$income_accounts_raw = $obj->result_array();

			$ids = array_column($income_accounts_raw,'account_id');
			$codes = array_column($income_accounts_raw,'account_code');

			$income_accounts = array_combine($ids, $codes);
		}

		return $income_accounts;
	}





	function set_selected_account_system_id(){
		$this->selected_account_system_id = $this->session->user_account_system_id;

		if($this->input->post('account_system_id') > 0 && $this->session->system_admin){
			$this->selected_account_system_id = $this->input->post('account_system_id');
		}
	}







	function get_cash_balances(){
		$this->load->model('office_cash_model');

		$this->period = $this->input->post('date_range') != null ? $this->input->post('date_range') : date('Y-m-01');
		$result = [];
		$header_cols = [];
		$this->set_selected_account_system_id();

		// Limiting records
		$start = intval($this->input->post('start'));
		$length = intval($this->input->post('length'));

		$order = $this->input->post('order');
		$col = '';
		$dir = 'desc';

		if(!empty($order)){
			$col = $order[0]['column'];
			$dir = $order[0]['dir'];
		}

		if( $col == '' || $col == 0){
			$col = 'office_id';
		}


		//  $account_system_cash_accounts = $this->office_cash_model->get_active_office_cash($this->selected_account_system_id);
		$cash_balance_headers = ["opening",'income','expense','closing'];

		if (is_numeric($col) && $col > 0) {
			$col = array_values($account_system_income_accounts)[$col - 1];
		}

		$search = $this->input->post('search');
		$search_value = isset($search['value']) ? $search['value'] : '';

		$search_column = $this->input->post('accounts');

		//  log_message('error', json_encode($search_column));

		$cash_balance_summary = $this->fund_balance_summary_report_model->logged_cash_balance_report($this->period, $start, $length, $col, $dir, $search_value, $search_column);

		//  log_message('error', json_encode($cash_balance_summary));

		$cnt = 0;

		foreach($cash_balance_summary['records'] as $office_code => $financial_report){
			foreach($financial_report as $financial_report_id => $balances){

				if(!is_array($balances) || !array_key_exists('cash_breakdown', $balances)) continue;

				$cash_at_hand_balances = $balances['cash_breakdown']['cash_at_hand'];

				//  log_message('error', json_encode(['search_column' => $search_column, 'cash_at_hand_balances' => $cash_at_hand_balances]));

				//  if($search_column > 0 &&  !array_key_exists($search_column, $cash_at_hand_balances)) continue;


				$result[$cnt]['office_code'] = '<a target="_blank" href="'.base_url().'financial_report/view/'.hash_id($financial_report_id,"encode").'">'.$office_code.'</a>';
				$header_cols['office_code'] = get_phrase('office_code');
				$innerCnt = 1;
				//  $sum = 0;

				foreach($cash_balance_headers as $cash_balance_header){
					// $result[$cnt][$cash_balance_header]
					$amount = 0;
					if(!empty($cash_at_hand_balances)){
						if($search_column > 0){
							$amount = isset($cash_at_hand_balances[$search_column][$cash_balance_header]) ? $cash_at_hand_balances[$search_column][$cash_balance_header] : 0;
						}else{
							$amount = array_sum(array_column($cash_at_hand_balances,$cash_balance_header));
						}

					}

					$header_cols[$cash_balance_header] = $cash_balance_header;
					$result[$cnt][$cash_balance_header] = number_format($amount,2);
					$innerCnt++;
				}

			}
			$cnt++;
		}

		$rst = ['data' => $result, 'columns' => $header_cols];

		//  log_message('error', json_encode($rst));
		//  $rst['data'] = [];
		return $rst;
	}





	function get_fund_columns(){
		$this->set_selected_account_system_id();
		$fund_income_accounts = $this->get_fund_income_accounts();

		$accounts = $this->format_datatable_headers($fund_income_accounts);

		return $accounts;
	}

	function get_cash_account_columns(){
		$this->set_selected_account_system_id();
		$this->load->model('office_cash_model');

		$office_cash_accounts = $this->office_cash_model->get_active_office_cash($this->selected_account_system_id);

		$office_cash_ids = array_column($office_cash_accounts, 'office_cash_id');
		$office_cash_names = array_column($office_cash_accounts, 'office_cash_name');

		$accounts = array_combine($office_cash_ids, $office_cash_names);

		$columns = $this->format_datatable_headers($accounts, false);

		return $columns;
	}

	function get_cash_columns(){
		$this->set_selected_account_system_id();

		$cash_balance_headers = ["opening",'income','expense','closing'];

		$columns = $this->format_datatable_headers($cash_balance_headers, false);

		return $columns;
	}

	function fund_columns($report_category){

		$result = [];

		switch($report_category){
			case "month_cash_balance":{
				$result['columns'] = $this->get_cash_columns();
				$result['accounts'] = $this->get_cash_account_columns();
				// log_message('error', json_encode($result['accounts']));
				break;
			}
			case "month_expense":{
				$result['columns'] = $this->get_month_expense_report_datatable_columns();
				break;
			}
			case "month_income":{
				$result['columns'] = $this->get_month_income_report_datatable_columns();
				//$result['accounts'] = $result['columns'];
				break;
			}

			case "fund_balance_trial":{
				$result['columns'] = $this->get_fund_balance_datatable_columns();
				$result['accounts'] = $this->get_fund_columns();
				break;
			}

			default:{
				$result['columns'] = $this->get_fund_columns();
				$result['accounts'] = $this->get_fund_columns();
				// log_message('error', json_encode($result['accounts']));
			}
		}

		echo json_encode($result);


	}

	function build_report_columns($name){
		if($name == 'month_report'){
			$result['columns'] = $this->get_cash_columns();
			$result['accounts'] = $this->get_cash_account_columns();
		}
		echo json_encode($result);
	}

	static function get_menu_list(){}



	function get_expense_account_data($key){
		$data = $this->read_db
			->select('expense_account_id, expense_account_name, expense_account_code, fk_income_account_id, income_account_code')
			->join('income_account', 'income_account_id = fk_income_account_id')
			->where('expense_account_id', $key)->get('expense_account')->row_array();

		return $data;

	}

	function get_expenses_accounts($income_account_id){
		return $this->read_db->select('expense_account_code, expense_account_name')
			->where('fk_income_account_id', $income_account_id)
			->get('expense_account')->result_array();
	}





	//$this->selected_account_system_id

	function get_month_expense_report_datatable_columns(): array
	{
		$this->set_selected_account_system_id();

		$fund_income_accounts = $this->read_db->select('income_account_code, income_account_name, income_account_id ')
			->where('fk_account_system_id', $this->selected_account_system_id)
			->get('income_account')->result_array();

		$user_context_definition_level = $this->session->context_definition['context_definition_level'];
		$columns_to_append = $this->determine_columns_by_context($user_context_definition_level);

		// Initialize columns with the first column (Office Code)
		$columns[] = [
			'data' => "office_code",
			'title' => get_phrase('office_code'),
			'visible' => true
		];


// Append additional columns based on the context
		foreach ($columns_to_append as $column) {
			$columns[] = [
				'data' => $column,
				'title' => ucfirst($column),
				'visible' => true
			];
		}

		$count = 1;

		// Add parent and child columns in the right order
		foreach ($fund_income_accounts as $income_account) {

			$income_account_code = $income_account['income_account_code'];
			$income_account_name = $income_account['income_account_name'];
			$income_account_id = $income_account['income_account_id'];

			// Get and add the child columns for each parent
			$expense_accounts = $this->get_expenses_accounts($income_account_id);

			foreach ($expense_accounts as $expense_account) {


				$income_account_label = $income_account_code . ' - ' . $expense_account['expense_account_code'];
				$name = $expense_account['expense_account_name'];
				$columns[] = [
					'data' => $income_account_label,
					'title' => $income_account_label,
					'id' => $count,
					'name' => $name,
					'parent' => $income_account_code,
					'visible' => false, // Initially hidden child columns
					'is_parent' => false,
					'className' => 'text-right'
				];
				$count++;
			}

			$columns[] = [
				'is_parent' => true,
				'name' => $income_account_name,
				'data' => $income_account_code,
				'title' => $income_account_code,
				'id' => $income_account_code,
				'className' => 'text-right'
			];

		}

		$columns[] = [
			'data' => "totals",
			'title' => get_phrase('totals'),
			'className' => 'text-right',
			'visible' => true
		];

		return $columns;
	}

	function get_month_income_report_datatable_columns(): array
	{
		$this->set_selected_account_system_id();
		$fund_income_accounts = $this->read_db->select('income_account_code, income_account_name, income_account_id ')
			->where('fk_account_system_id', $this->selected_account_system_id)
			->get('income_account')->result_array();


		// Initialize columns with the first column (Office Code)
		$user_context_definition_level = $this->session->context_definition['context_definition_level'];
		$columns_to_append = $this->determine_columns_by_context($user_context_definition_level);

		// Initialize columns with the first column (Office Code)
		$columns[] = [
			'data' => "office_code",
			'title' => get_phrase('office_code'),
			'visible' => true
		];

// Append additional columns based on the context
		foreach ($columns_to_append as $column) {
			$columns[] = [
				'data' => $column,
				'title' => ucfirst($column),
				'visible' => true,
				'className' => 'text-right'
			];
		}

		$count = 1;

		// Add parent and child columns in the right order
		foreach ($fund_income_accounts as $income_account) {
			$income_account_code = $income_account['income_account_code'];
			$income_account_name = $income_account['income_account_name'];
			$income_account_id = $income_account['income_account_id'];

			$columns[] = [
				'is_parent' => true,
				'name' => $income_account_name,
				'data' => $income_account_code,
				'title' => $income_account_code,
				'id' => $income_account_code,
				'className' => 'text-right',
			];
		}

		$columns[] = [
			'data' => "totals",
			'title' => get_phrase('totals'),
			'className' => 'text-right',
			'visible' => true
		];

		return $columns;
	}

	function get_fund_balance_datatable_columns(): array
	{
		$this->set_selected_account_system_id();
		$fund_income_accounts = $this->read_db->select('income_account_id, income_account_code, income_account_name, income_account_id ')
			->where('fk_account_system_id', $this->selected_account_system_id)
			->get('income_account')->result_array();


		// Initialize columns with the first column (Office Code)
		$user_context_definition_level = $this->session->context_definition['context_definition_level'];
		$columns_to_append = $this->determine_columns_by_context($user_context_definition_level);

		// Initialize columns with the first column (Office Code)
		$columns[] = [
			'data' => "office_code",
			'title' => get_phrase('office_code'),
			'visible' => true
		];


// Append additional columns based on the context
		foreach ($columns_to_append as $column) {
			$columns[] = [
				'data' => $column,
				'title' => ucfirst($column),
				'visible' => true
			];
		}

		$count = 1;

		// Add parent and child columns in the right order
		foreach ($fund_income_accounts as $income_account) {
			$income_account_code = $income_account['income_account_code'];
			$income_account_name = $income_account['income_account_name'];
			$income_account_id = $income_account['income_account_id'];

			$columns[] = [
				'is_parent' => true,
				'name' => $income_account_name,
				'data' => $income_account_code,
				'title' => $income_account_code,
				'id' => $income_account_id,
				'className' => 'text-right'
			];
		}

		$columns[] = [
			'data' => "totals",
			'title' => get_phrase('totals'),
			'className' => 'text-right',
			'visible' => true
		];

		return $columns;


	}









}

 * 
 * 
 */

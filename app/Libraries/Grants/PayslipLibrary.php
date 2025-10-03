<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\PayslipModel;
use App\Libraries\System\Types\EarningsConfig;
class PayslipLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $payslipModel;
    public $lookup_tables_with_null_values = ['pay_history'];

    function __construct()
    {
        parent::__construct();

        $this->payslipModel = new PayslipModel();

        $this->table = 'payslip';
    }

    function detailTables(): array
    {
        return [
            'payroll_deduction'
        ];
    }

    function detailListTableVisibleColumns(): array
    {
        return [
            'payslip_track_number',
            'payslip_name',
            'payslip_basic_pay',
            'payslip_total_earning',
            'payslip_taxable_pay',
            'payslip_total_deduction',
            'payslip_total_liability',
            'payslip_net_pay',
            'payslip_created_date',
        ];
    }

    function singleFormAddVisibleColumns(): array
    {
        return [
            'payroll_name',
            'user_name',
            'pay_history_name'
        ];
    }

    function lookupValues(): array
    {
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $lookUpValues = parent::lookupValues();
        $officeIds = array_column($this->session->hierarchy_offices, 'office_id');

        $lookUpValues['office'] = [];
        $officeBuilder = $this->read_db->table('office');
        $officeBuilder->select(['office_id', 'office_name']);

        if (!$this->session->system_admin) {
            $officeBuilder->whereIn('office_id', $officeIds);
        }

        $officeObj = $officeBuilder->get();

        if ($officeObj->getNumRows() > 0) {
            $lookUpValues['office'] = $officeObj->getResultArray();
        }

        $lookUpValues['user'] = [];

        $userBuilder = $this->read_db->table('user');

        $userBuilder->select(['user_id', 'CONCAT(user_firstname, " ", user_lastname, "[",user_email,"]") as user_name']);
        $userBuilder->join('context_center_user', 'context_center_user.fk_user_id=user.user_id');
        $userBuilder->join('context_center', 'context_center.context_center_id=context_center_user.fk_context_center_id');

        if (!$this->session->system_admin) {
            $userBuilder->whereIn('context_center.fk_office_id', $officeIds);
        }

        $userBuilder->where('user_is_active', '1');
        $userBuilder->whereIn('user.fk_status_id', $statusLibrary->getMaxApprovalStatusId('user'));
        $userObj = $userBuilder->get();

        if ($userObj->getNumRows() > 0) {
            $lookUpValues['user'] = $userObj->getResultArray();
        }


        return $lookUpValues;
    }

    function transactionValidateDuplicatesColumns(): array
    {
        return ['fk_user_id', 'fk_payroll_id'];
    }

    function formatColumnsValuesDependancyData(array $payslips): array
    {
        $payslipBuilder = $this->read_db->table('payslip');

        $payslipBuilder->select(['payslip_id']);
        $payslipBuilder->select(['payslip_basic_pay']);
        $payslipBuilder->selectSum('payroll_deduction_amount');
        $payslipBuilder->join('payroll_deduction', 'payroll_deduction.fk_payslip_id=payslip.payslip_id');
        $payslipBuilder->whereIn('payslip_id', array_column($payslips, 'payslip_id'));
        $payslipBuilder->groupBy('payslip_id');
        $payslipDataObj = $payslipBuilder->get();

        $payslipDataTotalDeduction = [];
        $payslipDataGrossPay = [];

        if ($payslipDataObj->getNumRows() > 0) {
            $payslipDataRaw = $payslipDataObj->getResultArray();

            $payslip_ids = array_column($payslipDataRaw, 'payslip_id');
            $payroll_deduction_amounts = array_column($payslipDataRaw, 'payroll_deduction_amount');
            // $payslip_basic_pays = array_column($payslipDataRaw,'payslip_basic_pay');

            $payslipDataTotalDeduction = array_combine($payslip_ids, $payroll_deduction_amounts);
            $payslipDataGrossPay[$payslipDataRaw[0]['payslip_id']] = $payslipDataRaw[0]['payslip_basic_pay'];
        }

        return compact('payslipDataTotalDeduction', 'payslipDataGrossPay');
    }

    function formatColumnsValues(string $columnName, mixed $columnValue, array $rowArray, array $dependancyData = []): mixed
    {
        if ($columnName == 'payslip_total_deduction') {
            $columnValue = number_format(($dependancyData['payslipDataTotalDeduction'][$rowArray['payslip_id'] ?? 0] ?? 0), 2);
        }

        return $columnValue;
    }

    function getOfficePayslipDetails($payslipId)
    {
        $officeBuilder = $this->read_db->table('office');

        $officeBuilder->select(
            'office_id, 
            office_name, 
            office_postal_address, 
            office_email, office_phone, 
            country_currency_code as office_currency_code, 
            country_currency_name as office_currency_name'
        );

        $officeBuilder->join('payroll', 'payroll.fk_office_id = office.office_id');
        $officeBuilder->join('payslip', 'payslip.fk_payroll_id = payroll.payroll_id');
        $officeBuilder->join('country_currency', 'country_currency.country_currency_id = office.fk_country_currency_id');
        $officeBuilder->where('payslip.payslip_id', $payslipId);
        $query = $officeBuilder->get();
        $officeData = $query->getRowArray();

        return $officeData;
    }

    private function _configurationBuilder(&$earningBuilder, ?EarningsConfig $configurations = null)
    {
        // Filter based on configurations
        if ($configurations != null) {
            if (!$configurations->show_accrued_earnings) {
                $earningBuilder->where('earning_category_is_accrued', '0'); // Only non accrued earnings 
            } else {
                $earningBuilder->where('earning_category_is_accrued', '1'); // Only accrued earnings
            }

            // Based on taxable earnings
            if (!$configurations->show_taxable_earnings) {
                $earningBuilder->where('earning_category_is_taxable', '0'); // Only non
            } else {
                $earningBuilder->where('earning_category_is_taxable', '1'); // Only taxable
            }

            // Based on basic earnings
            if (!$configurations->show_basic_earnings) {
                $earningBuilder->where('earning_category_is_basic', '0'); // Only non basic
            } else {
                $earningBuilder->where('earning_category_is_basic', '1'); // Only basic
            }

            // Based on recurring earnings
            if (!$configurations->show_recurring_earnings) {
                $earningBuilder->where('earning_category_is_recurring', '0'); // Only non recurring
            } else {
                $earningBuilder->where('earning_category_is_recurring', '1'); // Only recurring
            }
        }
    }

    private function _groupedEarnings($earnings)
    {
        $earningsPerCategory = [];

        foreach ($earnings as $earning) {

            if ($earning['earning_category_is_accrued'] == '1') {
                $earningsPerCategory['accrued_earnings'][$earning['id']] = $earning;
            } elseif ($earning['earning_category_is_accrued'] == '0') {
                $earningsPerCategory['payable_earnings'][$earning['id']] = $earning;
            }

            if ($earning['earning_category_is_taxable'] == '1') {
                $earningsPerCategory['taxable_earnings'][$earning['id']] = $earning;
            } elseif ($earning['earning_category_is_taxable'] == '0') {
                $earningsPerCategory['non_taxable_earnings'][$earning['id']] = $earning;
            }

            if ($earning['earning_category_is_basic'] == '1') {
                $earningsPerCategory['basic_earnings'][$earning['id']] = $earning;
            } elseif ($earning['earning_category_is_basic'] == '0') {
                $earningsPerCategory['non_basic_earnings'][$earning['id']] = $earning;
            }

            if ($earning['earning_category_is_recurring'] == '1') {
                $earningsPerCategory['recurring_earnings'][$earning['id']] = $earning;
            } elseif ($earning['earning_category_is_recurring'] == '0') {
                $earningsPerCategory['non_recurring_earnings'][$earning['id']] = $earning;
            }
        }

        return $earningsPerCategory;
    }

    private function getPayslipEarnings(int $payslipId, ?EarningsConfig $configurations = null)
    {

        $earningBuilder = $this->read_db->table('earning');
        $earningBuilder->select(['earning_category_id as id', 'earning_id as record_id', 'earning_category_name as name', 'earning_amount as amount', 'earning_category_is_accrued', 'earning_category_is_taxable', 'earning_category_is_basic', 'earning_category_is_recurring']);
        $earningBuilder->join('pay_history', 'pay_history.pay_history_id=earning.fk_pay_history_id');
        $earningBuilder->join('earning_category', 'earning_category.earning_category_id=earning.fk_earning_category_id');
        $earningBuilder->join('payslip', 'payslip.fk_pay_history_id=pay_history.pay_history_id');
        $earningBuilder->where('payslip_id', $payslipId);

        $this->_configurationBuilder($earningBuilder, $configurations);

        $earningObj = $earningBuilder->get();

        if ($earningObj->getNumRows() > 0) {
            $earnings = $earningObj->getResultArray();

            return $this->_groupedEarnings($earnings);
        }

        return null;
    }

    private function getPayslipDeductions($payslipId)
    {

        $deductionBuilder = $this->read_db->table('payslip');

        $deductionBuilder->select(['payroll_deduction_category_id as id', 'payroll_deduction_id as record_id', 'payroll_deduction_category_name as name', 'payroll_deduction_amount as amount']);
        $deductionBuilder->join('payroll_deduction', 'payroll_deduction.fk_payslip_id=payslip.payslip_id');
        $deductionBuilder->join('payroll_deduction_category', 'payroll_deduction_category.payroll_deduction_category_id=payroll_deduction.fk_payroll_deduction_category_id');
        $deductionBuilder->where('fk_payslip_id', $payslipId);
        $deductionObj = $deductionBuilder->get();

        if ($deductionObj->getNumRows() > 0) {
            return $deductionObj->getResultArray();
        }

        return null;
    }

    private function getDeductionOptions($accountSystemId)
    {

        $deductionBuilder = $this->read_db->table('payroll_deduction_category');
        $deductionBuilder->select(['payroll_deduction_category_id as id', 'payroll_deduction_category_name as name']);
        $deductionBuilder->where('fk_account_system_id', $accountSystemId);
        $deductionObj = $deductionBuilder->get();

        if ($deductionObj->getNumRows() > 0) {
            return $deductionObj->getResultArray();
        }
        return null;
    }

    private function getEarningOptions($accountSystemId)
    {

        $earningBuilder = $this->read_db->table('earning_category');
        $earningBuilder->select(['earning_category_id as id', 'earning_category_name as name', 'earning_category_is_basic', 'earning_category_is_taxable', 'earning_category_is_recurring', 'earning_category_is_accrued']);
        $earningBuilder->where('fk_account_system_id', $accountSystemId);
        $earningObj = $earningBuilder->get();

        if ($earningObj->getNumRows() > 0) {
            $rawEarningCategories = $earningObj->getResultArray();

            return $this->_groupedEarnings($rawEarningCategories);
        }

        return null;
    }

    private function payslipQuery($payslipId)
    {
        $payslipBuilder = $this->read_db->table('payslip');

        $payslipBuilder->select([
            'payslip_id',
            'payslip_pay_date',
            'payroll_period',
            'CONCAT(user.user_firstname, " ", user.user_lastname) as user_fullname',
            'designation_name as job_title',
            'context_center_user_employment_number as employment_number',
            'department.department_name as department'
        ]);
        $payslipBuilder->join('payroll', 'payroll.payroll_id=payslip.fk_payroll_id');
        $payslipBuilder->join('pay_history', 'pay_history.pay_history_id=payslip.fk_pay_history_id');
        $payslipBuilder->join('user', 'user.user_id=payslip.fk_user_id');
        $payslipBuilder->join('context_center_user', 'context_center_user.fk_user_id=user.user_id'); // Can be more than one context center
        $payslipBuilder->join('designation', 'designation.designation_id=context_center_user.fk_designation_id');
        $payslipBuilder->join('department_user', 'department_user.fk_user_id=user.user_id');
        $payslipBuilder->join('department', 'department.department_id=department_user.fk_department_id'); // Can be more than one department
        $payslipBuilder->where('payslip_id', $payslipId);
        $payslipBuilder->where('context_center_user_primary', '1');
        $payslipObj = $payslipBuilder->get();

        $payslipData = [];

        if ($payslipObj->getNumRows() > 0) {
            $payslipData = $payslipObj->getRowArray();
        }
        return $payslipData;
    }

    private function getPayslipInfo($payslipId)
    {

        $payslipDataResult = $this->payslipQuery($payslipId);

        $payslipData = [];

        if (!empty($payslipDataResult)) {
            $payslipData['payslip_pay_date'] = $payslipDataResult['payslip_pay_date'] ? date('jS M Y', strtotime($payslipDataResult['payslip_pay_date'])) : '.............';
            $payslipData['payslip_period_start_date'] = date('jS M Y', strtotime($payslipDataResult['payroll_period'] . '-01'));
            $payslipData['payslip_period_end_date'] = date('jS M Y', strtotime('last day of', strtotime($payslipDataResult['payroll_period'])));
        }

        return $payslipData;
    }

    private function getPayslipUser($payslipId)
    {
        [
            'user_fullname' => $user_fullname,
            'job_title' => $job_title,
            'employment_number' => $employment_number,
            'department' => $department
        ] = $this->payslipQuery($payslipId);

        $employment_number = $employment_number ?? '.............';
        $user_locale = $this->session->locale ?? 'en_US';

        return compact('user_fullname', 'job_title', 'employment_number', 'department', 'user_locale');
    }

    private function getPayslipAccountSystemId($payslipId)
    {
        $payslipBuilder = $this->read_db->table('payslip');

        $payslipBuilder->select(['account_system_id']);
        $payslipBuilder->join('payroll', 'payroll.payroll_id=payslip.fk_payroll_id');
        $payslipBuilder->join('office', 'office.office_id=payroll.fk_office_id');
        $payslipBuilder->join('account_system', 'account_system.account_system_id=office.fk_account_system_id');
        $payslipBuilder->where('payslip.payslip_id', $payslipId);
        $payslipObj = $payslipBuilder->get();

        $accountSystemId = null;

        if ($payslipObj->getNumRows() > 0) {
            $payslipData = $payslipObj->getRowArray();
            $accountSystemId = $payslipData['account_system_id'];
        }

        return $accountSystemId;
    }

    public function payslipOptions($payslipId){
        $accountSystemId = $this->getPayslipAccountSystemId($payslipId);

        $earningOptions = $this->getEarningOptions($accountSystemId);
        $payable_earning_options = array_values($earningOptions['payable_earnings'] ?? []);
        $accrued_earning_options = array_values($earningOptions['accrued_earnings'] ?? []);

        $deductionOptions = $this->getDeductionOptions($accountSystemId);

        return [
                'earnings' => [
                    'payable_earning_options' => $payable_earning_options,
                    'accrued_earning_options' => $accrued_earning_options
                ],
                'deductions' => $deductionOptions
            ];
    }

    private function earningsData($payslipId){
        
        $payslipEarnings = $this->getPayslipEarnings($payslipId);
        $payable_earnings = array_values($payslipEarnings['payable_earnings'] ?? []);
        $accrued_earnings = array_values($payslipEarnings['accrued_earnings'] ?? []); 
        
        return [
                'payable_earnings' => $payable_earnings,
                'accrued_earnings' => $accrued_earnings,
        ];
    }

    private function getPayslipPayroll($payslipId){
        $payrollBuilder = $this->read_db->table('payroll');

        $payrollBuilder->select('payroll.*');
        $payrollBuilder->where('payslip_id', $payslipId);
        $payrollBuilder->join('payslip','payslip.fk_payroll_id=payroll.payroll_id');
        $payrollObj = $payrollBuilder->get();

        $payroll = [];

        if($payrollObj->getNumRows() > 0){
            $payroll = $payrollObj->getRowArray();
        }

        return $payroll;
    }

    function checkIfStatusIsInitial($statusId){
        $statusBuilder = $this->read_db->table('status');

        $statusBuilder->select('status_id,status_approval_sequence,status_approval_direction,status_is_requiring_approver_action');
        $statusBuilder->where('status_id', $statusId);
        $statusBuilder->where('status_approval_sequence', '1');
        $countStatus = $statusBuilder->countAllResults();

        return $countStatus == 1 ? true : false;
    }

    public function getPayslipDetails($payslipId)
    {

        $userLibrary = new \App\Libraries\Core\UserLibrary();

        $payslipOptions = $this->payslipOptions($payslipId);
        $earnings = $this->earningsData($payslipId);
        $deductions = $this->getPayslipDeductions($payslipId);
        $payrollInfo = $this->getPayslipPayroll($payslipId);

        $currentPayrollStatusId = $payrollInfo['fk_status_id'];
        $statusIsInitial = $this->checkIfStatusIsInitial($currentPayrollStatusId);

        $payslip = [
            ...$this->getPayslipInfo($payslipId),
            'user' => $this->getPayslipUser($payslipId),
            'earnings' => $earnings,
            'deductions' => $deductions,
            'options' => $payslipOptions,
            'permission' => ($userLibrary
                                ->checkRoleHasPermissions('payslip', 'update') || 
                            $userLibrary
                                ->checkRoleHasPermissions('payslip', 'delete')) &&
                                $statusIsInitial ? 
                                'canUpdate' : 
                                'canRead',
        ];

        return $payslip;
    }

public function getPayslipPayHistory($payslipId){
        $payHistorybuilder = $this->read_db->table('pay_history');

        $payHistorybuilder->where('payslip_id', $payslipId);
        $payHistorybuilder->join('payslip', 'payslip.fk_pay_history_id=pay_history.pay_history_id');
        $payHistoryObj = $payHistorybuilder->get();

        $payHistory = [];

        if ($payHistoryObj->getNumRows() > 0) {
            $payHistory = $payHistoryObj->getRowArray();
        }

        return $payHistory;
    }

    function showListEditAction(array $record, array $dependancyData = []): bool {
        return false;
    }
}

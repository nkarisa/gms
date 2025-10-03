<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\PayrollModel;
use App\Libraries\System\Types\PostData;
class PayrollLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $payrollModel;
    public $lookup_tables_with_null_values = ['voucher'];

    function __construct()
    {
        parent::__construct();

        $this->payrollModel = new PayrollModel();

        $this->table = 'payroll';
    }

    function detailTables(): array
    {
        return ['payslip'];
    }

    private function getActiveTransactingOfficesWithPayHistory(){
        $officeBuilder = $this->read_db->table('office');

        $officeBuilder->select('DISTINCT(office_id)');
        $officeBuilder->where('office_is_active', '1');
        $officeBuilder->where('fk_context_definition_id', '1');
        $officeBuilder->where('office_is_readonly', '0');
        $officeBuilder->join('pay_history','pay_history.fk_office_id=office.office_id');
        $officeObj = $officeBuilder->get();

        $offices = [];

        if($officeObj->getNumRows() > 0){
            $offices = $officeObj->getResultArray();
        }

        return $offices;
    }
    public function generatePayrollForAllTransactingOffices(){
        // Get All active FCPs
        $activeTransactingOffices = $this->getActiveTransactingOfficesWithPayHistory();
        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();

        $countCreatedPayrolls = 0;
        foreach($activeTransactingOffices as $activeTransactingOffice){
            // Get the office current transacting month
            $officeId = $activeTransactingOffice['office_id'];
            $transactingDate = $voucherLibrary->getVoucherDate($officeId);

            // Create a payroll for the office for the month
            $response = $this->generatePayroll($officeId, $transactingDate);

            if($response['flag']){
                $countCreatedPayrolls++;
            }
        }

        return $countCreatedPayrolls;
    }

    public function generatePayroll($officeId, $reportingMonth)
    {
        // log_message('error', json_encode(compact('officeId', 'reportingMonth')));
        $userLibrary = new \App\Libraries\Core\UserLibrary();
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();

        $response = ['flag' => false, 'message' => 'Failed to generate payroll', 'header_id' => null];
        // Start Transaction
        $this->write_db->transStart();

        $officeAccountSystem = $officeLibrary->getOfficeAccountSystem($officeId);
        $officeAccountSystemId = $officeAccountSystem['account_system_id'];

        // Auto create a payroll record
        $payrollResult = $this->createPayroll($officeId, $officeAccountSystemId, $reportingMonth);

        // Check if payroll creation was successful
        if (!$payrollResult['flag']) {
            return $response;
        }

        $payrollId = $payrollResult['header_id'];

        // Get the active staff of the FCP
        $officeUsers = $userLibrary->getActiveOfficeStaff($officeId, $officeAccountSystemId);

        // Auto create payslips for all staff in an office
        // If the country's account system deduction products are set, the payslips will be prepopulated with the deductions
        // Otherwise will have empty deductions

        if (!empty($officeUsers) && $payrollResult['flag']) {

            $payslipData = $this->prepareUsersPayslipInfo($officeUsers, $payrollId);

            if (!empty($payslipData)) {
                // Create empty payslips that will be updated later
                $this->write_db->table('payslip')->insertBatch($payslipData);

                // Get all payslips for the newly created payroll
                $payrollPayslips = $this->getAllPayrollPayslips($payrollId);

                // Updater Factory class
                $updaterFactory = new \App\Libraries\Grants\Builders\PayslipUpdater\PayslipUpdaterFactory();

                // Define the types of updates to be performed
                $updateTypes = [
                    'deductions',
                    'earnings', // Both payable and liability
                    'taxable_pay', // = basic_pay + total_earnings
                    'net_pay' // taxable_pay - total_deductions - liability_earnings
                ];

                // Loop through each update type and perform the update
                foreach ($updateTypes as $type) {
                    $updaterFactory->createUpdater($type, $payrollPayslips)->updater();
                }           
            }
        }

        // End Transaction
        $this->write_db->transComplete();

        if ($this->write_db->transStatus() != FALSE) {
            $response['flag'] = true;
            $response['message'] = 'Payroll generated successfully';
            $response['header_id'] = $payrollId;
        }

        return $response;
    }

    private function getAllPayrollPayslips($payrollId)
    {
        return $this->write_db->table('payslip')
            // ->select('payslip.*')
            ->where('fk_payroll_id', hash_id($payrollId, 'decode'))
            ->get()
            ->getResultArray();
    }


    private function getPayHistoryBasicPay($payHistoryId)
    {
        $payHistoryBuilder = $this->read_db->table('pay_history');
        $payHistoryBuilder->select('earning_amount');
        $payHistoryBuilder->join('earning', 'earning.fk_pay_history_id=pay_history.pay_history_id');
        $payHistoryBuilder->join('earning_category', 'earning_category.earning_category_id=earning.fk_earning_category_id');
        $payHistoryBuilder->where('earning_category_is_basic', '1');
        $payHistoryBuilder->where('pay_history_id', $payHistoryId);
        $payHistoryObj = $payHistoryBuilder->get();

        if ($payHistoryObj->getNumRows() > 0) {
            return (float)$payHistoryObj->getRowArray()['earning_amount'];
        }

        return 0;
    }

    private function getPayHistoryTaxablePay($payHistoryId)
    {
        $payHistoryBuilder = $this->read_db->table('pay_history');
        $payHistoryBuilder->selectSum('earning_amount');
        $payHistoryBuilder->join('earning', 'earning.fk_pay_history_id=pay_history.pay_history_id');
        $payHistoryBuilder->join('earning_category', 'earning_category.earning_category_id=earning.fk_earning_category_id');
        $payHistoryBuilder->where('earning_category_is_taxable', '1');
        $payHistoryBuilder->where('pay_history_id', $payHistoryId);
        $payHistoryBuilder->groupBy('pay_history.pay_history_id');
        $payHistoryObj = $payHistoryBuilder->get();

        if ($payHistoryObj->getNumRows() > 0) {
            return (float)$payHistoryObj->getRowArray()['earning_amount'];
        }

        return 0;
    }

    private function prepareUsersPayslipInfo($officeUsers, $payrollId)
    {
        $payHistoryLibrary = new \App\Libraries\Grants\PayHistoryLibrary();
        

        $payslipData = [];
        $cnt = 0;
        foreach ($officeUsers as $officeUser) {
            $nameAndTrackNumber = $this->generateItemTrackNumberAndName('payslip');
            $currentPayHistory = $payHistoryLibrary->getUserLatestPayHistory($officeUser['user_id']);

            if (!$currentPayHistory) {
                continue; // Skip if no pay history found
            }

            $payHistoryId = $currentPayHistory->pay_history_id;

            $basic_pay = $this->getPayHistoryBasicPay($payHistoryId); 
            $taxable_pay = $this->getPayHistoryTaxablePay($payHistoryId); 

            $payslipData[$cnt] = [
                'payslip_name' => $officeUser['user_firstname'] . ' ' . $officeUser['user_lastname'],
                'payslip_track_number' => $nameAndTrackNumber['payslip_track_number'],
                'fk_user_id' => $officeUser['user_id'],
                'fk_payroll_id' => hash_id($payrollId, 'decode'),
                'payslip_basic_pay' => $basic_pay,
                'payslip_taxable_pay' => $taxable_pay,
                'fk_pay_history_id' => $payHistoryId,
                'payslip_total_deduction' => 0,
                'payslip_net_pay' => 0,
                'payslip_total_liability' => 0,
                'payslip_total_earning' => 0,
                'payslip_created_date' => date('Y-m-d'),
                'payslip_created_by' => $this->session->user_id,
                'payslip_last_modified_by' => $this->session->user_id
            ];

            $cnt++;
        }

        return $payslipData;
    }


    private function createPayroll($officeId, $officeAccountSystemId, $reportingMonth)
    {
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        $payrollOffice = $officeLibrary->getOfficeById($officeId);
        $nameAndTrackNumber = $this->generateItemTrackNumberAndName('payroll');
        $payrollName = $payrollOffice['office_code'] . '-' . date('F Y', strtotime($reportingMonth));
        $initialStatusId = $statusLibrary->initialItemStatus('payroll', $officeAccountSystemId);

        $newPayroll = [
            'payroll_name' => $payrollName,
            'payroll_track_number' => $nameAndTrackNumber['payroll_track_number'],
            'fk_office_id' => $payrollOffice['office_id'],
            'payroll_period' => date('Y-m-01', strtotime($reportingMonth)),
            'payroll_created_date' => date('Y-m-d'),
            'payroll_created_by' => $this->session->user_id,
            'payroll_last_modified_by' => $this->session->user_id,
            'fk_status_id' => $initialStatusId
        ];

        $postData = new PostData($newPayroll, 'payroll', false);

        return $this->add($postData);
    }
    function additionalListColumns(): array
    {
        $additional = [
            'action' => 'payroll_id'
        ];

        return $additional;
    }

    function formatColumnsValues(string $columnsName, mixed $columnsValues, array $rowData, array $dependancyData = []): mixed
    {

        if ($columnsName == 'action') {
            $this->approvalAction($columnsValues, $rowData);
        }

        return $columnsValues;
    }

    private function approvalAction(&$columnsValues, $rowData)
    {
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $office_account_system_id = $officeLibrary->getOfficeAccountSystem($rowData['office_id'])['account_system_id'];
        $status_data = $this->actionButtonData($this->controller, $office_account_system_id);
        extract($status_data);

        $columnsValues = approval_action_button($this->controller, $item_status, $rowData['payroll_id'], $rowData['status_id'], $item_initial_item_status_id, $item_max_approval_status_ids, false, true);

    }

    public function transactionValidateDuplicatesColumns(): array
    {
        return ['fk_office_id', 'payroll_period'];
    }

    function listTableVisibleColumns(): array
    {
        return [
            'payroll_id',
            'payroll_track_number',
            'payroll_name',
            'office_name',
            'payroll_period',
            'payroll_created_date'
        ];
    }

    function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void
    {
        if (!$this->session->system_admin) {
            $queryBuilder->whereIn('payroll.fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        }
    }

    function postApprovalActionEvent(array $item): void
    {
        // Create Tax Payable Voucher for Taxable Deductions and Payable voucher for payable staff earnings when the payroll becomes fully approved
    }

}
<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\PayrollModel;
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
<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\SystemOpeningBalanceModel;
class SystemOpeningBalanceLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new SystemOpeningBalanceModel();

        $this->table = 'system_opening_balance';
    }

    function detailTables(): array {
        return [
            'opening_bank_balance',
            'opening_cash_balance',
            'opening_fund_balance',
            'opening_accrual_balance',
            'opening_outstanding_cheque',
            'opening_deposit_transit'
        ];
    }

    function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void {
        $queryBuilder->where(['fk_context_definition_id' => 1]);
    }

    function showEditButton(): bool{
        return false;
    }

    function showListEditActionDependancyData($systemOpeningBalance): array {
        $officeIds = array_column($systemOpeningBalance, 'fk_office_id');

        $financialReportReadBuilder = $this->read_db->table('financial_report');

        $financialReportReadBuilder->select(['fk_office_id','financial_report_id']);
        $financialReportReadBuilder->whereIn('fk_office_id', $officeIds);
        $officeReportsObj = $financialReportReadBuilder->get();

        $officesWithFinancialReports = [];

        if($officeReportsObj->getNumRows() > 0){
            $officesWithFinancialReports = array_column($officeReportsObj->getResultArray(), 'fk_office_id');
        }

        return compact('officesWithFinancialReports');
    }
   
    function showListEditAction(array $record, array $dependancyData = []): bool {
        $officesWithFinancialReports = $dependancyData['officesWithFinancialReports'];
        $check = true;

        if(in_array($record['fk_office_id'], $officesWithFinancialReports)){
            return false;
        }

        return $check;
    }
}
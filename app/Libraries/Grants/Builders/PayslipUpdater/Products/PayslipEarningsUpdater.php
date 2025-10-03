<?php

namespace App\Libraries\Grants\Builders\PayslipUpdater\Products;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Config\Services;
use \App\Libraries\Grants\Builders\PayslipUpdater\UpdaterInterface;

class PayslipEarningsUpdater implements UpdaterInterface
{
    protected BaseConnection $write_db;
    protected BaseConnection $read_db;
    function __construct(protected array $payrollPayslips)
    {
        $this->read_db = \Config\Database::connect('read');
        $this->write_db = \Config\Database::connect('write');
    }

    public function updater(): bool
    {
        $payslipBuilder = $this->write_db->table('payslip');

        if (!empty($this->payrollPayslips)) {

            $earnings = $this->getEarnings();

            if (!empty($earnings)) {
                $payslipUpdateData = [];
                $cnt = 0;
                foreach ($this->payrollPayslips as $payslip) {

                    $payHistoryId = $payslip['fk_pay_history_id'];

                    if ($payHistoryId == null)
                        continue;

                    // Payables are paid to the staff
                    $payslipEarning = array_filter(
                        $earnings,
                        fn($auxilliaryPayment) =>
                        $auxilliaryPayment['fk_pay_history_id'] == $payHistoryId
                    );

                    // Liabilities/Accrued Earnings are withheld by the FCP and paid on a later date
                    $payslipTotalLiability = array_filter(
                        $earnings,
                        fn($auxilliaryPayment) =>
                        $auxilliaryPayment['fk_pay_history_id'] == $payHistoryId && $auxilliaryPayment['earning_category_is_accrued'] == '1'
                    );

                    // Update the payslip with the total earnings and liability
                    $payslipUpdateData[$cnt]['payslip_id'] = $payslip['payslip_id'];
                    $payslipUpdateData[$cnt]['payslip_total_liability'] = !empty($payslipTotalLiability) ? array_values($payslipTotalLiability)[0]['earning_amount'] : 0;
                    $payslipUpdateData[$cnt]['payslip_total_earning'] = !empty($payslipEarning) ? array_values($payslipEarning)[0]['earning_amount'] : 0;

                    $cnt++;

                }

                if (!empty($payslipUpdateData)) {
                    $payslipBuilder->updateBatch($payslipUpdateData, 'payslip_id');
                }
            }
        }

        return $this->write_db->affectedRows() > 0 ? true : false;
    }

    private function getEarnings()
    {
        $earnigs = [];
        $payHistoryIds = array_column($this->payrollPayslips, 'fk_pay_history_id');
        // Get all payable earnings for the the payHistoryIds
        $earnigsBuilder = $this->write_db->table('earning');

        $earnigsBuilder->select('fk_pay_history_id, earning_category_is_accrued'); // include earning_category_is_taxable in the future
        $earnigsBuilder->selectSum('earning_amount');
        $earnigsBuilder->join('earning_category', 'earning_category.earning_category_id=earning.fk_earning_category_id');
        $earnigsBuilder->whereIn('fk_pay_history_id', $payHistoryIds);
        $earnigsBuilder->groupBy('fk_pay_history_id, earning_category_is_accrued');
        $earnigsObj = $earnigsBuilder->get();

        if ($earnigsObj->getNumRows() > 0) {
            $earnigs = $earnigsObj->getResultArray();
        }

        return $earnigs;
    }
}
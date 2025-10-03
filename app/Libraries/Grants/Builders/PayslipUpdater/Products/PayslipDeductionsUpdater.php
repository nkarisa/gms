<?php

namespace App\Libraries\Grants\Builders\PayslipUpdater\Products;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Config\Services;
use \App\Libraries\Grants\Builders\PayslipUpdater\UpdaterInterface;

class PayslipDeductionsUpdater implements UpdaterInterface
{

    use \App\Traits\System\SetupTrait;

    protected BaseConnection $write_db;
    protected BaseConnection $read_db;
    function __construct(protected array $payrollPayslips)
    {
        $this->read_db = \Config\Database::connect('read');
        $this->write_db = \Config\Database::connect('write');
    }

    public function updater(): bool
    {

        $parollId = $this->payrollPayslips[0]['fk_payroll_id'];

        $payrollAccountSystem = $this->getPayrollAccountSystem($parollId);

        // Account System Deductions categories
        $accountSystemPayrollDeductionCategories = $this->getAccountSystemPayrollDeductionCategories($payrollAccountSystem['account_system_id']);

        $deductions = $this->payslipsDeductions($this->payrollPayslips, $payrollAccountSystem, $accountSystemPayrollDeductionCategories);

        // Insert Payroll deductions for all payslips
        return $this->insertPayrollDeductions($deductions, $accountSystemPayrollDeductionCategories);

    }
    private function getPayrollAccountSystem($payrollId)
    {
        // Get the account system code for a particular payroll
        $payrollAccountSystem = $this->write_db->table('payroll')
            ->select('account_system_id, account_system_code')
            ->join('office', 'office.office_id = payroll.fk_office_id')
            ->join('account_system', 'account_system.account_system_id = office.fk_account_system_id')
            ->where('payroll.payroll_id', $payrollId)
            ->get()
            ->getRowArray();

        return $payrollAccountSystem;
    }

    private function getAccountSystemPayrollDeductionCategories($accountSystemId)
    {
        // Get deduction categories for a particular account system
        $accountSystemDeductionCategories = $this->write_db->table('payroll_deduction_category')
            ->select('payroll_deduction_category_id,payroll_deduction_category_code')
            ->where('fk_account_system_id', $accountSystemId)
            ->get()
            ->getResultArray();

        return $accountSystemDeductionCategories;
    }

    private function payslipsDeductions($payrollPayslips, $payrollAccountSystem, $accountSystemPayrollDeductionCategories)
    {
        $deductions = [];

        // Get the account system code
        $payrollAccountSystemCode = $payrollAccountSystem['account_system_code'];

        // Create deductions with PayrollDeductionFactory
        $deducationFactory = new \App\Libraries\Grants\Builders\Payroll\PayrollDeductionFactory();

        // Get the deduction category code
        // $accountSystemPayrollDeductionCategories = $this->getAccountSystemPayrollDeductionCategories($payrollAccountSystem['account_system_id']);
        $accountSystemPayrollDeductionCodes = array_column($accountSystemPayrollDeductionCategories, 'payroll_deduction_category_code');

        $accountSystemPayrollDeductionProductNames = array_map(function ($deducationCode) use ($payrollAccountSystemCode) {
            return strtoupper($payrollAccountSystemCode) . '_' . ucfirst($deducationCode);
        }, $accountSystemPayrollDeductionCodes);


        foreach ($payrollPayslips as $payrollPayslip) {
            $deductions[$payrollPayslip['payslip_id']] = [];
            foreach ($accountSystemPayrollDeductionProductNames as $product) {
                $productClassName = str_replace('_', '', $product);

                $deductionProduct = $deducationFactory
                    ->createDeductionProduct($productClassName) ?? null;

                $deductionCode = strtolower(explode('_', $product)[1]);

                if ($deductionProduct != null) {
                    $newDeduction = $deductionProduct->setPayslip($payrollPayslip)->render();
                    $deductions[$payrollPayslip['payslip_id']][$deductionCode] = $newDeduction;
                } else {
                    $deductions[$payrollPayslip['payslip_id']][$deductionCode] = 0;
                }
            }
        }

        return $deductions;
    }

    private function insertPayrollDeductions($payslipsDeductions, $accountSystemPayrollDeductionCategories)
    {
        $payrollDeductionBuilder = $this->write_db->table('payroll_deduction');

        foreach ($payslipsDeductions as $payslip_id => $deductions) {

            $payrollDeductionData = [];
            $cnt = 0;
            foreach ($deductions as $deducation_code => $deduction_amount) {
                $nameAndTrackNumber = $this->generateItemTrackNumberAndName('payroll_deduction');

                $deductionCategory = array_filter($accountSystemPayrollDeductionCategories, function ($payrollDeductionCategory) use ($deducation_code) {
                    return $payrollDeductionCategory['payroll_deduction_category_code'] == $deducation_code;
                });

                $deductionCategory = array_values($deductionCategory);

                $payrollDeductionData[$cnt] = [
                    'payroll_deduction_name' => $nameAndTrackNumber['payroll_deduction_name'],
                    'payroll_deduction_track_number' => $nameAndTrackNumber['payroll_deduction_track_number'],
                    'fk_payroll_deduction_category_id' => $deductionCategory[0]['payroll_deduction_category_id'],
                    'fk_payslip_id' => $payslip_id,
                    'payroll_deduction_amount' => $deduction_amount,
                    'payroll_deduction_created_date' => date('Y-m-d'),
                    'payroll_deduction_created_by' => Services::session()->user_id,
                    'payroll_deduction_last_modified_by' => Services::session()->user_id
                ];
                $cnt++;
            }

            if (!empty($payrollDeductionData)) {
                $payrollDeductionBuilder->insertBatch($payrollDeductionData);

                // Update payslip total deduction and net pay
                $totalDeduction = array_sum(array_column($payrollDeductionData, 'payroll_deduction_amount'));

                $payslipBuilder = $this->write_db->table('payslip');
                $payslipBuilder->where('payslip_id', $payslip_id);

                // Get current payslip data
                $currentPayslip = $payslipBuilder->get()->getRowArray();
                $grossPay = $currentPayslip['payslip_basic_pay'];

                // Calculate net pay
                $netPay = $grossPay - $totalDeduction;

                // Update payslip with total deduction and net pay
                $payslipBuilder->where('payslip_id', $payslip_id);
                $payslipBuilder->update([
                    'payslip_total_deduction' => $totalDeduction,
                    'payslip_net_pay' => $netPay,
                    // 'payslip_total_liability' => $totalLiability,
                    'payslip_last_modified_by' => Services::session()->user_id
                ]);

            }
        }

        return $this->write_db->affectedRows() > 0 ? true : false;
    }

}
<?php

namespace App\Libraries\Grants\Builders\Payroll\Products;

use \App\Libraries\Grants\Builders\Payroll\DeductionInterface;

class KEPaye implements DeductionInterface
{
    private array $payslip;
    public function setPayslip($payslip): DeductionInterface
    {
        $this->payslip = $payslip;
        return $this;
    }
    public function render(): float
    {
        $taxable_income = $this->payslip['payslip_taxable_pay'];
        // Define the tax bands and rates as of Finance Act 2023.
        // The bands are based on monthly taxable income.
        $tax_bands = [
            ['rate' => 0.10, 'upper_limit' => 24000],
            ['rate' => 0.25, 'upper_limit' => 32333], // 24,000 + 8,333
            ['rate' => 0.30, 'upper_limit' => 500000], // 32,333 + 467,667
            ['rate' => 0.325, 'upper_limit' => 800000], // 500,000 + 300,000
            ['rate' => 0.35, 'upper_limit' => PHP_FLOAT_MAX] // Above 800,000
        ];

        $total_tax = 0;
        $remaining_income = $taxable_income;

        // Apply the tax rate to each band progressively.
        if ($remaining_income > 0) {
            $band1_tax = min($remaining_income, 24000) * 0.10;
            $total_tax += $band1_tax;
            $remaining_income -= 24000;
        }

        if ($remaining_income > 0) {
            $band2_tax = min($remaining_income, 8333) * 0.25;
            $total_tax += $band2_tax;
            $remaining_income -= 8333;
        }

        if ($remaining_income > 0) {
            $band3_tax = min($remaining_income, 467667) * 0.30;
            $total_tax += $band3_tax;
            $remaining_income -= 467667;
        }

        if ($remaining_income > 0) {
            $band4_tax = min($remaining_income, 300000) * 0.325;
            $total_tax += $band4_tax;
            $remaining_income -= 300000;
        }

        if ($remaining_income > 0) {
            $band5_tax = $remaining_income * 0.35;
            $total_tax += $band5_tax;
        }

        // Apply the monthly personal relief.
        $personal_relief = 2400.00;
        $paye_payable = max(0, $total_tax - $personal_relief);

        return round($paye_payable, 2);
    }
}
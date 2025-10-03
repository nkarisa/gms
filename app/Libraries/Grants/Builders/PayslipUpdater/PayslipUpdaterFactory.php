<?php 

namespace App\Libraries\Grants\Builders\PayslipUpdater;

use App\Libraries\Grants\Builders\PayslipUpdater\Products;

class PayslipUpdaterFactory {
    function createUpdater(string $payslipField, array $payslips): UpdaterInterface{

        switch($payslipField) {
            case 'deductions':
                return new Products\PayslipDeductionsUpdater($payslips);
            case 'earnings':
                return new Products\PayslipEarningsUpdater($payslips);
            case 'taxable_pay':
                return new Products\PayslipTaxablePayUpdater($payslips);
            case 'net_pay':
                return new Products\PayslipNetPayUpdater($payslips);
            default:
                throw new \InvalidArgumentException("Unknown payslip field: " . $payslipField);
        }

    }
}
<?php 

namespace App\Libraries\Grants\Builders\Payroll\Products;

use \App\Libraries\Grants\Builders\Payroll\DeductionInterface;

class KESha implements DeductionInterface {
    private array $payslip;
    public function setPayslip($payslip): DeductionInterface{
        $this->payslip = $payslip;
        return $this;
    }
    
    public function render(): float{
        $sha_rate = 2.75;

        $employee_current_contract_value = $this->payslip['payslip_basic_pay'];
        $current_calculated_sha = ($employee_current_contract_value * $sha_rate) / 100;

        return $current_calculated_sha;
    }
}
<?php 

namespace App\Libraries\Grants\Builders\Payroll;

interface DeductionInterface {
    public function setPayslip($payslip): DeductionInterface;
    public function render(): float;
}
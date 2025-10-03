<?php

namespace App\Libraries\Grants\Builders\PayslipUpdater\Products;

use App\Libraries\Grants\Builders\PayslipUpdater\UpdaterInterface;

use CodeIgniter\Database\BaseConnection;
class PayslipNetPayUpdater implements UpdaterInterface
{
    protected BaseConnection $write_db;
    protected BaseConnection $read_db;
    function __construct(protected array $payslips)
    {
        $this->read_db = \Config\Database::connect('read');
        $this->write_db = \Config\Database::connect('write');
    }

    protected function getUpdatedPayslips()
    {
        $payslipIds = array_column($this->payslips, 'payslip_id');

        $payslipBuilder = $this->write_db->table('payslip');
        $payslipBuilder->select('payslip_id, payslip_basic_pay, payslip_total_earning, payslip_total_deduction, payslip_total_liability');
        $payslipBuilder->whereIn('payslip_id', $payslipIds);
        $payslipObj = $payslipBuilder->get();

        $this->payslips = $payslipObj->getResultArray();
    }

    function updater(): bool
    {
        $this->getUpdatedPayslips();
       
        $cnt = 0;
        $payslipUpdates = [];
        
        foreach ($this->payslips as $payslip) {
            $payslipUpdates[$cnt]['payslip_id'] = $payslip['payslip_id'];
            $payslipUpdates[$cnt]['payslip_net_pay'] = $payslip['payslip_total_earning'] - ($payslip['payslip_total_deduction'] + $payslip['payslip_total_liability']);
            $cnt++;
        }

        if(!empty($payslipUpdates)){
            $this->write_db->table('payslip')->updateBatch($payslipUpdates, 'payslip_id');
        }

        return true;
    }
}
<?php 

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Grants\PayrollLibrary;

class MyDailyTask extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'task:daily';
    protected $description = 'Runs a daily maintenance task.';

    public function run(array $params)
    {
        
        $payrollLibrary = new PayrollLibrary();
        $result = $payrollLibrary->generatePayroll('1621', '2025-08-01');

        if($result['flag']){
            CLI::write('Payroll created successfully!');
        }else{
            CLI::write('Payroll creating failed!');
        }
        
    }
}
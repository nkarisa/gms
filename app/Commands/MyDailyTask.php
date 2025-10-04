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
        $response = $payrollLibrary->generatePayrollForAllTransactingOffices();

        if($response){
            CLI::write('Payroll created successfully!');
        }else{
            CLI::write('Payroll creating failed!');
        }
        
    }
}
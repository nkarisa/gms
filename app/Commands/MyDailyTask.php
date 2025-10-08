<?php 

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\System\ScheduledTasks;

class MyDailyTask extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'task:daily';
    protected $description = 'Runs a daily maintenance task.';

    public function run(array $params)
    {
        
        // $tasksLibrary = new ScheduledTasks();
        // $response = $tasksLibrary->schedule();

        // if($response){
        //     CLI::write('Payroll created successfully!');
        // }else{
        //     CLI::write('Payroll creating failed!');
        // }
        
    }
}
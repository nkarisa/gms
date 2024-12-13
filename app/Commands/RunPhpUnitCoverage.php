<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RunPhpUnitCoverage extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:coverage';
    protected $description = 'Run PHPUnit tests with code coverage report generation.';

    public function run(array $params)
    {
        CLI::write('Running PHPUnit tests with coverage report...', 'yellow');

        $command = "cd ".APPPATH."../ && export XDEBUG_MODE=coverage  && composer test";

        // Execute the command
        $output = shell_exec($command);
        
        if ($output) {
            CLI::write($output, 'green');
            CLI::write('Coverage report generated at ./build/logs/coverage.html', 'green');
        } else {
            CLI::write('Failed to execute PHPUnit command.', 'red');
        }
    }
}

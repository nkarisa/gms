<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Test\DatabaseTestTrait;

class RunPhpUnitCoverage extends BaseCommand
{
    use DatabaseTestTrait;

    protected $group = 'Testing';
    protected $name = 'test:coverage';
    protected $description = 'Run Migration Update and PHPUnit tests with code coverage report generation.';

    public function run(array $params)
    {
        // Run all App\Database\Migration to the test database
        CLI::write('Running Setup Migrations .....', 'yellow');

        $migrationOutput = shell_exec("php " . APPPATH . "../spark migrate -n App\Database\Migrations -g tests");

        if ($migrationOutput) {
            CLI::write($migrationOutput, 'yellow');
            CLI::write('Migration successful', 'green');
        } else {
            CLI::write('Failed to run migrations.', 'red');
        }

        // Instantiate the settings table schema

        $db = \Config\Database::connect();
        $testingDatabase = env("database.tests.database");
        $db->query("CREATE TABLE IF NOT EXISTS `$testingDatabase`.`settings` (
            `id` int NOT NULL AUTO_INCREMENT,
            `class` varchar(255) NOT NULL,
            `key` varchar(255) NOT NULL,
            `value` text,
            `type` varchar(31) NOT NULL DEFAULT 'string',
            `context` varchar(255) DEFAULT NULL,
            `created_at` datetime NOT NULL,
            `updated_at` datetime NOT NULL,
            PRIMARY KEY (`id`)
            );");

        // Begin running PHPUnit tests

        CLI::write('Running PHPUnit tests with coverage report...', 'yellow');

        $command = "cd " . APPPATH . "../ && export XDEBUG_MODE=coverage  && composer test";
        // Execute the command
        $output = shell_exec($command);

        if ($output) {
            CLI::write($output, 'yellow');
            CLI::write('Coverage report generated at ./build/logs/coverage.html', 'green');
        } else {
            CLI::write('Failed to execute PHPUnit command.', 'red');
        }
    }
}

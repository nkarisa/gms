<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class DbInit extends BaseCommand
{
    protected $group        = 'db';
    protected $name         = 'db:init';
    protected $description  = 'Executes SQL statements from a specified file.';
    protected $usage        = 'db:runsql <filepath>';
    protected $arguments    = [
        'filepath' => 'Path to the SQL file to execute.',
    ];
    protected $options      = [];

    public function run(?array $params = null)
    {
        $filepath = APPPATH.'Database/Sql/schema.sql'; // Default SQL script path

        if(!file_exists($filepath)){
            $filepathRaw = $params[0] ?? CLI::prompt('Enter the path to the SQL file from the app directory: app/');
            $filepath = APPPATH.$filepathRaw;
        }

        if (!is_file($filepath) || !is_readable($filepath)) {
            CLI::error("Error: File not found or not readable: {$filepath}");
            return;
        }

        $db = Database::connect();
        $sql = file_get_contents($filepath);

        $queries = explode(';', $sql); // Split the SQL file by semicolons

        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                try {
                    $db->query($query);
                    CLI::write("Executed: {$query}", 'green');
                } catch (\Exception $e) {
                    CLI::error("Error executing query: {$query}");
                    CLI::error($e->getMessage());
                }
            }
        }

        CLI::write('SQL file executed successfully.', 'green');
    }
}
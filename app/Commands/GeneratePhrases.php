<?php

namespace App\Commands;

use App\Models\Core\AccountSystemSettingModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class GeneratePhrases extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'App';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'lang:generate';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Get all phrases from all files in the application and generate a language file for each country according to their most spoken languages.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'command:name [arguments] [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '--language' => 'Language code (e.g., en, sw, am, )',
        '--country'  => 'Country code (e.g., KE, TZ, UG, ZM, MW, KH, TG, )'
    ];

    private $accountSystemBuilder;

    function __construct(){

        //Database connection
        $db = Database::connect();
        $this->accountSystemBuilder = $db->table('account_system');
    }

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $language = CLI::getOption('language');
        $country  = CLI::getOption('country');

        if (!$language || !$country) {
            CLI::error('Both --language and --country options are required.');
            return;
        }

        helper('language');

        //Country Languages
        $countryLanguages =[
            'global' => ['en'],
            'KE' => ['en', 'sw'],
            'TZ' => ['en', 'sw'],
            'UG' => ['en', 'sw'],
            'RW' => ['en', 'sw', 'kinyarwanda'],
            'GH' => ['en'],
            'ET' => ['en', 'am'],
            'ZM' => ['en'],
            'MW' => ['en'],
            // Add more if needed
        ];

        $countryCode = TRIM(strtolower($country));
        $results = $this->accountSystemBuilder->select('account_system_code')
            ->where('account_system_is_active', 1)
            ->like('account_system_code', $countryCode)
            -> get()->getResultArray();

        foreach ($results as $row) {
            if($row['account_system_code'] == 'global'){
                $existingAccountSystemCode = 'Global';
            }
            else {
                $existingAccountSystemCode = strtoupper(trim($row['account_system_code']));
            }

            if (!array_key_exists($country, $countryLanguages)) {
                CLI::write("Skipping $country: No language mapping defined.", 'yellow');
                continue;
            }

            foreach ($countryLanguages[$country] as $lang) {
                CLI::write("Generating for Country: $country, Language: $lang...", 'green');
                process_php_files(APPPATH, $lang, $country);
            }
        }

        //process_php_files();
        process_get_phrase_files(APPPATH, $language, $existingAccountSystemCode);
    }
}

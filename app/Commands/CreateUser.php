<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\Core\UserModel;

class CreateUser extends BaseCommand
{
    protected $group       = 'Users';
    protected $name        = 'user:create';
    protected $description = 'Create a new user in the users table';

    private $userModel;
    private $roleBuilder;
    private $contextDefinitionBuilder;
    private $countryCurrencyBuilder;
    private $languageBuilder;
    private $accountSystemBuilder;

    function __construct(){
        $this->userModel = new UserModel();

         // Database connection
         $db = \Config\Database::connect();
         $this->roleBuilder = $db->table('role');
         $this->accountSystemBuilder = $db->table('account_system');
         $this->contextDefinitionBuilder = $db->table('context_definition');
         $this->countryCurrencyBuilder = $db->table('country_currency');
         $this->languageBuilder = $db->table('language');
    }

    public function run(array $params)
    {
        $contextDefinitionSelect = $this->getActiveContextDefinitionnIdAndNames();
        $accountSystemSelect = $this->getActiveAccountSystemsIdAndNames();
        $languageSelect = $this->getActiveLanguagesIdAndNames();

        // Prompt for user details
        $firstName = CLI::prompt('Enter first name', null, 'required');
        $lastName = CLI::prompt('Enter last name', null, 'required');
        $email = CLI::prompt('Enter email', null, 'required|valid_email');
        $contextDefinitionId = CLI::promptByKey('Choose a User Type:', $contextDefinitionSelect, ['required']);
        $accountSystemId = CLI::promptByKey('Choose an Account System:', $accountSystemSelect, ['required']);
        
        $roleSelect = $this->getActiveRolesIdAndNamesByAccountSystemAndContext($accountSystemId,$contextDefinitionId); 
        $roleId = CLI::promptByKey('Choose a role:', $roleSelect, ['required']);

        $languageId = CLI::promptByKey('Choose a Language:', $languageSelect, ['required']);

        $countryCurrencySelect = $this->getActiveCountryCurrencyIdAndNamesByAccountSystem($accountSystemId);
        $countryCurrencyId = CLI::promptByKey('Choose a Country Currency:', $countryCurrencySelect, ['required']);

        // Instantiate the UserModel
        $userModel = new UserModel();

        $userLibrary = new \App\Libraries\Core\UserLibrary();
        // Prepare the user data
        $user = [
            'user_email'      => $email,
            'user_firstname' => $firstName,
            'user_lastname'  => $lastName,
            'user_name'     => $email,
            'fk_context_definition_id' => $contextDefinitionId,
            'fk_language_id' => $languageId,
            'fk_country_currency_id' => $countryCurrencyId,
            'fk_role_id' => $roleId,
            'fk_account_system_id' => 3,
            'user_password' => $userLibrary->passwordSalt('password'),
            'user_created_date' => date('Y-m-d')
        ];

        // Save the user data
        if ($userModel->insert($user)) {
            CLI::write('User created successfully!', 'green');
        } else {
            CLI::error('Failed to create user.');
            CLI::error(implode("\n", $userModel->errors()));
        }
    }

    function getActiveContextDefinitionnIdAndNames(){
        $this->contextDefinitionBuilder->select('context_definition_id,context_definition_name');
        $this->contextDefinitionBuilder->where(['context_definition_is_active' => 1]);
        $contextDefinitions = $this->contextDefinitionBuilder->get()->getResultArray();

        $contextDefinitions_ids = array_column($contextDefinitions, 'context_definition_id');
        $contextDefinitions_names = array_column($contextDefinitions, 'context_definition_name');
        $contextDefinitionSelect = array_combine($contextDefinitions_ids, $contextDefinitions_names);

        return $contextDefinitionSelect;
    }

    function getActiveAccountSystemsIdAndNames(){
        $this->accountSystemBuilder->select('account_system_id,account_system_name');
        $this->accountSystemBuilder->where(['account_system_is_active' => 1]);
        $accountSystems = $this->accountSystemBuilder->get()->getResultArray();

        $accountSystems_ids = array_column($accountSystems, 'account_system_id');
        $accountSystems_names = array_column($accountSystems, 'account_system_name');
        $accountSystemSelect = array_combine($accountSystems_ids, $accountSystems_names);

        return $accountSystemSelect;
    }

    function getActiveRolesIdAndNamesByAccountSystemAndContext(int $accountSystemId, int $contextDefinitionId){
        $this->roleBuilder->select('role_id,role_name');
        $this->roleBuilder->where(['role_is_active' => 1, 
        'fk_context_definition_id' => $contextDefinitionId, 'fk_account_system_id' => $accountSystemId]);
        $roles = $this->roleBuilder->get()->getResultArray();

        $roles_ids = array_column($roles, 'role_id');
        $roleNames = array_column($roles, 'role_name');
        $roleSelect = array_combine($roles_ids, $roleNames);

        return $roleSelect;
    }

    function getActiveLanguagesIdAndNames(){
        $this->languageBuilder->select('language_id,language_name');
        $languages = $this->languageBuilder->get()->getResultArray();
        
        $language_ids = array_column($languages, 'language_id');
        $language_names = array_column($languages, 'language_name');
        $languageSelect = array_combine($language_ids, $language_names);

        return $languageSelect;
    }

    function getActiveCountryCurrencyIdAndNamesByAccountSystem(int $accountSystemId){
        $this->countryCurrencyBuilder->select('country_currency_id,country_currency_name');
        $this->countryCurrencyBuilder->where(['fk_account_system_id' => $accountSystemId]);
        $countryCurrencies = $this->countryCurrencyBuilder->get()->getResultArray();

        $countryCurrency_ids = array_column($countryCurrencies, 'country_currency_id');
        $countryCurrency_names = array_column($countryCurrencies, 'country_currency_name');
        $countryCurrencySelect = array_combine($countryCurrency_ids, $countryCurrency_names);
        
        return $countryCurrencySelect;
    }

}

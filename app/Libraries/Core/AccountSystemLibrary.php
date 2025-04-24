<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\AccountSystemModel;
class AccountSystemLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

  protected $table;
  protected $coreModel;

  function __construct()
  {
    parent::__construct();

    $this->coreModel = new AccountSystemModel();

    $this->table = 'account_system';
  }

  function getAccountSystems()
  {
    $builder = $this->read_db->table($this->table);
    $builder->select(array('account_system_id', 'account_system_code', 'account_system_name'));
    $account_systems = $builder->get()->getResultObject();
    return $account_systems;
  }

  function getAccountSystemIdByCode($account_system_code)
  {
    $builder = $this->read_db->table('account_system');
    $builder->where('account_system_code', $account_system_code);
    $account_system_id = $builder->get()->getRow()->account_system_id;
    return $account_system_id;
  }

  /**
   * get_countries(): return an array of account systems/countries
   * @author Onduso 
   * @access public 
   * @return array
   */
  public function getCountries(): array
  {

    $builder = $this->read_db->table($this->table);
    $builder->select(['account_system_id', 'account_system_code']);
    $builder->where(['account_system_is_active' => 1]);
    $countries = $builder->get()->getResultArray();

    $country_ids = array_column($countries, 'account_system_id');
    $country_code = array_column($countries, 'account_system_code');

    $country_ids_and_codes = array_combine($country_ids, $country_code);

    return $country_ids_and_codes;
  }

  /**
   * get_country_language(): returns language id
   * @author Onduso 
   * @access public 
   * @return int
   * @param int $account_system_id
   */
  public function getCountryLanguage(int $account_system_id): array
  {

    $builder = $this->read_db->table('account_system_language');

    $builder->select(['fk_language_id']);
    $builder->where(['account_system_language.fk_account_system_id' => $account_system_id]);
    $language = $builder->get();

    $language_id = 0;

    if ($language->getNumRows() > 0) {
      $language_id = $language->getRow()->fk_language_id;
    }

    return compact('language_id');
  }

  public function changeFieldType(): array
  {
    $fields = [];

    // Query ddatabase for all languages in an account system
    $builder = $this->read_db->table('language');
    $builder->select(['language_id', 'language_name']);
    $languages = $builder->get()->getResultArray();

    // $fields['country_currency_name']['field_type'] = 'text';

    $fields['language_name']['field_type'] = 'select';
    foreach ($languages as $language) {
      $fields['language_name']['options'][$language['language_id']] = $language['language_name'];
    }

    // Get account systm settings
    $builder = $this->read_db->table('account_system_setting');
    $builder->select(['account_system_setting_name']);
    $builder->where(['account_system_setting_value' => 1]);
    $account_system_settings = $builder->get()->getResultArray();

    $fields['account_system_settings']['field_type'] = 'select';
    $fields['account_system_settings']['select2'] = true;
    foreach ($account_system_settings as $account_system_setting) {
      $fields['account_system_settings']['options'][$account_system_setting['account_system_setting_name']] = get_phrase($account_system_setting['account_system_setting_name']);
    }

    $fields['country_currency_name']['field_type'] = 'select';
    $currency_json = file_get_contents(APPPATH . 'Temp/currency.json');
    $currency_data = json_decode($currency_json, true);

    $fields['default_project_start_date']['field_type'] = 'date';

    foreach ($currency_data as $currency) {
      $fields['country_currency_name']['options'][$currency['code'].'--'.$currency['name']] = $currency['name'] . " (" . $currency['code'] . ")";
    }

    // Get Account System Settings
    $builder = $this->read_db->table('account_system_setting');
    $builder->select(['account_system_setting_name']);
    $builder->where(['account_system_setting_value' => 1]);
    $account_system_settings = $builder->get()->getResultArray();


    $fields['account_system_level']['field_type'] = 'select';
    $levels = ['4' => get_phrase('country'), '5' => get_phrase('region')];

    foreach($levels as $key => $level){
      $fields['account_system_level']['options'][$key] = $level;
    }

    $fields['template_account_system']['field_type'] = 'select';
    // Get all country, region, global
    $accountSystemReadBuilder = $this->read_db->table('account_system');
    $accountSystemReadBuilder->select(['account_system_id','account_system_name']);
    $accountSystemReadBuilder->where(['account_system_is_active' => 1, 'account_system_level>' => 4]);
    $activeAccountSystemObj =$accountSystemReadBuilder->get();

    if($activeAccountSystemObj->getNumRows() > 0){
      $activeAccountSystems = $activeAccountSystemObj->getResultArray();

      foreach($activeAccountSystems as $activeAccountSystem){
        $fields['template_account_system']['options'][$activeAccountSystem['account_system_id']] = $activeAccountSystem['account_system_name'];
      }
    }

    return $fields;
  }

private function createCountryCurrency(StatusLibrary $statusLib, int $account_system_id, string $currency_code_and_name): int|null
  {
    $currency_code_and_name_array = explode("--",$currency_code_and_name);
    $currency_code = $currency_code_and_name_array[0];
    $currency_name = $currency_code_and_name_array[1];

    $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('country_currency');

    $countryCurrentInsert['country_currency_name'] = $currency_name;
    $countryCurrentInsert['country_currency_track_number'] = $itemTrackNumberAndName['country_currency_track_number'];
    $countryCurrentInsert['country_currency_code'] = $currency_code;
    $countryCurrentInsert['fk_account_system_id'] = $account_system_id;
    $countryCurrentInsert['country_currency_created_by'] = $this->session->user_id;
    $countryCurrentInsert['country_currency_created_date'] = date('Y-m-d');
    $countryCurrentInsert['country_currency_last_modified_by'] = $this->session->user_id;
    $countryCurrentInsert['country_currency_last_modified_date'] = date('Y-m-d');
    $countryCurrentInsert['fk_status_id'] = $statusLib->initialItemStatus('country_currency');
    $countryCurrentInsert['fk_approval_id'] = NULL;


    $countryCurrencyWriteBuilder = $this->write_db->table('country_currency');
    $countryCurrencyWriteBuilder->insert($countryCurrentInsert);

    if ($this->write_db->affectedRows() > 0) {
      return $this->write_db->insertID();
    }

    return null;
  }

private function updateAccountSystemSettings(array $account_system_settings, int $account_system_id): void
  {
    $accountSystemSettingsReadBuilder = $this->read_db->table('account_system_setting');
    $accountSystemSettingsWriteBuilder = $this->write_db->table('account_system_setting');

    foreach ($account_system_settings as $account_system_setting_name) {
      $accountSystemSettingsReadBuilder->where(['account_system_setting_name' => $account_system_setting_name]);
      $accountSystemSettingObj = $accountSystemSettingsReadBuilder->get();

      if ($accountSystemSettingObj->getNumRows() > 0) {
        $accountSystemSettingAccounts = $accountSystemSettingObj->getRow()->account_system_setting_accounts;

        $accountSystemSettingAccountsArray = json_decode($accountSystemSettingAccounts);

        if (is_array($accountSystemSettingAccountsArray)) {
          array_push($accountSystemSettingAccountsArray, $account_system_id);
        } else {
          $accountSystemSettingAccountsArray = [$account_system_id];
        }

        $accountSystemSettingsWriteBuilder->where(['account_system_setting_name' => $account_system_setting_name]);
        $accountSystemSettingsWriteBuilder->update(['account_system_setting_accounts' => json_encode($accountSystemSettingAccountsArray)]);
      }
    }
  }

private function createAccountSystemLanguage(StatusLibrary $statusLib, int $account_system_id, int $language_id, $account_system_code): void
  {
    $accountSystemLanguageWriteBuilder = $this->write_db->table("account_system_language");

    $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('account_system_language');
    $accountSystemLanguageInsert['account_system_language_name'] = $itemTrackNumberAndName['account_system_language_name'];
    $accountSystemLanguageInsert['account_system_language_track_number'] = $itemTrackNumberAndName['account_system_language_track_number'];
    $accountSystemLanguageInsert['fk_account_system_id'] = $account_system_id;
    $accountSystemLanguageInsert['fk_language_id'] = $language_id;
    $accountSystemLanguageInsert['account_system_language_created_by'] = $this->session->user_id;
    $accountSystemLanguageInsert['account_system_language_created_date'] = date('Y-m-d');
    $accountSystemLanguageInsert['account_system_language_last_modified_by'] = $this->session->user_id;
    $accountSystemLanguageInsert['account_system_language_last_modified_date'] = date('Y-m-d');
    $accountSystemLanguageInsert['fk_status_id'] = $statusLib->initialItemStatus('account_system_language');
    $accountSystemLanguageInsert['fk_approval_id'] = NULL;

    $accountSystemLanguageWriteBuilder->insert($accountSystemLanguageInsert);

    // Create language package file

    $this->createLanguageDirectoryStructure($language_id, $account_system_code);
  }

private function createLanguageDirectoryStructure($language_id, $account_system_code){
      $languageReadBuilder = $this->read_db->table("language");

      // 1. Get the language code
      $language_code = $languageReadBuilder->where(['language_id' => $language_id])->get()->getRowArray()['language_code'];

      // 2. Check if the language directory exists. Create the directory if not existing
      $language_dir = APPPATH.'Language'.DS.$language_code;
      if(!file_exists($language_dir)){
        mkdir($language_dir);
      }
  
      // 3. Check if the account system language directory exists in en
      $english_language_account_system_dir = APPPATH.'Language'.DS.'en'.DS.$account_system_code;
      if(!file_exists($english_language_account_system_dir)){
        mkdir($english_language_account_system_dir);
      }
  
      // 4. Check if the account system language directory exists in account system default language
      $default_language_account_system_dir = $language_dir.DS.$account_system_code;
      if(!file_exists($default_language_account_system_dir)){
        mkdir($default_language_account_system_dir);
      }
  
      $default_language_global_dir = $language_dir.DS.'Global';
      if(!file_exists($default_language_global_dir)){
        mkdir($default_language_global_dir);
      }
  
      // 5. Copy english language file from global to account system langage
      $engFromFile = APPPATH.'Language'.DS.'en'.DS.'Global'.DS.'App.php';
      $engToFile = APPPATH.'Language'.DS.'en'.DS.$account_system_code.DS.'App.php';
      copy($engFromFile, $engToFile);
  
      // 6. Copy english language file from global to english to default language of the new account system
      $defaultFromFile = APPPATH.'Language'.DS.'en'.DS.'Global'.DS.'App.php';
      $defaultToFile = APPPATH.'Language'.DS.$language_code.DS.$account_system_code.DS.'App.php';
      copy($defaultFromFile, $defaultToFile);
  
      $toFile = APPPATH.'Language'.DS.$language_code.DS.'Global'.DS.'App.php';
      copy($defaultFromFile, $toFile);
}

private function getRoleGroupNameById(int $roleGroupId): mixed
  {
    $builder = $this->read_db->table('role_group');
    $builder->select(['role_group_name']);
    $builder->where(['role_group_id' => $roleGroupId]);
    $roleGroupObj = $builder->get();
    if ($roleGroupObj->getNumRows() > 0) {
      return $roleGroupObj->getRow()->role_group_name;
    }
    return null;
  }

// private function getAccountSystemCode(int $account_system_id): mixed
//   {
//     $builder = $this->read_db->table('account_system');
//     $builder->select(['account_system_code']);
//     $builder->where(['account_system_id' => $account_system_id]);
//     $accountSystemObj = $builder->get();
//     if ($accountSystemObj->getNumRows() > 0) {
//       return $accountSystemObj->getRow()->account_system_code;
//     }
//     return null;
//   }

private function createRoleGroupAssociations(StatusLibrary $statusLib, int $account_system_id, array $rolesGroups, string $account_system_code, int $template_account_system): void
  {
    foreach ($rolesGroups as $rolesGroup) {

      // $rolesGroupArray = explode('_', $rolesGroup);
      $roleGroupId = $rolesGroup['role_group_id']; //$rolesGroupArray[0];
      $contextDefinitionId = $rolesGroup['fk_context_definition_id']; // $rolesGroupArray[1];

      $roleItemTrackNumberAndName = $this->generateItemTrackNumberAndName('role');

      $rolesInsert['role_name'] = $account_system_code . '-' . $this->getRoleGroupNameById($roleGroupId);//$roleItemTrackNumberAndName['role_name'];
      $rolesInsert['role_track_number'] = $roleItemTrackNumberAndName['role_track_number'];
      $rolesInsert['fk_context_definition_id'] = $contextDefinitionId;
      $rolesInsert['fk_account_system_id'] = $account_system_id;
      $rolesInsert['role_created_by'] = $this->session->user_id;
      $rolesInsert['role_created_date'] = date('Y-m-d');
      $rolesInsert['role_last_modified_date'] = date('Y-m-d');
      $rolesInsert['role_last_modified_by'] = $this->session->user_id;
      $rolesInsert['fk_status_id'] = 1;
      $rolesInsert['fk_approval_id'] = 0;

      $rolesWriteBuilder = $this->write_db->table('role');
      $rolesWriteBuilder->insert($rolesInsert);
      // Get the role insert id 
      $roleId = $this->write_db->insertId();

      $roleItemTrackNumberAndName = $this->generateItemTrackNumberAndName('role_group_association');

      $rolesGroupAssociationInsert['role_group_association_name'] = $roleItemTrackNumberAndName['role_group_association_name'];
      $rolesGroupAssociationInsert['role_group_association_track_number'] = $roleItemTrackNumberAndName['role_group_association_track_number'];
      $rolesGroupAssociationInsert['fk_role_group_id'] = $roleGroupId;
      $rolesGroupAssociationInsert['fk_role_id'] = $roleId;
      $rolesGroupAssociationInsert['role_group_association_is_active'] = '';
      $rolesGroupAssociationInsert['role_group_association_created_date'] = '';
      $rolesGroupAssociationInsert['role_group_association_created_by'] = '';
      $rolesGroupAssociationInsert['role_group_association_last_modified_date'] = '';
      $rolesGroupAssociationInsert['role_group_association_last_modified_by'] = '';
      $rolesGroupAssociationInsert['fk_status_id'] = $statusLib->initialItemStatus('role_group_association');
      $rolesGroupAssociationInsert['fk_approval_id'] = 0;

      $rolesGroupAssociationWriteBuilder = $this->write_db->table('role_group_association');
      $rolesGroupAssociationWriteBuilder->insert($rolesGroupAssociationInsert);

    }
  }

private function createIncomeAccount(StatusLibrary $statusLib, array $globalIncomeAccount, int $account_system_id, string $account_system_code): int
  {
    $incomeAccountWriteBuilder = $this->write_db->table('income_account');
    $incomeAccountTrackNumberAndName = $this->generateItemTrackNumberAndName('income_account');
    // Create a new Income Account for the Account System
    $incomeAccountInsert['income_account_name'] = $account_system_code . '-' . $globalIncomeAccount['income_account_name'];
    $incomeAccountInsert['income_account_track_number'] = $incomeAccountTrackNumberAndName['income_account_track_number'];
    $incomeAccountInsert['income_account_description'] = $globalIncomeAccount['income_account_description'];
    $incomeAccountInsert['income_account_code'] = $account_system_code . $globalIncomeAccount['income_account_code'];
    $incomeAccountInsert['income_account_is_active'] = 1;
    $incomeAccountInsert['fk_income_vote_heads_category_id'] = $globalIncomeAccount['fk_income_vote_heads_category_id'];
    $incomeAccountInsert['income_account_is_budgeted'] = $globalIncomeAccount['income_account_is_budgeted'];
    $incomeAccountInsert['income_account_is_donor_funded'] = $globalIncomeAccount['income_account_is_donor_funded'];
    $incomeAccountInsert['fk_account_system_id'] = $account_system_id;
    $incomeAccountInsert['income_account_created_date'] = date('Y-m-d');
    $incomeAccountInsert['income_account_created_by'] = $this->session->user_id;
    $incomeAccountInsert['income_account_last_modified_date'] = date('Y-m-d');
    $incomeAccountInsert['income_account_last_modified_by'] = $this->session->user_id;
    $incomeAccountInsert['fk_status_id'] = $statusLib->initialItemStatus('income_account');
    $incomeAccountInsert['fk_approval_id'] = 0;

    $incomeAccountWriteBuilder->insert($incomeAccountInsert);
    return $this->write_db->insertID();
  }

private function createExpenseAccount(StatusLibrary $statusLib, array $globalExpenseAccount, int $incomeAccountId, string $account_system_code): void
  {

    $expenseAccountWriteBuilder = $this->write_db->table('expense_account');
    $expenseAccountTrackNumberAndName = $this->generateItemTrackNumberAndName('expense_account');

    $expenseAccountInsert['expense_account_name'] = $account_system_code . '-' . $globalExpenseAccount['expense_account_name'];
    $expenseAccountInsert['expense_account_track_number'] = $expenseAccountTrackNumberAndName['expense_account_track_number'];
    $expenseAccountInsert['expense_account_description'] = $globalExpenseAccount['expense_account_description'];
    $expenseAccountInsert['expense_account_code'] = $account_system_code . $globalExpenseAccount['expense_account_code'];
    $expenseAccountInsert['expense_account_is_admin'] = $globalExpenseAccount['expense_account_is_admin'];
    $expenseAccountInsert['fk_expense_vote_heads_category_id'] = $globalExpenseAccount['fk_expense_vote_heads_category_id'];
    $expenseAccountInsert['expense_account_is_medical_rembursable'] = $globalExpenseAccount['expense_account_is_medical_rembursable'];
    $expenseAccountInsert['expense_account_is_active'] = 1;
    $expenseAccountInsert['expense_account_is_budgeted'] = $globalExpenseAccount['expense_account_is_budgeted'];
    $expenseAccountInsert['fk_income_account_id'] = $incomeAccountId;
    $expenseAccountInsert['expense_account_created_date'] = date('Y-m-d');
    $expenseAccountInsert['expense_account_created_by'] = $this->session->user_id;
    $expenseAccountInsert['expense_account_last_modified_date'] = date('Y-m-d');
    $expenseAccountInsert['expense_account_last_modified_by'] = $this->session->user_id;
    $expenseAccountInsert['fk_status_id'] = $statusLib->initialItemStatus('expense_account');
    $expenseAccountInsert['fk_approval_id'] = 0;

    $expenseAccountWriteBuilder->insert($expenseAccountInsert);
  }

private function createFunder(StatusLibrary $statusLib, int $account_system_id, string $account_system_code): int
  {
    $funderWriteBuilder = $this->write_db->table('funder');
    $funderTrackNumberAndName = $this->generateItemTrackNumberAndName('funder');

    $funderInsert['funder_name'] = get_phrase('organisation_name', 'Compassion International') . '-' . $account_system_code;
    $funderInsert['funder_track_number'] = $funderTrackNumberAndName['funder_track_number'];
    $funderInsert['funder_description'] = get_phrase('organisation_name', 'Compassion International');
    $funderInsert['fk_account_system_id'] = $account_system_id;
    $funderInsert['funder_created_date'] = date('Y-m-d');
    $funderInsert['funder_created_by'] = $this->session->user_id;
    $funderInsert['funder_last_modified_date'] = date('Y-m-d');
    $funderInsert['funder_last_modified_by'] = $this->session->user_id;
    $funderInsert['fk_status_id'] = $statusLib->initialItemStatus('funder');
    $funderInsert['fk_approval_id'] = 0;
    $funderWriteBuilder->insert($funderInsert);

    return $this->write_db->insertID();
  }

private function createProject(StatusLibrary $statusLib, string $account_system_code, array $accountSystemIncomeAccount, int $funderId, int $fundingStatusId, string $default_project_start_date): void
  {

    $projectWriteBuilder = $this->write_db->table('project');
    $projectTrackNumberAndName = $this->generateItemTrackNumberAndName('project');


    $income_account_code = $accountSystemIncomeAccount['income_account_code'];
    $projectInitialCode = $account_system_code . 'P';
    $incomeAccountSystemRevenueInitialCode = substr($income_account_code, 0, 3);
    $projectCode = str_replace($incomeAccountSystemRevenueInitialCode, $projectInitialCode, $income_account_code);

    $projectInsert['project_name'] = $projectCode . '-' . $accountSystemIncomeAccount['income_account_name'];
    $projectInsert['project_track_number'] = $projectTrackNumberAndName['project_track_number'];
    $projectInsert['project_code'] = $projectCode;
    $projectInsert['project_description'] = $projectCode . '-' . $accountSystemIncomeAccount['income_account_name'];
    $projectInsert['project_start_date'] = $default_project_start_date;
    $projectInsert['project_end_date'] = NULL;
    $projectInsert['fk_funder_id'] = $funderId;
    $projectInsert['fk_funding_status_id'] = $fundingStatusId;
    $projectInsert['project_is_default'] = 1;
    $projectInsert['project_created_by'] = $this->session->user_id;
    $projectInsert['project_created_date'] = date('Y-m-d');
    $projectInsert['project_last_modified_by'] = $this->session->user_id;
    $projectInsert['project_last_modified_date'] = date('Y-m-d');
    $projectInsert['fk_status_id'] = $statusLib->initialItemStatus('project');
    $projectInsert['fk_approval_id'] = 0;

    $projectWriteBuilder->insert($projectInsert);
    
    $projectId = $this->write_db->insertID();

    $this->createProjectIncomeAccount($statusLib, $projectId, $accountSystemIncomeAccount['income_account_id']);

  }

private function createProjectIncomeAccount(StatusLibrary $statusLib, int $projectId, int $incomeAccountId): void
  {
    $projectIncomeAccountWriteBuilder = $this->write_db->table('project_income_account'); //
    $projectIncomeAccountTrackNumberAndName = $this->generateItemTrackNumberAndName('project_income_account');

    $projectIncomeAccountInsert['project_income_account_name'] = $projectIncomeAccountTrackNumberAndName['project_income_account_name'];
    $projectIncomeAccountInsert['project_income_account_track_number'] = $projectIncomeAccountTrackNumberAndName['project_income_account_track_number'];
    $projectIncomeAccountInsert['fk_project_id'] = $projectId;
    $projectIncomeAccountInsert['fk_income_account_id'] = $incomeAccountId;
    $projectIncomeAccountInsert['project_income_account_created_date'] = date('Y-m-d');
    $projectIncomeAccountInsert['project_income_account_last_modified_date'] = date('Y-m-d');
    $projectIncomeAccountInsert['project_income_account_created_by'] = $this->session->user_id;
    $projectIncomeAccountInsert['project_income_account_last_modified_by'] = $this->session->user_id;
    $projectIncomeAccountInsert['fk_status_id'] = $statusLib->initialItemStatus('project_income_account');
    $projectIncomeAccountInsert['fk_approval_id'] = 0;

    $projectIncomeAccountWriteBuilder->insert($projectIncomeAccountInsert);
  }

private function copyGlobalAccountsToNewAccountSystem(StatusLibrary $statusLib, int $account_system_id, string $account_system_code, $template_account_system_id): void
  {
    $incomeAccountReadBuilder = $this->read_db->table('income_account');
    $expenseAccountReadBuilder = $this->read_db->table('expense_account');

    // Get all the Global Income Accounts
    $incomeAccountReadBuilder->where(['income_account.fk_account_system_id' => $template_account_system_id]); // Replace 1 with Global Income Account System ID
    $globalIncomeAccounts = $incomeAccountReadBuilder->get();

    if ($globalIncomeAccounts->getNumRows() > 0) {
      foreach ($globalIncomeAccounts->getResultArray() as $globalIncomeAccount) {

        // Create a new Income Account
        $incomeAccountId = $this->createIncomeAccount($statusLib, $globalIncomeAccount, $account_system_id, $account_system_code);

        $expenseAccountReadBuilder->where(['fk_income_account_id' => $globalIncomeAccount['income_account_id']]);
        $globalExpenseAccounts = $expenseAccountReadBuilder->get();
        if ($globalExpenseAccounts->getNumRows() > 0) {
          foreach ($globalExpenseAccounts->getResultArray() as $globalExpenseAccount) {
            // Create a new Expense Account for the Account System
            $this->createExpenseAccount($statusLib, $globalExpenseAccount, $incomeAccountId, $account_system_code);
          }
        }
      }
    }
  }

private function createProjectAndIncomeAccountAssociation(StatusLibrary $statusLib, int $account_system_id, string $account_system_code, string $default_project_start_date): void
  {
    $incomeAccountWriteBuilder = $this->write_db->table('income_account'); // Only works if a write builder

    // Create a funder 
    $funderId = $this->createFunder($statusLib, $account_system_id, $account_system_code);

    // Creating funding status
    $fundingStatusId = $this->createFundingStatus($statusLib, $account_system_id);

    // Create Projects, Allocation and Income Account Associations
    $incomeAccountWriteBuilder->where(['income_account.fk_account_system_id' => $account_system_id, 'income_account_is_donor_funded' => 0]);
    $accountSystemIncomeAccounts = $incomeAccountWriteBuilder->get();

    if ($accountSystemIncomeAccounts->getNumRows() > 0) {
      foreach ($accountSystemIncomeAccounts->getResultArray() as $accountSystemIncomeAccount) {
        $this->createProject($statusLib,$account_system_code, $accountSystemIncomeAccount, $funderId, $fundingStatusId, $default_project_start_date);
      }
    }
  }

private function createFundingStatus($statusLib, $account_system_id): int
  {
    $fundingStatusWriterBuilder = $this->write_db->table('funding_status');
    $fundingStatusTrackNumberAndName = $this->generateItemTrackNumberAndName('funding_status');
    // Create a new funding status
    $fundingStatusInsert['funding_status_track_number'] = $fundingStatusTrackNumberAndName['funding_status_track_number'];
    $fundingStatusInsert['funding_status_name'] = get_phrase('Fully Funded');
    $fundingStatusInsert['funding_status_is_active'] = 1;
    $fundingStatusInsert['fk_account_system_id'] = $account_system_id;
    $fundingStatusInsert['funding_status_created_date'] = date('Y-m-d');
    $fundingStatusInsert['funding_status_created_by'] = $this->session->user_id;
    $fundingStatusInsert['funding_status_last_modified_date'] = date('Y-m-d');
    $fundingStatusInsert['funding_status_last_modified_by'] = $this->session->user_id;
    $fundingStatusInsert['fk_status_id'] = $statusLib->initialItemStatus('funder');
    $fundingStatusInsert['fk_approval_id'] = 0;

    $fundingStatusWriterBuilder->insert($fundingStatusInsert);

    return $this->write_db->insertID();
}

private function createOfficeAndContext($statusLib, $header_id, $account_system_code, $country_currency_id, $template_account_system, $account_system_start_date, $hierarchy_level){
    $officeWriterBuilder = $this->write_db->table('office');
    $contextOfficeWriterBuilder = $this->write_db->table($hierarchy_level == 4 ? 'context_country' : 'context_region');
    $contextReportingOfficeReaderBuilder = $this->write_db->table($hierarchy_level == 4 ? 'context_region' : 'context_global');


    // Create National Office
    $officeName = $hierarchy_level == 4 ? $account_system_code.'-'.get_phrase('national_office') : $account_system_code.'-'.get_phrase('regiona_office');

    $officeTrackNumberAndName = $this->generateItemTrackNumberAndName('office');
    $officeInsert['office_track_number'] = $officeTrackNumberAndName['office_track_number'];
    $officeInsert['office_name'] = $officeName;
    $officeInsert['fk_account_system_id'] = $header_id;
    $officeInsert['office_description'] = $officeName;
    $officeInsert['office_code'] = $account_system_code;
    $officeInsert['fk_context_definition_id'] = $hierarchy_level;
    $officeInsert['office_start_date'] = $account_system_start_date;
    $officeInsert['office_end_date'] = NULL;
    $officeInsert['office_is_active'] = 1;
    $officeInsert['office_is_suspended'] = 0;
    $officeInsert['office_is_readonly'] = 1;
    $officeInsert['fk_country_currency_id'] = $country_currency_id;
    $officeInsert['office_created_date'] = date('Y-m-d');
    $officeInsert['office_created_by'] = $this->session->user_id;
    $officeInsert['office_last_modified_date'] = date('Y-m-d');
    $officeInsert['office_last_modified_by'] = $this->session->user_id;
    $officeInsert['fk_status_id'] = $statusLib->initialItemStatus('office');
    $officeInsert['fk_approval_id'] = 0;

    $officeWriterBuilder->insert($officeInsert);
    $officeId = $this->write_db->insertID();

    $reportingContextId = null;
    // Get Context Region

    $joinTable = $hierarchy_level == 4 ? 'context_region': 'context_global';
    $contextReportingOfficeReaderBuilder->select([$hierarchy_level == 4 ? 'context_region_id' : 'context_global_id']);
    $contextReportingOfficeReaderBuilder->where(['office.fk_account_system_id' => $template_account_system, 'office.fk_context_definition_id' => $hierarchy_level + 1]);
    $contextReportingOfficeReaderBuilder->join('office','office.office_id='.$joinTable.'.fk_office_id'); // context_region
    $reportingContextObj = $contextReportingOfficeReaderBuilder->get();

    if($reportingContextObj->getNumRows() > 0){
      $row = $reportingContextObj->getRow();
      $reportingContextId = $hierarchy_level == 4 ? $row->context_region_id : $row->context_global_id;
      // Create Context Office
      $contextTable = $hierarchy_level == 4 ? 'context_country' : 'context_region';
      $itemTrackNumberAndName = $this->generateItemTrackNumberAndName($hierarchy_level == 4 ? 'context_country' : 'context_region');
      $contextOfficeInsert[$contextTable.'_track_number'] = $itemTrackNumberAndName[$contextTable.'_track_number'];
      $contextOfficeInsert[$contextTable.'_name'] = get_phrase('context_for_office').' '.$officeName;
      $contextOfficeInsert[$contextTable.'_description'] = get_phrase('context_for_office').' '.$officeName;
      $contextOfficeInsert['fk_office_id	'] = $officeId;
      $contextOfficeInsert['fk_context_definition_id	'] = $hierarchy_level;
      $contextOfficeInsert[$hierarchy_level == 4 ? 'fk_context_region_id' : 'fk_context_global_id'] = $reportingContextId;
      $contextOfficeInsert[$contextTable.'_created_date'] = date('Y-m-d');
      $contextOfficeInsert[$contextTable.'_created_by'] = $this->session->user_id;
      $contextOfficeInsert[$contextTable.'_last_modified_date	'] = date('Y-m-d');
      $contextOfficeInsert[$contextTable.'_last_modified_by	'] = $this->session->user_id;
      $contextOfficeInsert['fk_status_id	'] = $statusLib->initialItemStatus($contextTable);
      $contextOfficeInsert['fk_approval_id	'] = 0;
  
      $contextOfficeWriterBuilder->insert($contextOfficeInsert);

    }
    

}

function getAccountSystemRoleGroups(int $accountSystemId): array{
    // Get Role Groups
    $roleGroupBuilder = $this->read_db->table('role_group');
    $roleGroupBuilder->select(['role_group_id', 'role_group_name', 'fk_context_definition_id']);
    $roleGroupBuilder->where(['role_group_is_active' => 1, 'role_group.fk_account_system_id' => $accountSystemId, 'fk_context_definition_id<' => 5]);
    $roleGroupsObj = $roleGroupBuilder->get();

    $roleGroups = [];

    if($roleGroupsObj->getNumRows() > 0){
      $roleGroups = $roleGroupsObj->getResultArray();
    }

    return $roleGroups;
}

private function createVoucherTypes($statusLib, $account_system_id, $account_system_code, $template_account_system){
  $voucherTypeReadBuilder = $this->read_db->table('voucher_type');
  $voucherTypeWriteBuilder = $this->write_db->table('voucher_type');
  $voucherTypeLibrary = new \App\Libraries\Grants\VoucherTypeLibrary();
  
  // 1. Get the voucher type records from the template accounting system
  $voucherTypeReadBuilder->where(['fk_account_system_id' => $template_account_system]);
  $resultObj = $voucherTypeReadBuilder->get();

  if($resultObj->getNumRows() > 0){
    $voucherTypesToCopy = $resultObj->getResultArray();

    $voucherTypesData = [];

    for($i =0; $i < count($voucherTypesToCopy); $i++){
      $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('voucher_type');
      $data['voucher_type_track_number'] = $itemTrackNumberAndName['voucher_type_track_number'];
      $data['voucher_type_name'] = strtoupper($account_system_code). '-' .$voucherTypesToCopy[$i]['voucher_type_name'];
      $data['voucher_type_abbrev'] = strtoupper($account_system_code).$voucherTypesToCopy[$i]['voucher_type_abbrev'];
      $data['fk_account_system_id'] = $account_system_id;
      $data['voucher_type_created_by'] = $this->session->user_id;
      $data['voucher_type_created_date'] = date('Y-m-d');
      $data['voucher_type_last_modified_by'] = $this->session->user_id;
      $data['voucher_type_last_modified_date'] = date('Y-m-d');
      $data['fk_approval_id'] = 0;
      $data['fk_status_id'] = $statusLib->initialItemStatus('voucher_type');

      unset($voucherTypesToCopy[$i]['voucher_type_id']);
      $voucherTypesData[$i] = array_replace($voucherTypesToCopy[$i], $data);
    }

    if(count($voucherTypesData) > 0){
      $voucherTypeWriteBuilder->insertBatch($voucherTypesData);
    }

    // Create hidden voucher types for Funds Transfera dn Voided Cheques
    $voucherTypeLibrary->createHiddenVoucherTypes($account_system_id, $account_system_code);

  }

}

private function createOfficeCash($statusLib, $account_system_id, $account_system_code, $template_account_system){
  $officeCashReadBuilder = $this->read_db->table('office_cash');
  $officeWriteBuilder = $this->write_db->table('office_cash');
  
  // 1. Get the office cash records from the template accounting system
  $officeCashReadBuilder->where(['fk_account_system_id' => $template_account_system]);
  $resultObj = $officeCashReadBuilder->get();

  if($resultObj->getNumRows() > 0){
    $officeCashToCopy = $resultObj->getResultArray();

    $officeCashData = [];

    for($i =0; $i < count($officeCashToCopy); $i++){
      $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('office_cash');
      $data['office_cash_track_number'] = $itemTrackNumberAndName['office_cash_track_number'];
      $data['office_cash_name'] = strtoupper($account_system_code). '-' .$officeCashToCopy[$i]['office_cash_name'];
      $data['fk_account_system_id'] = $account_system_id;
      $data['office_cash_created_by'] = $this->session->user_id;
      $data['office_cash_created_date'] = date('Y-m-d');
      $data['office_cash_last_modified_by'] = $this->session->user_id;
      $data['office_cash_last_modified_date'] = date('Y-m-d');
      $data['fk_approval_id'] = 0;
      $data['fk_status_id'] = $statusLib->initialItemStatus('office_cash');

      unset($officeCashToCopy[$i]['office_cash_id']);
      $officeCashData[$i] = array_replace($officeCashToCopy[$i], $data);
    }

    if(count($officeCashData) > 0){
      $officeWriteBuilder->insertBatch($officeCashData);
    }

  }
}

private function createAccountSystemRoles($statusLib, $account_system_id, $account_system_code, $template_account_system, $rolesGroups, $account_system_level){
  $roleReadBuilder = $this->read_db->table('role');
  $roleWriteBuilder = $this->write_db->table('role');
  $rolePermissionReadBuilder = $this->write_db->table('role_permission');

  // 1. Get the office cash records from the template accounting system
  $roleReadBuilder->where(['fk_account_system_id' => $template_account_system]);
  if($account_system_level == 4){
    $roleReadBuilder->where(['fk_context_definition_id <=' => $account_system_level]);
  }
  $resultObj = $roleReadBuilder->get();

  if($resultObj->getNumRows() > 0){
    $roleToCopy = $resultObj->getResultArray();

    // Get role permissions
    $oldlRoleIds = array_column($roleToCopy,'role_id');
    $rolePermissionReadBuilder->select(['fk_role_id','fk_permission_id'])->whereIn('fk_role_id',$oldlRoleIds);
    $rolePermissionsObj = $rolePermissionReadBuilder->get();
    
    $rolePermissions = [];
    
    if($rolePermissionsObj->getNumRows() > 0){
      $rolePermissionsArray = $rolePermissionsObj->getResultArray();
      
      foreach($rolePermissionsArray as $rolePermission){
        $rolePermissions[$rolePermission['fk_role_id']][] = $rolePermission['fk_permission_id'];
      }
    }

    for($i =0; $i < count($roleToCopy); $i++){
      $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('role');
      $data['role_track_number'] = $itemTrackNumberAndName['role_track_number'];
      $data['role_name'] = strtoupper($account_system_code). '-' .$roleToCopy[$i]['role_name'];
      $data['fk_account_system_id'] = $account_system_id;
      $data['fk_context_definition_id'] = $roleToCopy[$i]['fk_context_definition_id'];
      $data['role_created_by'] = $this->session->user_id;
      $data['role_created_date'] = date('Y-m-d');
      $data['role_last_modified_by'] = $this->session->user_id;
      $data['role_last_modified_date'] = date('Y-m-d');
      $data['fk_approval_id'] = 0;
      $data['role_template_id'] = $roleToCopy[$i]['role_id'];
      $data['fk_status_id'] = $statusLib->initialItemStatus('role');

      $_oldRoleId = $roleToCopy[$i]['role_id']; 
      unset($roleToCopy[$i]['role_id']);

      $roleWriteBuilder->insert($data);
      $newRoleId = $this->write_db->insertID();

      $permissionIdsToAttach = isset($rolePermissions[$_oldRoleId]) ? $rolePermissions[$_oldRoleId] : 0;
      
      if(count($permissionIdsToAttach) > 0){
        $this->insertPermissionsToRole($statusLib, $newRoleId, $permissionIdsToAttach);
      }

      // Check if old role has a role group association and attach
      $roleGroupAssociationReadBuilder = $this->read_db->table('role_group_association');
      $roleGroupAssociationWriteBuilder = $this->write_db->table('role_group_association');

      $roleGroupAssociationReadBuilder->where(['fk_role_id' => $_oldRoleId]);
      $roleGroupAssocObj = $roleGroupAssociationReadBuilder->get();

      if($roleGroupAssocObj->getNumRows() > 0){
        $roleGroupAssoc = $roleGroupAssocObj->getResultArray();

        foreach($roleGroupAssoc as $row){
          $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('role_group_association');       
          $assocData['role_group_association_name'] = $itemTrackNumberAndName['role_group_association_name'];
          $assocData['role_group_association_track_number'] = $itemTrackNumberAndName['role_group_association_track_number'];
          $assocData['fk_role_group_id'] = $row['fk_role_group_id'];
          $assocData['fk_role_id'] = $newRoleId ;
          $assocData['role_group_association_is_active'] = $row['role_group_association_is_active'];
          $assocData['role_group_association_created_date'] = date('Y-m-d');
          $assocData['role_group_association_created_by'] = $this->session->user_id;
          $assocData['role_group_association_last_modified_date'] = date('Y-m-d');
          $assocData['role_group_association_last_modified_by'] = $this->session->user_id;
          $assocData['fk_status_id'] = $statusLib->initialItemStatus('role_group_association');
          $assocData['fk_approval_id'] = 0;

          $roleGroupAssociationWriteBuilder->insert($assocData);
        }
      }

    }

  }else{
        $this->createRoleGroupAssociations($statusLib, $account_system_id, $rolesGroups, $account_system_code, $template_account_system);
  }
}

private function insertPermissionsToRole($statusLib, $roleId, $permissionIds){
  $rolePermissionWriteBuilder = $this->write_db->table('role_permission');
  $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('role_permission');

  $data  = [];

  for($i = 0; $i < count($permissionIds); $i++){
    $data[$i]['role_permission_track_number'] = $itemTrackNumberAndName['role_permission_track_number'];
    $data[$i]['role_permission_name'] = $itemTrackNumberAndName['role_permission_name'];
    $data[$i]['role_permission_is_active'] = 1;
    $data[$i]['fk_role_id'] = $roleId;
    $data[$i]['fk_permission_id'] = $permissionIds[$i];
    $data[$i]['fk_approval_id'] = 0;
    $data[$i]['fk_status_id'] = $statusLib->initialItemStatus('role');
    $data[$i]['role_permission_created_date'] = date('Y-m-d');
    $data[$i]['role_permission_created_by'] = $this->session->user_id;
    $data[$i]['role_permission_last_modified_date'] = date('Y-m-d');
    $data[$i]['role_permission_last_modified_by'] = $this->session->user_id;
  }
  

  $rolePermissionWriteBuilder->insertBatch($data);

}

private function createApprovalFlow($statusLib, $account_system_id, $account_system_code, $template_account_system){
  
  // Query builders
  $approvalFlowReadBuilder = $this->read_db->table('approval_flow');
  $statusWriteBuilder = $this->write_db->table('status');
  $accountSystemWriteBuilder = $this->write_db->table('account_system'); // Must be in write_db
  $statusRoleWriteBuilder = $this->write_db->table('status_role');

  // Approval flow library object
  $approvalFlowLibrary = new \App\Libraries\Core\ApprovalFlowLibrary();

  // Creating user Id
  $user_id = $this->session->user_id;

  // The newly created accounting system  
  $account_system = $accountSystemWriteBuilder->where(['account_system_id' => $account_system_id])
  ->get()->getRowArray();

  // Selected columns
  $selectedColumns = [
    'fk_approve_item_id',
    'approve_item_name',
    'status_id',
    'status_name',
    'status_button_label',
    'status_decline_button_label',
    'status_signatory_label',
    'fk_approval_flow_id',
    'status_approval_sequence',
    'status_backflow_sequence',
    'status_approval_direction',
    'status_is_requiring_approver_action',
    'fk_role_id'
  ];

  // Get templating account system approval flow and status
  $approvalFlowReadBuilder->where(['fk_account_system_id' => $template_account_system]);
  $approvalFlowReadBuilder->select($selectedColumns);
  $approvalFlowReadBuilder->join('status','status.fk_approval_flow_id=approval_flow.approval_flow_id');
  $approvalFlowReadBuilder->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
  $approvalFlowReadBuilder->join('status_role','status.status_id=status_role.status_role_status_id','LEFT');
  $templateApprovalFlowStatus = $approvalFlowReadBuilder->get();


  // Check if the template approval flow and status is present
  if($templateApprovalFlowStatus->getNumRows() > 0){
     // Array to store the template accounting system approval flow
    $template = [];
    $approve_item = [];
    $templateApprovalStatus = $templateApprovalFlowStatus->getResultArray();

    foreach($templateApprovalStatus as $templateStatus){
      $approve_item_id = $templateStatus['fk_approve_item_id'];
      $approve_item_name = $templateStatus['approve_item_name'];
      $status_id = $templateStatus['status_id'];
      $approve_item[$approve_item_id] = $approve_item_name;
      
      unset($templateStatus['fk_approve_item_id']);
      unset($templateStatus['approve_item_name']);
      unset($templateStatus['status_id']);

      $template[$approve_item_id]['status'][$status_id] = $templateStatus;

      if(isset($templateStatus['fk_role_id']) && $templateStatus['fk_role_id'] > 0){
        $template[$approve_item_id]['status_role'][$status_id][] = $templateStatus['fk_role_id'];
      }
       
    }

    foreach($template as $approveItemId => $statuses){
        
      $approvalFlowId = $approvalFlowLibrary->insertApprovalFlow($account_system, $approveItemId, $approve_item[$approveItemId], $user_id);

      $statusData = [];
      foreach($statuses['status'] as $oldStatusId => $status){
        // Create statuses
        $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('status');
        $statusData['status_name'] = $status['status_name'];
        $statusData['status_track_number'] =  $itemTrackNumberAndName['status_track_number'];
        $statusData['status_button_label'] = $status['status_button_label'];
        $statusData['status_decline_button_label'] = $status['status_decline_button_label'];
        $statusData['status_signatory_label'] = $status['status_signatory_label'];
        $statusData['fk_approval_flow_id'] = $approvalFlowId;
        $statusData['status_approval_sequence'] = $status['status_approval_sequence'];
        $statusData['status_backflow_sequence'] = $status['status_backflow_sequence'];
        $statusData['status_approval_direction'] = $status['status_approval_direction'];
        $statusData['status_is_requiring_approver_action'] = $status['status_is_requiring_approver_action'];
        $statusData['status_created_date'] = date('Y-m-d');
        $statusData['status_created_by'] = $user_id;
        $statusData['status_last_modified_date'] = date('Y-m-d');
        $statusData['status_last_modified_by'] = $user_id;

        $statusWriteBuilder->insert($statusData);
        $statusId = $this->write_db->insertID();

        if(isset($template[$approveItemId]['status_role']) && isset($template[$approveItemId]['status_role'][$oldStatusId])) {
          $statusRoles = $template[$approveItemId]['status_role'][$oldStatusId];

          if(!empty($statusRoles)){
            $statusRole = [];
            
            for($i = 0; $i < count($statusRoles); $i++){
                $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('status_role');
                $statusRole[$i]['status_role_track_number'] = $itemTrackNumberAndName['status_role_track_number'];
                $statusRole[$i]['status_role_name'] =  $status['status_name'];
                $statusRole[$i]['fk_role_id'] = $this->findRelatedAccountingSystemRole($statusRoles[$i], $account_system_id);// $statusRoles[$i];
                $statusRole[$i]['fk_status_id'] = $statusLib->initialItemStatus('status_role');
                $statusRole[$i]['status_role_status_id'] = $statusId;
                $statusRole[$i]['status_role_is_active'] = 1;
                $statusRole[$i]['status_role_created_by'] = $user_id;
                $statusRole[$i]['status_role_created_date'] = date('Y-m-d');
                $statusRole[$i]['status_role_last_modified_by'] = $user_id;
                $statusRole[$i]['status_role_last_modified_date'] = date('Y-m-d');
                $statusRole[$i]['fk_approval_id'] = 0;
            }
            $statusRoleWriteBuilder->insertBatch($statusRole);
          }
        }
      }
    }
  }
}

private function findRelatedAccountingSystemRole($oldRoleId, $account_system_id): int{
  // $roleReadBuilder = $this->read_db->table('role');
  $roleWriteBuilder = $this->write_db->table('role');

  $roleWriteBuilder->where(['role_template_id' => $oldRoleId, 'fk_account_system_id' => $account_system_id]);
  $newRoleObj = $roleWriteBuilder->get();

  $newRoleId = 0;

  if($newRoleObj->getNumRows() > 0){
    $newRoleId = $newRoleObj->getRowArray()['role_id'];
  }

  return $newRoleId;
}

public function actionAfterInsert($post_array, $approval_id, $header_id): bool
  {
    $state = true;

    $account_system_settings = isset($post_array['account_system_settings']) ?  $post_array['account_system_settings'] : [];
    $language_id = $post_array['fk_language_id'];
    $default_project_start_date = isset($post_array['default_project_start_date']) ? $post_array['default_project_start_date'] : null;
    $template_account_system = $post_array['template_account_system'];
    $account_system_code = $post_array['account_system_code'];
    $account_system_level = $post_array['account_system_level'];
    $currency_code_and_name = $post_array['fk_country_currency_id'];
    $statusLib = new \App\Libraries\Core\StatusLibrary();
    
    $rolesGroups = $this->getAccountSystemRoleGroups($template_account_system);
    
    // Insert a record in the country currency table
    $countryCurrenyId = $this->createCountryCurrency($statusLib, $header_id, $currency_code_and_name);

    // Update the account system settings account_system_setting_accounts json with $header_id with set 
    // Only valid for country based accounting systems
    if($account_system_level == 4){
      $this->updateAccountSystemSettings($account_system_settings, $header_id);
    }

    // Insert account_system_language record
    $this->createAccountSystemLanguage($statusLib, $header_id, $language_id, $account_system_code);

    // Add Account System Roles 
    $this->createAccountSystemRoles($statusLib, $header_id, $account_system_code, $template_account_system, $rolesGroups, $account_system_level);
    // $this->createRoleGroupAssociations($statusLib, $header_id, $rolesGroups, $account_system_code, $template_account_system);
    
    // Copy Global Income and Expense Accounts to the New Account System
    $this->copyGlobalAccountsToNewAccountSystem($statusLib, $header_id, $account_system_code, $template_account_system);

    //Create Project and Project Income Account Association
    // Only applicable to country based accounting systems
    if($account_system_level == 4){
      $this->createProjectAndIncomeAccountAssociation($statusLib, $header_id, $account_system_code, $default_project_start_date);
    }
     // Create the National Office and Context
    $this->createOfficeAndContext($statusLib, $header_id, $account_system_code, $countryCurrenyId, $template_account_system, $default_project_start_date, $account_system_level);
  
    // Create voucher types
    $this->createVoucherTypes($statusLib, $header_id, $account_system_code, $template_account_system);

    // Create cash boxes
    $this->createOfficeCash($statusLib, $header_id, $account_system_code, $template_account_system);

    // Create approval workflow
    $this->createApprovalFlow($statusLib, $header_id, $account_system_code, $template_account_system);

    return $state;
  }

public function singleFormAddVisibleColumns(): array
  {
    return [
      'account_system_name',
      'account_system_code',
      'account_system_is_active',
      'account_system_level', // National Office Or Region
      'template_account_system',
      'account_system_settings',
      'language_name',
      'country_currency_name',
      'default_project_start_date'
    ];
  }


private function disableEnableFeature($tableName, $isBeingDeactivated, $accountSystemId, $fieldsToupdate = []){
  $writeBuilder = $this->write_db->table($tableName);

  $data = [];
  
  if(!empty($fieldsToupdate)){
    foreach($fieldsToupdate as $fieldName => $updateData){
      $data[$fieldName] = $isBeingDeactivated ? $updateData['onDeactivate'] : $updateData['onActivate'];
    }
  }

  if(!empty($data)){
    $writeBuilder->where(['fk_account_system_id' => $accountSystemId]);
    $writeBuilder->update($data);
  }
}

private function featuresDeactivationOrAction($postData, $accountSystemId){
    $isBeingDeactivated = $postData['account_system_is_active'] == 0 ? true : false;
    $activationField = ['onDeactivate' => 0, 'onActivate' => 1];

    $itemsToBeDeactivatedOrActivated = [
      'office' => ['office_end_date' => ['onDeactivate' => date('Y-m-d'), 'onActivate' => NULL],'office_is_active' => $activationField],
      'income_account' => ['income_account_is_active' => $activationField],
      'role' => ['role_is_active' => $activationField],
      'voucher_type' => ['voucher_type_is_active' => $activationField],
      'office_cash' => ['office_cash_is_active' => $activationField],
      'user' => ['user_is_active' => $activationField],
      'approval_flow' => ['approval_flow_is_active' => $activationField],
      'bank' => ['bank_is_active' => $activationField],
    ];

    foreach ($itemsToBeDeactivatedOrActivated as $tableName => $fieldsToUpdate) {
      $this->disableEnableFeature($tableName, $isBeingDeactivated, $accountSystemId, $fieldsToUpdate);
    }
}
  public function actionAfterEdit(array $postData, int $approveId, int $itemId): bool
  {
    $this->featuresDeactivationOrAction($postData, $itemId);
    return true;
  }

  function editVisibleColumns(): array {
    return [
      'account_system_name',
      'account_system_is_allocation_linked_to_account',
      'account_system_is_active'
    ];
  }

  function transactionValidateDuplicatesColumns(): array
  {
      return ['account_system_code'];
  }
}
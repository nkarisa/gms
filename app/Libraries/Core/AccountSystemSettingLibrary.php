<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\AccountSystemSettingModel;
use App\Enums\AccrualExpenseAccountCodes;
class AccountSystemSettingLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new AccountSystemSettingModel();

        $this->table = 'account_system_setting';
    }

    public function insertNewAccountSystemLanguage(int $languageId)
    {
        // Generate language data
        $generatedData = $this->generateItemTrackNumberAndName('account_system_language');

        $langData = [
            'account_system_language_name' => $generatedData['account_system_language_name'],
            'account_system_language_track_number' => $generatedData['account_system_language_track_number'],
            'fk_account_system_id' => $this->session->get('user_account_system_id'),
            'fk_language_id' => $languageId,
            'account_system_language_created_date' => date('Y-m-d'),
            'account_system_language_created_by' => $this->session->get('user_id'),
            'account_system_language_last_modified_by' => $this->session->get('user_id'),
            'fk_status_id' => $this->initialItemStatus('account_system_language')
        ];

        // Check if the language entry already exists
        $existingCount = $this->read_db->table('account_system_language')
            ->where('fk_account_system_id', $this->session->get('user_account_system_id'))
            ->where('fk_language_id', $languageId)
            ->countAllResults();

        // Insert only if the language entry does not exist
        if ($existingCount == 0) {
            $this->write_db->table('account_system_language')->insert($langData);
        }
    }

   
    function getAccountSystemSettings($account_system_id){
        $accountSystemSettingReadBuilder = $this->read_db->table('account_system_setting');
        $accountSystemSettingReadBuilder->select(array('account_system_setting_name as setting_name','account_system_setting_value as setting_value','account_system_setting_accounts'));
        $account_system_setting_obj = $accountSystemSettingReadBuilder->get();

        $account_system_setting = [];
    
        if($account_system_setting_obj->getNumRows() > 0){
          $account_system_setting_array = $account_system_setting_obj->getResultArray();
          
          foreach($account_system_setting_array as $settings){
            $account_systems = [];
            if($settings['account_system_setting_accounts'] != null){
              $account_systems = json_decode($settings['account_system_setting_accounts']);

              if(is_array($account_systems) && in_array($account_system_id, $account_systems)){
                $account_system_setting[$settings['setting_name']] = $settings['setting_value'];
               
                // break;
              }

            }
          }
        }
        return $account_system_setting;
      }

      public function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void {

      }

      public function changeFieldType(): array {
        $fields = [];

        $fields['account_system_setting_value']['field_type'] = 'select';
        $fields['account_system_setting_value']['options'] = [1 => get_phrase('on'), 0 => get_phrase('off')];

        $getAccountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();
        $accountSystems = $getAccountSystemLibrary->getAccountSystems();
        $ids = array_column($accountSystems, 'account_system_id');
        $codes = array_column($accountSystems, 'account_system_code');
        $fields['account_system_setting_accounts']['field_type'] = 'select';
        $fields['account_system_setting_accounts']['options'] = array_combine($ids, $codes);
        $fields['account_system_setting_accounts']['select2'] = true;

        return $fields;
      }
   
    public function formatColumnsValuesDependancyData(array $data): array {
      $accountSystems = [];
      $accountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();

      $accountSystems = $accountSystemLibrary->getAccountSystems();

      return compact('accountSystems');
    }

    public function formatColumnsValues(string $columnName, mixed $columnValue, array $rowArray, array $dependancyData = []): mixed {

      if($columnName == 'account_system_setting_accounts'){
        $accountSystems = $dependancyData['accountSystems'];

        if($columnValue != null && isValidJSONArray($columnValue)){
          
          $selectedAccountSystemIds = json_decode($columnValue);
          $selectedAccountSystemCode = [];

          foreach($accountSystems as $accountSystem){
            if(in_array($accountSystem->account_system_id, $selectedAccountSystemIds)){
              $selectedAccountSystemCode[] = strtoupper($accountSystem->account_system_code);
            }
          }

          $columnValue = implode(', ', $selectedAccountSystemCode);
        }
      }

      return $columnValue;
    }

    public function listTableVisibleColumns(): array {
      return [
        'account_system_setting_track_number',
        'account_system_setting_name',
        'account_system_setting_value',
        'account_system_setting_accounts',
        'account_system_setting_created_date',
        'account_system_setting_last_modified_date'
      ];
    }

    function singleFormAddVisibleColumns(): array
    {
      return [
        'account_system_setting_name',
        'account_system_setting_description',
        'account_system_setting_accounts',
        'account_system_setting_value'
      ];
    }

    public function actionAfterEdit(array $postData, int $approveId, int $itemId): bool{
      if($postData['account_system_setting_name'] == 'use_accrual_based_accounting'){

        $this->write_db->transStart();
        $account_system_ids = json_decode($postData['account_system_setting_accounts']);
        $this->createVoucherTypesAndExpenseAccountsForAccrual($account_system_ids);
        $this->write_db->transComplete();
      
        if($this->write_db->transStatus() == false){
          return false;
        }
      }
      return true;
    }

    public function createVoucherTypesAndExpenseAccountsForAccrual(array $account_system_ids){
        $voucherTypeLibrary = new \App\Libraries\Grants\VoucherTypeLibrary();

        // Create depreciation and payroll liability expense accounts in support funds
        $expenseAccountsLibrary = new \App\Libraries\Grants\ExpenseAccountLibrary();
        $depreciationExpenseAccountsGroupedByAccountSystemId = $expenseAccountsLibrary->createAccountSystemAccrualExpenseAccount($account_system_ids, AccrualExpenseAccountCodes::DEPRECIATION);
        $payrollLiabilityExpenseAccountsGroupedByAccountSystemId= $expenseAccountsLibrary->createAccountSystemAccrualExpenseAccount($account_system_ids, AccrualExpenseAccountCodes::PAYROLL);
        
        $expenseAccounts = [
          'depreciationExpenseAccounts' => $depreciationExpenseAccountsGroupedByAccountSystemId,
          'payrollLiabilityExpenseAccounts' => $payrollLiabilityExpenseAccountsGroupedByAccountSystemId
        ];
        // Create accrual vouchers
        $voucherTypeLibrary->createAccountingSystemAccrualVoucherTypes($account_system_ids, $expenseAccounts);
        
    }

    public function getAccountSystemSettingsIds(\App\Enums\AccountSystemSettingEnum $accountSystemSetting){
        $accountSystemSettingbuilder = $this->read_db->table('account_system_setting');
        $accountSystemSettingbuilder->select('account_system_setting_accounts');
        $accountSystemSettingbuilder->where('account_system_setting_name', $accountSystemSetting->value);
        $accountSystemSettingbuilder->where('account_system_setting_value', '1');
        $accountSystemSettingObj = $accountSystemSettingbuilder->get();

        $accountSystemIds = [];

        if($accountSystemSettingObj->getNumRows() > 0){
            $settings = $accountSystemSettingObj->getRowArray();
            $accountSystemIds = json_decode($settings['account_system_setting_accounts']);
        }

        return $accountSystemIds;
    }
}
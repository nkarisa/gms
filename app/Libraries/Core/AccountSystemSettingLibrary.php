<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\AccountSystemSettingModel;
class AccountSystemSettingLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new AccountSystemSettingModel();

        $this->table = 'core';
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
   
}
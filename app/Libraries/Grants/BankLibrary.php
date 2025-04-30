<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BankModel;

class BankLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{
    protected $table;
    protected $bankModel;

    public array $lookUpTablesForeignKeyMappings = [
        'user' => [
            'bank_last_modified_by',
            'bank_created_by'
        ]
    ];

    function __construct()
    {
        parent::__construct();

        $this->bankModel = new BankModel();

        $this->table = 'bank';
    }

    function detailTables(): array
    {
        return ['office_bank'];
    }

    function listTableVisibleColumns(): array
    {
        return ['bank_track_number', 'bank_name', 'bank_swift_code', 'bank_is_active', 'account_system_name'];
    }

    function setDatatableSearching(\CodeIgniter\Database\BaseBuilder $builder, array $selectColumns, array $extraColumns = [])
    {
        $extraColumns = ['user_firstname', 'user_lastname'];
        return parent::setDatatableSearching($builder, $selectColumns, $extraColumns);
    }

    // function editVisibleColumns(): array {
    //     $fields = [
    //         'bank_name',
    //         'bank_swift_code',
    //         'bank_is_active',
    //         'account_system_name'
    //     ];

    //     // If a bank has office active office banks remove bank is active field
    //     $bankHasActiveOfficeBanks = $this->bankHasActiveOfficeBanks(hash_id($this->id, 'decode'));

    //     if($bankHasActiveOfficeBanks){
    //         unset($fields[array_search('bank_is_active', $fields)]);
    //     }

    //     return $fields;
    // }

    function actionBeforeEdit(array $postArray): array {
        $bankHasActiveOfficeBanks = $this->bankHasActiveOfficeBanks(hash_id($this->id, 'decode'));

        if($bankHasActiveOfficeBanks && $postArray['header']['bank_is_active'] == 0){
              return [
                'flag' => false,
                'message' => get_phrase('bank_has_active_account_edit_failure', 'Bank edit failed. Bank has active bank accounts')
              ];  
        }

        return $postArray;
    }
    private function bankHasActiveOfficeBanks($bankId){
        $bankReadBuilder = $this->read_db->table('bank');

        $bankReadBuilder->where(['bank_id' => $bankId, 'office_bank_is_active' => 1]);
        $bankReadBuilder->join('office_bank','office_bank.fk_bank_id=bank.bank_id');
        $count = $bankReadBuilder->countAllResults();

        return $count ? true : false;
    }
}
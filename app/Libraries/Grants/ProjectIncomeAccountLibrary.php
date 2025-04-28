<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ProjectIncomeAccountModel;
class ProjectIncomeAccountLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $projectIncomeAccountModel;

    function __construct()
    {
        parent::__construct();

        $this->projectIncomeAccountModel = new ProjectIncomeAccountModel();

        $this->table = 'project_income_account';
    }

    public function detailListTableVisibleColumns(): array {
        return [
            'project_income_account_track_number',
            'income_account_name',
            'project_name',
            'project_income_account_created_date',
            'project_income_account_last_modified_date'
        ];
    }

    public function singleFormAddVisibleColumns(): array {
        return [
            'project_name',
            'income_account_name'
        ];
    }

    public function transactionValidateDuplicatesColumns(): array{
        return ['fk_project_id'];
    }
   
}
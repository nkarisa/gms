<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\EarningCategoryModel;
class EarningCategoryLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $earningCategoryModel;

    function __construct()
    {
        parent::__construct();

        $this->earningCategoryModel = new EarningCategoryModel();

        $this->table = 'earning_category';
    }

    function singleFormAddVisibleColumns(): array {
        return [
            'earning_category_name',
            'earning_category_is_basic',
            'earning_category_is_taxable',
            'earning_category_is_recurring',
            'earning_category_is_accrued',
            'account_system_name'
        ];
    }

    function listTableVisibleColumns(): array {
        return [
            'earning_category_track_number',
            'earning_category_name',
            'earning_category_is_basic',
            'earning_category_is_taxable',
            'earning_category_is_recurring',
            'earning_category_is_accrued',
            'account_system_name',
            'earning_category_created_date'
        ];
    }
   
}
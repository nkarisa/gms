<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\EarningsCategoryModel;
class EarningsCategoryLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $earningsCategoryModel;

    function __construct()
    {
        parent::__construct();

        $this->earningsCategoryModel = new EarningsCategoryModel();

        $this->table = 'earnings_category';
    }

    function singleFormAddVisibleColumns(): array {
        return [
            'earnings_category_name',
            'earnings_category_is_basic',
            'earnings_category_is_taxable',
            'earnings_category_is_recurring',
            'earnings_category_is_payable',
            'account_system_name'
        ];
    }

    function listTableVisibleColumns(): array {
        return [
            'earnings_category_track_number',
            'earnings_category_name',
            'earnings_category_is_basic',
            'earnings_category_is_taxable',
            'earnings_category_is_recurring',
            'earnings_category_is_payable',
            'account_system_name',
            'earnings_category_created_date'
        ];
    }
   
}
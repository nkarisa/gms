<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\PayrollDeductionCategoryModel;
class PayrollDeductionCategoryLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $payrolldeductioncategoryModel;

    function __construct()
    {
        parent::__construct();

        $this->payrolldeductioncategoryModel = new PayrollDeductionCategoryModel();

        $this->table = 'payroll_deduction_category';
    }

    public function actionBeforeInsert(array $postArray): array {

        // Pattern to match any character that is NOT a letter, number, or underscore.
        $pattern = '/[^a-zA-Z0-9_]/';

        // Replace all non-valid characters with an empty string.
        $postArray['header']['payroll_deduction_category_code'] = preg_replace($pattern, '', $postArray['header']['payroll_deduction_category_code']);

        return $postArray;
    }


    function changeFieldType(): array {
        $fields['payroll_deduction_category_liability']['field_type'] = 'select';
        $fields['payroll_deduction_category_liability']['options'] = ['short_term' => 'short_term','long_term' => 'long_term'];

        return $fields;
    }

    public function singleFormAddVisibleColumns(): array {
        return [
            'payroll_deduction_category_name',
            'payroll_deduction_category_code',
            'payroll_deduction_category_liability',
            'account_system_name'
        ];
    }

    public function listTableVisibleColumns(): array {
        return [
            'payroll_deduction_category_track_number',
            'payroll_deduction_category_name',
            'payroll_deduction_category_code',
            'payroll_deduction_category_liability',
            'account_system_name',
            'payroll_deduction_category_created_date'
        ];
    }
   
}
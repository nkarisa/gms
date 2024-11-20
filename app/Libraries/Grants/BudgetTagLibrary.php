<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BudgetTagModel;

class BudgetTagLibrary extends GrantsLibrary
{

    protected $table;
    protected $budgetTagModel;

    function __construct()
    {
        parent::__construct();

        $this->budgetTagModel = new BudgetTagModel();

        $this->table = 'budget_tag';
    }

    function getBudgetTagIdBasedOnReportingMonth($office_id,$reporting_month, $custom_financial_year){
        $months = [];
        $customFinancialYearLibrary = new CustomFinancialYearLibrary();
        $budgetReviewCountLibrary = new BudgetReviewCountLibrary();

        $report_month = date('n',strtotime($reporting_month));
        $custom_financial_year_id = isset($custom_financial_year['custom_financial_year_id']) ? $custom_financial_year['custom_financial_year_id'] : null ;
        
        if($custom_financial_year_id == null || (isset($custom_financial_year['custom_financial_year_is_active']) && $custom_financial_year['custom_financial_year_is_active'])){
            $office_custom_financial_years = $customFinancialYearLibrary->officeCustomFinancialYears($office_id);
            $count_office_custom_fy = count($office_custom_financial_years);

            if($count_office_custom_fy > 1){
                $previous_custom_financial_year = $office_custom_financial_years[$count_office_custom_fy - 2];
                $months =  $customFinancialYearLibrary->getMonthsOrderForCustomYear($previous_custom_financial_year['custom_financial_year_id']);
            }else{
                $builder = $this->read_db->table('month');
                $builder->select(array('month_number'));
                $builder->orderBy('month_order ASC');
                $months_array = $builder->get()->getResultArray();
                $months = array_column($months_array, 'month_number');  
            }
      
        }else{
            $months =  $customFinancialYearLibrary->getMonthsOrderForCustomYear($custom_financial_year_id);
        }

        $review_count = $budgetReviewCountLibrary->budgetReviewCountByOffice($office_id);

        $chunk_key_range = range(1, $review_count);
        $period_size = count($months) /  $review_count;
        $month_chunks = array_chunk($months, $period_size);
        $month_chunks_with_proper_keys = array_combine($chunk_key_range, $month_chunks);

        $level = 0;

        foreach($month_chunks_with_proper_keys as $period_key => $months_in_period){
            if(in_array($report_month, $months_in_period)){
                $level = $period_key;
            }
        }

        $builder = $this->read_db->table('budget_tag');
        $builder->select(array('budget_tag_id','budget_tag_name'));
        $builder->where(array('office_id'=>$office_id, 'budget_tag_level' => $level));
        $builder->join('account_system','account_system.account_system_id=budget_tag.fk_account_system_id');
        $builder->join('office','office.fk_account_system_id=account_system.account_system_id');
        $budget_tag = $builder->get()->getRowArray();

        return $budget_tag;
    }
    
}
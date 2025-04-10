<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\MonthModel;
class MonthLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new MonthModel();

        $this->table = 'core';
    }

    public function defaultFyStartMonth()
    {

        $first_month_builder = $this->read_db->table('month');
        $first_month_builder->select(['month_id', 'month_number', 'month_name']);
        $first_month_builder->where('month_order', 1);
        $first_month=$first_month_builder->get()->getRow();

        return $first_month;
    }

    public function pastMonthsInFy($office_id, $budget_tag_level, $unfreeze_past_quarter = false)
    {

        $budgetLib=new \App\Libraries\Grants\BudgetReviewCountLibrary();

        $past_months_in_fy = [];

        $review_count = $budgetLib->budgetReviewCountByOffice($office_id);

        $month_list_order = array_column(month_order($office_id),'month_number');

        $chunk_size = count($month_list_order) /  $review_count; 

        $year_periods = array_chunk($month_list_order, $chunk_size);

        for($i = 1; $i < $budget_tag_level; $i++){
            $past_months_in_fy = array_merge($past_months_in_fy, $year_periods[$i - 1]);
        }
                
        return $past_months_in_fy;
    }
}

<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BudgetReviewCountModel;
class BudgetReviewCountLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new BudgetReviewCountModel();

        $this->table = 'budget_review_count';
    }

    function budgetReviewCountByOffice($office_id){
        // log_message('error', json_encode([$office_id]));
        $builder = $this->read_db->table('budget_review_count');
        $builder->join('account_system','account_system.account_system_id=budget_review_count.fk_account_system_id');
        $builder->join('office','office.fk_account_system_id=account_system.account_system_id');
        $builder->where(array('office_id' => $office_id));
        $budget_review_count_obj = $builder->get();

        $budget_review_count = 4; // Default is 4, that is quarterly

        if($budget_review_count_obj->getNumRows() > 0){
            $budget_review_count = $budget_review_count_obj->getRow()->budget_review_count_number;
        }
    
        return $budget_review_count;
      }
   
}
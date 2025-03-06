<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\VarianceCommentModel;
class VarianceCommentLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new VarianceCommentModel();

        $this->table = 'grants';
    }

    function getExpenseAccountComment($expense_account_id,$budget_id,$report_id){
        $builder = $this->read_db->table('variance_comment');
        $builder->where(array('fk_expense_account_id'=>$expense_account_id,'fk_financial_report_id'=>$report_id,'fk_budget_id'=>$budget_id));
        $variance_comment_obj = $builder->get('variance_comment');
    
        $commment = '';
    
        if($variance_comment_obj->getNumRows() > 0){
          $commment = $variance_comment_obj->getRow()->variance_comment_text;
        }

        return $commment;
    }

    function getAllExpenseAccountComment($budget_id,$report_id){
           
        $variance_comments_array = [];
        $builder = $this->read_db->table('variance_comment');
        $builder->select(array('fk_income_account_id as income_account_id','fk_expense_account_id as expense_account_id','variance_comment_text'));
        $builder->where(['fk_budget_id'=>$budget_id,'fk_financial_report_id'=>$report_id]);
        $builder->join('expense_account','expense_account.expense_account_id=variance_comment.fk_expense_account_id');
        $variance_comment_obj = $builder->get();

        if($variance_comment_obj->getNumRows() > 0){
            $variance_comments = $variance_comment_obj->getResultArray();

            foreach($variance_comments as $variance_comment){
                $variance_comments_array[$variance_comment['income_account_id']][$variance_comment['expense_account_id']] = $variance_comment['variance_comment_text'];
            }
        }

        return $variance_comments_array;
  }


   
}
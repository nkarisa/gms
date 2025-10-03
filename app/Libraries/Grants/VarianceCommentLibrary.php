<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\VarianceCommentModel;
use App\Libraries\Grants\BudgetLibrary;
class VarianceCommentLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;
    protected $budgetLibrary;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new VarianceCommentModel();
        $this->budgetLibrary = new BudgetLibrary();

        $this->table = 'grants';
    }

    function getExpenseAccountComment($expense_account_id,$budget_id,$report_id){
        $builder = $this->read_db->table('variance_comment');
        $builder->where(array('fk_expense_account_id'=>$expense_account_id,'fk_financial_report_id'=>$report_id,'fk_budget_id'=>$budget_id));
        $variance_comment_obj = $builder->get();
    
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

  function add($post, $parentTable = null, $parentId = null){

    // $post = $this->input->post();
    // $post = $this->request->getPost();

    $expense_account_id = $post['expense_account_id'];
    $variance_comment_text = $post['variance_comment_text'];
    $office_id = $post['office_id'];
    $reporting_month = $post['reporting_month'];
    $report_id = $post['report_id'];

    $budget_id = $this->budgetLibrary->getBudgetIdBasedOnMonth($office_id,$reporting_month);
    // $budget_id = $this->budget_model->get_budget_id_based_on_month($office_id,$reporting_month);

    $message = "";

    if($budget_id > 0){
        $variance_comment_builder = $this->read_db->table('variance_comment');
        $variance_comment_builder->where(array('fk_expense_account_id'=>$expense_account_id,
        'fk_financial_report_id'=>$report_id,'fk_budget_id'=>$budget_id));
        $variance_comment_obj = $variance_comment_builder->get();

        $db = $this->read_db;
  
        $db->transStart();

        if($variance_comment_obj->getNumRows() == 0){

            $variance_comment_data['fk_budget_id'] = $budget_id;
            $variance_comment_data['fk_financial_report_id'] = $report_id;
            $variance_comment_data['fk_expense_account_id'] = $expense_account_id;
            $variance_comment_data['variance_comment_text'] = $variance_comment_text;

            $variance_comment_to_insert = $this->mergeWithHistoryFields($this->controller, $variance_comment_data, false);
        
            //pop out financial_report_track_number, financial_report_created_by, financial_report_created_date
            unset($variance_comment_to_insert['financial_report_track_number']);
            unset($variance_comment_to_insert['financial_report_created_by']);
            unset($variance_comment_to_insert['financial_report_created_date']);

            $new_variance_builder = $this->write_db->table('variance_comment');
            log_message('error', json_encode($variance_comment_to_insert));
            $new_variance_builder->insert($variance_comment_to_insert);
            

            $message = "Comment created successfully";
        }else{
        $data['variance_comment_text'] = $variance_comment_text;

        $update_variance_builder = $this->write_db->table('variance_comment');
        $update_variance_builder->where(array('fk_expense_account_id'=>$expense_account_id,'fk_financial_report_id'=>$report_id));
        $update_variance_builder->update($data);

        $message = "Comment updated successfully";
        }


        $db->transCommit();

        if($db->transStatus() == false){
            $message = "Error occurred";
        }
    }else{
        $message = get_phrase("you_do_not_have_an_approved_budget_review_for_the_period");//"You don't have an approved budget review for the period";
    }


    return $message;
}


   
}
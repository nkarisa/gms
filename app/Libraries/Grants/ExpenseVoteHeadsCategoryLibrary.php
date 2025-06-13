<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ExpenseVoteHeadsCategoryModel;
use App\Enums\AccrualExpenseAccountCodes;
class ExpenseVoteHeadsCategoryLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ExpenseVoteHeadsCategoryModel();

        $this->table = 'expense_vote_heads_category';
    }


    public function checkAndCreateAccrualExpenseVoteHeadCategory(AccrualExpenseAccountCodes $accrualExpenseAccountCode){
        $expenseVoteHeadsCategoryReadBuilder = $this->read_db->table('expense_vote_heads_category');
        $expenseVoteHeadsCategoryWriteBuilder = $this->write_db->table('expense_vote_heads_category');
        
        // Check if funding stream for support is present if not create it
        $fundingStreamLibrary = new \App\Libraries\Grants\FundingStreamLibrary();
        $supportFundingStreamId = $fundingStreamLibrary->checkAndCreateSupportFundingStream();

        // Check if depreciation expense vote head category is present
        $expenseVoteHeadsCategoryReadBuilder->select(['expense_vote_heads_category_id','expense_vote_heads_category_name']);
        $expenseVoteHeadsCategoryReadBuilder->where(['expense_vote_heads_category_code' => $accrualExpenseAccountCode->value]);
        $expenseVoteHeadsCategoryObj = $expenseVoteHeadsCategoryReadBuilder->get();

        $accrualExpenseVoteHeadsCategoryId = 0;
        // Create the vote head if not present
        if($expenseVoteHeadsCategoryObj->getNumRows() > 0){
            $accrualExpenseVoteHeadsCategoryId = $expenseVoteHeadsCategoryObj->getRowArray()['expense_vote_heads_category_id'];
        }else{
            $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('expense_vote_heads_category');
            $statusLibrary = new \App\Libraries\Core\StatusLibrary();

            $voteHeadData['expense_vote_heads_category_track_number'] = $itemTrackNumberAndName['expense_vote_heads_category_track_number'];
            $voteHeadData['expense_vote_heads_category_name'] = $accrualExpenseAccountCode->value;
            $voteHeadData['expense_vote_heads_category_description'] = $accrualExpenseAccountCode->value;
            $voteHeadData['fk_funding_stream_id'] = $supportFundingStreamId;
            $voteHeadData['expense_vote_heads_category_is_active'] = 1;
            $voteHeadData['expense_vote_heads_category_created_date'] = date('Y-m-d');
            $voteHeadData['expense_vote_heads_category_created_by'] = $this->session->user_id;
            $voteHeadData['expense_vote_heads_category_last_modified_date'] = date('Y-m-d');
            $voteHeadData['expense_vote_heads_category_last_modified_by'] = $this->session->user_id;
            $voteHeadData['fk_approval_id'] = NULL;
            $voteHeadData['fk_status_id'] = $statusLibrary->initialItemStatus('expense_vote_heads_category');

            $expenseVoteHeadsCategoryWriteBuilder->insert($voteHeadData);

            $accrualExpenseVoteHeadsCategoryId = $this->write_db->insertID();
        }

        return $accrualExpenseVoteHeadsCategoryId;
    }
   
}
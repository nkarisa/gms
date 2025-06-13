<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\IncomeVoteHeadsCategoryModel;
class IncomeVoteHeadsCategoryLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new IncomeVoteHeadsCategoryModel();

        $this->table = 'income_vote_heads_category';
    }

    public function getSupportIncomeVoteHeadsCategoryId(){
        $incomeVoteHeadsCategoryReadbuilder = $this->read_db->table('income_vote_heads_category');
        $incomeVoteHeadsCategoryWritebuilder = $this->write_db->table('income_vote_heads_category');

        $incomeVoteHeadsCategoryReadbuilder->select(['income_vote_heads_category_id','income_vote_heads_category_name','fk_funding_stream_id','income_vote_heads_category_code']);
        $incomeVoteHeadsCategoryReadbuilder->where(['income_vote_heads_category_code' => 'support']);
        $incomeVoteHeadsCategoryObj = $incomeVoteHeadsCategoryReadbuilder->get();

        $incomeVoteHeadsCategoryId = 0;

        if($incomeVoteHeadsCategoryObj->getNumRows() > 0){
            $incomeVoteHeadsCategoryId = $incomeVoteHeadsCategoryObj->getRowArray()['income_vote_heads_category_id'];
        }else{
            // Get support funding stream id
            $fundingStreamLibrary = new \App\Libraries\Grants\FundingStreamLibrary();
            $fundingStreamId = $fundingStreamLibrary->checkAndCreateSupportFundingStream();

            // Create the missing vote head
            $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('income_vote_heads_category');
            $statusLibrary = new \App\Libraries\Core\StatusLibrary();

            $incomeVoteHeadsCategoryData['income_vote_heads_category_track_number'] = $itemTrackNumberAndName['income_vote_heads_category_track_number'];
            $incomeVoteHeadsCategoryData['income_vote_heads_category_name'] = get_phrase('support_funds');
            $incomeVoteHeadsCategoryData['income_vote_heads_category_description'] = get_phrase('support_funds_description');
            $incomeVoteHeadsCategoryData['fk_funding_stream_id'] = $fundingStreamId;
            $incomeVoteHeadsCategoryData['income_vote_heads_category_code'] = 'support';
            $incomeVoteHeadsCategoryData['income_vote_heads_category_is_active'] = 1;
            $incomeVoteHeadsCategoryData['income_vote_heads_category_created_date'] = date('Y-m-d');
            $incomeVoteHeadsCategoryData['income_vote_heads_category_created_by'] = 1;
            $incomeVoteHeadsCategoryData['income_vote_heads_category_last_modified_date'] = date('Y-m-d');
            $incomeVoteHeadsCategoryData['income_vote_heads_category_last_modified_by'] = 1;
            $incomeVoteHeadsCategoryData['fk_approval_id'] = NULL;
            $incomeVoteHeadsCategoryData['fk_status_id'] = $statusLibrary->initialItemStatus('statusLibrary');

            $incomeVoteHeadsCategoryWritebuilder->insert($incomeVoteHeadsCategoryData);

            $incomeVoteHeadsCategoryId = $this->write_db->insertID();
        }

        return $incomeVoteHeadsCategoryId;
    }
   
}
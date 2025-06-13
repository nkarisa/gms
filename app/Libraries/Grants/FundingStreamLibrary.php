<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\FundingStreamModel;
class FundingStreamLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new FundingStreamModel();

        $this->table = 'funding_stream';
    }

    public function checkAndCreateSupportFundingStream(){
        $fundingStreamReadBuilder = $this->read_db->table('funding_stream');
        $fundingStreamWriteBuilder = $this->write_db->table('funding_stream');

        // Check if support funding stream is present
        $fundingStreamReadBuilder->select(['funding_stream_id','funding_stream_code']);
        $fundingStreamReadBuilder->where(['funding_stream_code' => 'support']);
        $supportFundingStreamObj = $fundingStreamReadBuilder->get();

        $fundingStreamId = 0;

        if($supportFundingStreamObj->getNumRows() > 0){
            // If support funding is present, get the id
            $fundingStreamId = $supportFundingStreamObj->getRowArray()['funding_stream_id'];
        }else{
            // If not present create it and get the id
            $statusLibrary = new \App\Libraries\Core\StatusLibrary();
            $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('funding_stream');

            $fundingStreamData['funding_stream_name'] = 'Support Stream';
            $fundingStreamData['funding_stream_track_number'] = $itemTrackNumberAndName['funding_stream_track_number'];
            $fundingStreamData['funding_stream_code'] = 'support';
            $fundingStreamData['funding_stream_created_date'] = date('Y-m-d');
            $fundingStreamData['funding_stream_created_by'] = $this->session->user_id;
            $fundingStreamData['funding_stream_last_modified_by'] = $this->session->user_id;
            $fundingStreamData['funding_stream_last_modified_date'] = date('Y-m-d');
            $fundingStreamData['fk_status_id'] = $statusLibrary->initialItemStatus('funding_stream');
            $fundingStreamData['fk_approval_id'] = NULL;

            $fundingStreamWriteBuilder->insert($fundingStreamData);

            $fundingStreamId = $this->write_db->insertID();
        }   


        return $fundingStreamId;
    }
   
}
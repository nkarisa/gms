<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ItemReasonModel;
class ItemReasonLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ItemReasonModel();

        $this->table = 'item_reason';
    }

    public function getApproveItemDefaultReason($approveItemName){
        $itemReasonReadBuilder = $this->read_db->table('item_reason');

        $itemReasonReadBuilder->select(['item_reason_id','item_reason_name']);
        $itemReasonReadBuilder->where(['approve_item_name' => $approveItemName]);
        $itemReasonReadBuilder->join('approve_item','approve_item.approve_item_id=item_reason.fk_approve_item_id');
        $defaultReasonObj = $itemReasonReadBuilder->get();

        $defaultReason = [];

        if($defaultReasonObj->getNumRows() > 0){
            $defaultReason = $defaultReasonObj->getRowArray();
        }

        return $defaultReason;
    }
}
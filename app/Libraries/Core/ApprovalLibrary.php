<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\ApprovalModel;
use App\Libraries\Core\StatusLibrary;

class ApprovalLibrary extends GrantsLibrary
{

    protected $table;
    protected $approvalModel;

    function __construct()
    {
        parent::__construct();

        $this->approvalModel = new ApprovalModel();

        $this->table = 'approval';
    }

    public function insertApprovalRecord($approveable_item)
    {
        $statusLibrary = new StatusLibrary();
        $insert_id = 0;

        // Generate approval record details
        $approval_random = record_prefix('Approval') . '-' . rand(1000, 90000);
        $approval = [
            'approval_track_number' => $approval_random,
            'approval_name' => 'Approval Ticket # ' . $approval_random,
            'approval_created_by' => $this->session->get('user_id') ?? 1,
            'approval_created_date' => date('Y-m-d'),
            'approval_last_modified_by' => $this->session->get('user_id') ?? 1,
            'fk_approve_item_id' => $this->read_db->table('approve_item')
                ->where('approve_item_name', strtolower($approveable_item))
                ->get()
                ->getRow()
                ->approve_item_id,
            'fk_status_id' => $statusLibrary->initialItemStatus($approveable_item)
        ];

        $builder = $this->write_db->table('approval');
        $builder->insert($approval);
        $insert_id = $this->write_db->insertID();

        return $insert_id;
    }

}
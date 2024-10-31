<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\ApprovalFlowModel;
class ApprovalFlowLibrary extends GrantsLibrary
{

    protected $table;
    protected $approvalFlowModel;

    function __construct()
    {
        parent::__construct();

        $this->approvalFlowModel = new ApprovalFlowModel();

        $this->table = 'approval_flow';
    }


    /**
     * Inserts a new approval flow record into the database.
     *
     * @param array $account_system The account system data.
     * @param int $approve_item_id The ID of the item being approved.
     * @param string $approve_item_name The name of the item being approved.
     * @param int $user_id The ID of the user creating the approval flow.
     *
     * @return int The ID of the newly inserted approval flow record.
     */
    public function insertApprovalFlow($account_system, $approve_item_id, $approve_item_name, $user_id)
    {
        // Prepare the data for the approval flow record
        $approval_flow_data['approval_flow_track_number'] = generate_item_track_number_and_name('approval_flow')['approval_flow_track_number'];
        $approval_flow_data['approval_flow_name'] = $account_system['account_system_name'] . ' ' . str_replace("_", " ", $approve_item_name) . ' workflow';
        $approval_flow_data['fk_approve_item_id'] = $approve_item_id;
        $approval_flow_data['fk_account_system_id'] = $account_system['account_system_id'];

        // Set the created and last modified fields
        $approval_flow_data['approval_flow_created_by'] = $user_id;
        $approval_flow_data['approval_flow_created_date'] = date('Y-m-d');
        $approval_flow_data['approval_flow_last_modified_by'] = $user_id;

        // Insert the approval flow record into the database
        $this->approvalFlowModel->insert((object) $approval_flow_data);

        // Return the ID of the newly inserted record
        return $this->approvalFlowModel->getInsertID();
    }

    function detailTables(): array {
        return ['status'];
    }

    function showListEditAction(array $record): bool{
        return true;
    }


    function listTableWhere(\CodeIgniter\Database\BaseBuilder $builder): void{
       parent::listTableWhere($builder);
       $builder->where('approve_item_is_active', 1);
    }

}
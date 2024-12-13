<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\ApproveItemModel;

class ApproveItemLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{
    protected $table;

    protected $approveItemModel;

    public function __construct()
    {
        parent::__construct();

        $this->approveItemModel = new ApproveItemModel();

        $this->table = 'approve_item';
    }

    // public function multiSelectField(): string
    // {
    //     return '';
    // }

    // public function actionBeforeIinsert(array $postArray): array{
    //     return $postArray;
    // }
    /**
     * Inserts a missing approveable item into the database if it does not exist.
     *
     * @param string $table The name of the table for which the approveable item is being checked.
     * @return int The ID of the inserted or existing approveable item.
     */
    public function insertMissingApproveableItem($table)
    {
        // Fetch the existing approveable item with the given table name

        $approve_items = $this->read_db->table('approve_item')
            ->getWhere(['approve_item_name' => $table]);

        // Initialize the approve item ID
        $approve_item_id = 0;

        // Get the user ID from the session, or default to 1 if not found
        $user_id = session()->get('user_id') ? session()->get('user_id') : 1; // User Id 1 is created by setup

        // If no existing approveable item is found, insert a new one
        if ($approve_items->getNumRows() == 0) {
            // Generate a new item track number and name for the approveable item
            $item_track_data = generate_item_track_number_and_name('approve_item');

            // Prepare the data for insertion
            $data = [
                'approve_item_track_number' => $item_track_data['approve_item_track_number'],
                'approve_item_name' => $table,
                'approve_item_is_active' => 0, // Not active means the items do not require approval
                'approve_item_created_date' => date('Y-m-d'),
                'approve_item_created_by' => $user_id,
                'approve_item_last_modified_by' => $user_id
            ];


            // Insert the new approveable item into the database
            $builder = $this->write_db->table("approve_item");
            $builder->insert($data);

            // Get the ID of the newly inserted approveable item
            $approve_item_id = $this->write_db->insertID();
        } else {
            // If an existing approveable item is found, get its ID
            $approve_item = $approve_items->getRow();
            $approve_item_id = $approve_item->approve_item_id;
        }

        // Return the ID of the inserted or existing approveable item
        return $approve_item_id;
    }

    public function approveableItems()
    {

        $builder = $this->read_db->table('approve_item');
        $builder->select('approve_item_name');
        $builder->where('approve_item_is_active', 1);

        $approveable_items_array = $builder->get()->getResultArray();
        $approveable_items = array_column($approveable_items_array, 'approve_item_name');

        return $approveable_items;
    }

    function approveableItem($approveable_item_name = "")
    {

        $approveable_item_name = isEmpty($approveable_item_name) ? $this->controller : $approveable_item_name;

        $approveable_item_flag = false;

        if (in_array($approveable_item_name, $this->approveableItems())) {
            $approveable_item_flag = true;
        }

        return $approveable_item_flag;
    }

}
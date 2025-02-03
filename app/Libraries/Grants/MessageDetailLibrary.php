<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\MessageDetailModel;
class MessageDetailLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new MessageDetailModel();

        $this->table = 'grants';
    }


    public function postNewMessage(string $approve_item, int $primary_key, string $message_body): int
    {

        $message_track = $this->generateItemTrackNumberAndName('message');
        $message_detail_track = $this->generateItemTrackNumberAndName('message_detail');

        // Fetch approve_item_id
        $approveItemQuery = $this->read_db->table('approve_item')
            ->select('approve_item_id')
            ->where('approve_item_name', $approve_item)
            ->get()
            ->getRow();

        if (!$approveItemQuery) {
            return 0; // Return failure if approve_item not found
        }

        $approve_item_id = $approveItemQuery->approve_item_id;

        // Start transaction
        $this->write_db->transStart();

        // Insert message
        $insertMessageData = [
            'message_track_number' => $message_track['message_track_number'],
            'message_name' => $message_track['message_name'],
            'fk_approve_item_id' => $approve_item_id,
            'message_record_key' => $primary_key,
            'message_created_by' => session()->get('user_id'), // CI4 session handling
            'message_created_date' => date('Y-m-d H:i:s') // CI4 prefers 24-hour format
        ];

        $this->write_db->table('message')->insert($insertMessageData);
        $message_id = $this->write_db->insertID();

        // Insert message details
        $insertDetailData = [
            'message_detail_track_number' => $message_detail_track['message_detail_track_number'],
            'message_detail_name' => $message_detail_track['message_detail_name'],
            'fk_user_id' => session()->get('user_id'),
            'message_detail_content' => $message_body,
            'fk_message_id' => $message_id,
            'message_detail_created_date' => date('Y-m-d H:i:s'),
            'message_detail_created_by' => session()->get('user_id')
        ];

        $this->write_db->table('message_detail')->insert($insertDetailData);

        // Complete transaction
        $this->write_db->transComplete();

        return $this->write_db->transStatus() ? 1 : 0;
    }


   
}
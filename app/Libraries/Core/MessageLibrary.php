<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\MessageModel;
class MessageLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new MessageModel();

        $this->table = 'core';
    }

    public function getChatMessages(string $approve_item_name, int $record_primary_key)
    {
        // Get the approve_item_id based on the approve_item_name
        $approveItemRow = $this->read_db->table('approve_item')
            ->select('approve_item_id')
            ->where('approve_item_name', $approve_item_name)
            ->get()
            ->getRow();

        if (!$approveItemRow) {
            // Handle case where no approve_item is found
            return [];
        }

        $approve_item_id = $approveItemRow->approve_item_id;

        // Build the query to get chat messages
        $builder = $this->read_db->table('message_detail')
            ->select([
                'fk_user_id AS author',
                'message_detail_content AS message',
                'message_detail_created_date AS message_date'
            ])
            ->join('message', 'message.message_id = message_detail.fk_message_id')
            ->where([
                'fk_approve_item_id' => $approve_item_id,
                'message_record_key' => hash_id($record_primary_key, 'decode')
            ])
            ->orderBy('message_detail_created_date', 'DESC');

        // Execute the query and return the result as an array
        $chat_messages = $builder->get()->getResultArray();

        return $chat_messages;
    }
   
}
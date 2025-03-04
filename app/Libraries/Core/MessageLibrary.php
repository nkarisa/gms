<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\MessageModel;
use CodeIgniter\HTTP\ResponseInterface;

class MessageLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
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

      /**
     * notesHistory(): This method retrieves old messages/notes
     * @author Livingstone Onduso
     * @access public
     * @return string
     * @param int $item_id
     */
    function notesHistory(int $item_id){
    
        $data['notes'] = [];
    
        $builder=$this->read_db->table('message_detail');
        $builder->select(array('user_id','message_detail_last_modified_by as last_modified_by',
        "message_id",'message_detail_last_modified_date as last_modified_date','message_record_key',
        "message_detail_id","CONCAT(user_firstname, ' ', user_lastname) as creator",
        'message_detail_content as body', 'message_detail_created_date as created_date', 'message_detail_readers as message_readers'));
        $builder->where(array('approve_item_name' => 'budget_item', 'message_record_key' => $item_id));
        $builder->join('message','message.message_id=message_detail.fk_message_id');
        $builder->join('user','user.user_id=message_detail.fk_user_id');
        $builder->join('approve_item','approve_item.approve_item_id=message.fk_approve_item_id');
        $messages_obj = $builder->get();
    
        if($messages_obj->getNumRows() > 0){
          $data['notes'] = $messages_obj->getResultArray();
        }
        
        $viewContent = view('message/message_holder', $data);

        echo $viewContent;
    
      }
    /**
     * postNewMessage(): This method posts messages/notes
     * @author Livingstone Onduso
     * @access public
     * @return 
     * @param $approve_item, int $primary_key, $message_body
     */
      function postNewMessage($approve_item, int $primary_key, $message_body){

        $libStatus=new  \App\Libraries\Core\StatusLibrary();
        $message_track = $libStatus->generateItemTrackNumberAndName('message');

        $message_detail_track = $libStatus->generateItemTrackNumberAndName('message_detail');
    
        $reader=$this->read_db->table('approve_item');
        $reader->select(array('approve_item_id'));
        $reader->where(array('approve_item_name' => $approve_item));
        $approve_item_id = $reader->get()->getRow()->approve_item_id;
    
        $this->write_db->transStart();
    
        $writer_message=$this->write_db->table('message');

        $insert_message_data = [
          'message_track_number' => $message_track['message_track_number'],
          'message_name' => $message_track['message_name'],
          'fk_approve_item_id' => $approve_item_id,
          'message_record_key' => $primary_key,
          'message_created_by' => $this->session->user_id,
          'message_created_date' => date('Y-m-d h:i:s')
        ];
    
        $writer_message->insert($insert_message_data);
    
        $message_id = $this->write_db->insertID();
    
        $insert_detail_data = [
          'message_detail_track_number' => $message_detail_track['message_detail_track_number'],
          'message_detail_name' => $message_detail_track['message_detail_name'],
          'fk_user_id' => $this->session->user_id,
          'message_detail_content' => $message_body,
          'fk_message_id' => $message_id,
          'message_detail_created_date' => date('Y-m-d h:i:s'),
          'message_detail_created_by' => $this->session->user_id
        ];
    
        $writer_message_details=$this->write_db->table('message_detail');
        $writer_message_details->insert($insert_detail_data);
    
        $this->write_db->transComplete();
        
        $response = 0;
        
        if($this->write_db->transStatus() == true){
          $response = 1;
        }
    
        return $response;
      }
    /**
     * updateMessage(): This method updates existing messages/notes
     * @author Livingstone Onduso
     * @access public
     * @return  int
     * @param int $message_detail_id, string $note
     */
      public function updateMessage(int $message_detail_id, string $note):int{

        $data['message_detail_content'] = $note;
        $data['message_detail_last_modified_date'] = date('Y-m-d h:i:s');
        $data['message_detail_last_modified_by'] = $this->session->user_id;
        $data['message_detail_readers'] = NULL;
        
        $writer=$this->write_db->table('message_detail');
        $writer->where(array('message_detail_id' => $message_detail_id));
        $writer->update($data);
    
        $response = 0;
    
        if($this->write_db->affectedRows()){
          $response = 1;
        }
    
        return $response;
      }

      /**
     * deleteNote(): This method deletes existing messages/notes
     * @author Livingstone Onduso
     * @access public
     * @return  int
     */
    function deleteNote():int{

        
        $post=$this->request->getPost();
  
        // ALL QUERIES MUST USE write_db HANDLE. DO NOT CHANGE

        $writer_builder_msg_detail=$this->write_db->table('message_detail');

        $writer_builder_msg_detail->where(array('message_detail_id' => $post['message_detail_id']));
        $writer_builder_msg_detail->delete();

        $writer_builder_msg_detail->where(array('fk_message_id' => $post['message_id']));
        $available_remaining_message_details = $writer_builder_msg_detail->get()->getNumRows();
  
        if($available_remaining_message_details == 0){

            $writer_delete=$this->write_db->table('message');

             $writer_delete->where(array('message_id' => $post['message_id']));
             $writer_delete->delete();
        }
  
        if($this->write_db->affectedRows() > 0){
          return 1;
        }else{
          return 0;
        }
      }

    // /**
    //  * deleteNote(): This method deletes existing messages/notes
    //  * @author Livingstone Onduso
    //  * @access public
    //  * @return  int
    //  * @param int $message_id, int $message_detail_id
    //  */
    //   function deleteNote(int $message_id, int $message_detail_id):int{

    //     // ALL QUERIES MUST USE write_db HANDLE. DO NOT CHANGE
  
    //     $writer_builder_msg_detail=$this->write_db->table('message_detail');

    //     $writer_builder_msg_detail->where(array('message_detail_id' => $message_detail_id));
    //     $writer_builder_msg_detail->delete();

    //     $writer_builder_msg_detail->where(array('fk_message_id' => $message_id));
    //     $available_remaining_message_details = $writer_builder_msg_detail->get()->getNumRows();
  
    //     if($available_remaining_message_details == 0){

    //         $writer_delete=$this->write_db->table('message');

    //          $writer_delete->where(array('message_id' => $message_id));
    //          $writer_delete->delete();
    //     }
  
    //     if($this->write_db->affectedRows() > 0){
    //       return 1;
    //     }else{
    //       return 0;
    //     }
    //   }
   
}
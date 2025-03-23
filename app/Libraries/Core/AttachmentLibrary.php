<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\AttachmentModel;
use Config\Session;

class AttachmentLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $coreModel;
    protected $awsAttachmentLibrary;
    protected $grantsLibrary;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new AttachmentModel();
        $this->awsAttachmentLibrary = new \App\Libraries\System\AwsAttachmentLibrary();
        $this->grantsLibrary = new \App\Libraries\System\GrantsLibrary();

        $this->table = 'core';
    }

  /**
   * upload_files
   */

   function uploadFiles($storeFolder, $attachment_type_name = "")
   {
 
     $path_array = explode("/", $storeFolder);
     $item_id = $path_array[3];
     //voucher_ID_355019
 
     //return  $path_array;
     //Added by Livingstone Onduso on 11/03/2022 incase of any error in upload function [For medical uploads to work]
     if (!is_numeric($item_id)) {
 
       //Medical uploading piece
       if (strpos($item_id, '-')) {
 
         $explode_item_id = explode('-', $item_id);
 
         $storeFolder = str_replace($item_id, $explode_item_id[0], $storeFolder);
 
         $item_id = $explode_item_id[1];
       }
       //Other uploads e.g. voucher or budget except mfr bank statement uploads
       else {
 
         $last_item_in_array = end($path_array); //The item ID
 
         $explode_last_item = explode('-', $last_item_in_array);
 
         $item_id = $explode_last_item[2]; //item ID at position 2
       }
     }
 
     //End of added piece
     $builder = $this->read_db->table('approve_item');
     $approve_item_id = $builder->getWhere(array('approve_item_name' => $path_array[2]))->getRow()->approve_item_id;
 
     $additional_attachment_table_insert_data = [];
  
     $additional_attachment_table_insert_data['fk_approve_item_id'] = $approve_item_id;
     $additional_attachment_table_insert_data['attachment_primary_id'] = $item_id;
     $additional_attachment_table_insert_data['attachment_is_s3_upload'] = 1;
     $additional_attachment_table_insert_data['attachment_created_by'] = $this->session->user_id;
     $additional_attachment_table_insert_data['attachment_last_modified_by'] = $this->session->user_id;
     $additional_attachment_table_insert_data['attachment_created_date'] = date('Y-m-d');
     $additional_attachment_table_insert_data['attachment_track_number'] = $this->grantsLibrary->generateItemTrackNumberAndName('attachment')['attachment_track_number'];
     $additional_attachment_table_insert_data['fk_approval_id'] = $this->grantsLibrary->insertApprovalRecord('attachment');
     $additional_attachment_table_insert_data['fk_status_id'] = $this->grantsLibrary->initialItemStatus('attachment');
     $additional_attachment_table_insert_data['fk_attachment_type_id'] = $this->getAttachmentTypeId($attachment_type_name);
 
     $attachment_where_condition_array = [];
 
     $attachment_where_condition_array = array(
       'fk_approve_item_id' => $approve_item_id,
       'attachment_primary_id' => $item_id
     );
 
     $preassigned_urls =  $this->awsAttachmentLibrary->uploadFiles($storeFolder, $additional_attachment_table_insert_data, $attachment_where_condition_array);
 
     return $preassigned_urls;
   }

   function getAttachmentTypeId($attachment_type_name = "")
   {
    $builder = $this->read_db->table('attachment_type');
     $builder->select(array('attachment_type_id'));
 
     if($attachment_type_name != ""){
       $builder->where(array('attachment_type_name' => $attachment_type_name));
     }else{
       // This code is to manage backward compatibility
       $builder->where(array('approve_item_name' => strtolower($this->controller)));
     }
     
     $builder->join('approve_item', 'approve_item.approve_item_id=attachment_type.fk_approve_item_id');
     $attachment_type_id = $builder->get()->getRow()->attachment_type_id;
 
     return $attachment_type_id;
   }

   function getUploadedS3Documents($attachment_id, $approve_item_name)
  {

    $reader_builder=$this->$this->read_db->table('attachment');

    $reader_builder->select(['attachment_id', 'attachment_name', 'attachment_url']);
    $reader_builder->where([
      'fk_account_system_id' => $this->session->user_account_system_id, 
      'attachment_primary_id' => $attachment_id,
      'approve_item_name' => $approve_item_name
    ]);
    $reader_builder->join('approve_item','approve_item.approve_item_id=attachment.fk_approve_item_id');
    $uploaded_docs =  $reader_builder->get()->getResultArray();

    return $uploaded_docs;
  }

  public function getLocalFilesystemAttachmentUrl($objectKey){
    return base_url().$objectKey;
  }

  public function deleteUploadedDocument(int $uploaded_image_id, string $file_path='')
  {

    //Delete Bank statements
    if($file_path!=''){

      $this->awsAttachmentLibrary->deleteBankStatementInS3($file_path);

    }

    //Delete From attachment table
    
    $this->write_db->transStart();
    $builder =  $this->write_db->table('attachment');
    $builder->where(['attachment_id' => $uploaded_image_id]);
    $builder->delete();
    $this->write_db->transComplete();

    // $this->write_db->where(['attachment_id' => $uploaded_image_id]);
    // $this->write_db->delete('attachment');
    // $this->write_db->trans_complete();

    if ($this->write_db->transStatus() == FALSE) {
      return 0;
    } else {
      return 1;
    }
  }
   
}
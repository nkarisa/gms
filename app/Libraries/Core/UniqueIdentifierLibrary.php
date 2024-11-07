<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\UniqueIdentifierModel;

class UniqueIdentifierLibrary extends GrantsLibrary
{

    protected $table;

    protected $uniqueIdentifierModel;

    public function __construct()
    {
        parent::__construct();

        $this->uniqueIdentifierModel = new UniqueIdentifierModel();

        $this->table = 'unique_identifier';
    }

    // public function multiSelectField(): string
    // {
    //     return '';
    // }

    // public function actionBeforeIinsert(array $postArray): array{
    //     return $postArray;
    // }
    /**
     * Retrieves the unique identifier associated with a specific account system.
     *
     * @param int $accountSystemId The ID of the account system.
     * @return array An associative array containing the unique identifier details.
     *              If no active unique identifier is found, an empty array is returned.
     *
     * @throws \Exception If there is an error executing the database query.
     */
    public function getAccountSystemUniqueIdentifier($accountSystemId)
    {
        // Initialize the database query builder
        $builder = $this->read_db->table($this->table);

        // Select the required columns
        $builder->select('unique_identifier_id, unique_identifier_name');

        // Apply the filter for the account system ID
        $builder->where('fk_account_system_id', $accountSystemId);

        // Apply the filter for active unique identifiers
        $builder->where('unique_identifier_is_active', 1);

        // Execute the database query
        $query = $builder->get();

        // Initialize an empty array to store the unique identifier details
        $uniqueIdentifier = [];

        // Check if any rows were returned by the query
        if ($query->getNumRows() > 0) {
            // If rows were returned, fetch the first row as an associative array
            $uniqueIdentifier = $query->getRowArray();
        }

        // Return the unique identifier details
        return $uniqueIdentifier;
    }

    function validUserUniqueIdentifier($user_id)
    {

        $userLibrary = new \App\Libraries\Core\UserLibrary();

        $user_info = $userLibrary->getUserInfo(['user_id' => $user_id]);

        $valid_user_unique_identifier = [];

        $user_unique_identifier = ['unique_identifier_id' => $user_info['unique_identifier_id'], 'unique_identifier_name' => $user_info['unique_identifier_name']];
        $account_system_unique_identifier = $this->getAccountSystemUniqueIdentifier($user_info['account_system_id']);

        if (!empty($account_system_unique_identifier)) {
            if ($user_info['unique_identifier_id'] != null) {
                if ($user_unique_identifier['unique_identifier_id'] == $account_system_unique_identifier['unique_identifier_id']) {
                    $valid_user_unique_identifier = $account_system_unique_identifier;
                } else {
                    $valid_user_unique_identifier = $user_unique_identifier;
                }
            } else {
                $valid_user_unique_identifier = $account_system_unique_identifier;
            }

        }

        return $valid_user_unique_identifier;
    }


    function userUniqueIdentifierUploads($user_id)
    {

        $attachment_type_name = 'user_unique_identifier_document';
        $account_system_unique_identifier = $this->getAccountSystemUniqueIdentifier($this->session->user_account_system_id);
        $unique_identifier_id = isset($account_system_unique_identifier['unique_identifier_id']) ? $account_system_unique_identifier['unique_identifier_id'] : 0;
        $attachment_url = "uploads/attachments/user/" . $user_id . "/user_identifier_document/" . $unique_identifier_id;

        $builder = $this->read_db->table('attachment');

        $builder->select(array('attachment_url', 'attachment_name'));
        $builder->where(array('attachment_type_name' => $attachment_type_name, 'attachment_url' => $attachment_url));
        $builder->join('attachment_type', 'attachment_type.attachment_type_id=attachment.fk_attachment_type_id');
        $attachment_obj = $builder->get();

        $attachments = [];

        if ($attachment_obj->getNumRows() > 0) {
            $attachments = $attachment_obj->getResultArray();
        }

        return $attachments;
    }

    function checkUniqueIdentifierDuplicates($unique_identifier_id, $user_unique_identifier){

        $identifier_duplicates = ['status' => false, 'records' => []];
        
        $builder = $this->read_db->table('user');
        
        $builder->select(array('user_firstname','user_lastname','user_email'));
        $builder->where(array('unique_identifier_id' => $unique_identifier_id, 'user_unique_identifier' => $user_unique_identifier));
        $builder->join('unique_identifier','unique_identifier.unique_identifier_id=user.fk_unique_identifier_id');
        $builder->join('account_system','account_system.account_system_id=unique_identifier.fk_account_system_id');
        $user_obj = $builder->get();
    
        if($user_obj->getNumRows() > 0){
          $identifier_duplicates = ['status' => true, 'records' => $user_obj->getResultArray()];
        }
    
        return $identifier_duplicates;
      }

      function getOfficeContextAllowedUniqueIdentifier($context_definition_id, $context_office_id)
    {
        $context_name = 'center';

        switch ($context_definition_id) {
            case 1:
                $context_name = 'center';
                break;
            case 2:
                $context_name = 'cluster';
                break;
            case 3:
                $context_name = 'cohort';
                break;
            case 4:
                $context_name = 'country';
                break;
            case 5:
                $context_name = 'region';
                break;
            case 6:
                $context_name = 'global';
                break;
            default:
                $context_name = 'center';
        }
        
        $builder = $this->read_db->table('unique_identifier');

        $builder->select(array('unique_identifier_id', 'unique_identifier_name'));
        $builder->join('account_system', 'account_system.account_system_id=unique_identifier.fk_account_system_id');
        $builder->join('office', 'office.fk_account_system_id=account_system.account_system_id');
        $builder->join('context_' . $context_name, 'context_' . $context_name . '.fk_office_id=office.office_id');
        $builder->where(array('context_' . $context_name . '_id' => $context_office_id, 'unique_identifier_is_active' => 1));
        $active_unique_identifier_obj = $builder->get();

        $active_unique_identifier = [];

        if ($active_unique_identifier_obj->getNumRows() > 0) {
            $active_unique_identifier = $active_unique_identifier_obj->getRowArray();
        }

        return $active_unique_identifier;
    }
}
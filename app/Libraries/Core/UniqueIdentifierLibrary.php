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
}
<?php

namespace App\Models\Grants;

use CodeIgniter\Model;

class ReimbursementIllinessCategoryModel extends Model
{
    protected $table            = 'reimbursementillinesscategory';
    protected $primaryKey       = 'reimbursementillinesscategory_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'reimbursementillinesscategorycreated_date';
    protected $updatedField  = 'reimbursementillinesscategory_last_modified_date';
    protected $deletedField  = 'reimbursementillinesscategory_deleted_date';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}

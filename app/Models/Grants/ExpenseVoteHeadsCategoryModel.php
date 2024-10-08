<?php

namespace App\Models\Grants;

use CodeIgniter\Model;

class ExpenseVoteHeadsCategoryModel extends Model
{
    protected $table            = 'expensevoteheadscategory';
    protected $primaryKey       = 'expensevoteheadscategory_id';
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
    protected $createdField  = 'expensevoteheadscategorycreated_date';
    protected $updatedField  = 'expensevoteheadscategory_last_modified_date';
    protected $deletedField  = 'expensevoteheadscategory_deleted_date';

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

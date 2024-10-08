<?php

namespace App\Models\Grants;

use CodeIgniter\Model;

class BudgetProjectionIncomeAccountModel extends Model
{
    protected $table            = 'budgetprojectionincomeaccount';
    protected $primaryKey       = 'budgetprojectionincomeaccount_id';
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
    protected $createdField  = 'budgetprojectionincomeaccountcreated_date';
    protected $updatedField  = 'budgetprojectionincomeaccount_last_modified_date';
    protected $deletedField  = 'budgetprojectionincomeaccount_deleted_date';

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

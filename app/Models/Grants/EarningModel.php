<?php

namespace App\Models\Grants;

use CodeIgniter\Model;

class EarningModel extends Model
{
    protected $table            = 'earning';
    protected $primaryKey       = 'earning_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'earning_name',
        'earning_track_number',
        'fk_pay_history_id',
        'fk_earning_category_id',
        'earning_amount',
        'earning_created_date',
        'earning_created_by',
        'earning_last_modified_by'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'earning_created_date';
    protected $updatedField  = 'earning_last_modified_date';
    protected $deletedField  = 'earning_deleted_date';

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

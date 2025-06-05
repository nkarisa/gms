<?php

namespace App\Models\Grants;

use CodeIgniter\Model;

class VoucherModel extends Model
{
    protected $table            = 'voucher';
    protected $primaryKey       = 'voucher_id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Grants\Voucher::class;
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
    protected $createdField  = 'voucher_created_date';
    protected $updatedField  = 'voucher_last_modified_date';
    protected $deletedField  = 'voucher_deleted_date';

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

<?php

namespace App\Models\Grants;

use CodeIgniter\Model;

class PayrollDeductionModel extends Model
{
    protected $table            = 'payroll_deduction';
    protected $primaryKey       = 'payroll_deduction_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'payroll_deduction_name',
        'payroll_deduction_track_number',
        'fk_payroll_deduction_category_id',
        'fk_payslip_id',
        'payroll_deduction_amount',
        'payroll_deduction_created_date',
        'payroll_deduction_created_by',
        'payroll_deduction_last_modified_by'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'payroll_deduction_created_date';
    protected $updatedField  = 'payroll_deduction_last_modified_date';
    protected $deletedField  = 'payroll_deduction_deleted_date';

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

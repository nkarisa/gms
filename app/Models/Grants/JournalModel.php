<?php

namespace App\Models\Grants;

use CodeIgniter\Model;
use \Tatter\Relations\Traits\ModelTrait;

class JournalModel extends Model
{
    protected $table            = 'journal';
    protected $primaryKey       = 'journal_id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Grants\Journal::class;
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
    protected $createdField  = 'journal_created_date';
    protected $updatedField  = 'journal_last_modified_date';
    protected $deletedField  = 'journal_deleted_date';

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

    protected $with = ['voucher_detail'];
}

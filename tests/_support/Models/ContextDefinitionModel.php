<?php

namespace Tests\Support\Models;

use CodeIgniter\Model;

class ContextDefinitionModel extends Model
{
    protected $table          = 'context_definition';
    protected $primaryKey     = 'context_definition_id';
    protected $returnType     = 'object';
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'context_definition_id',
        'context_definition_track_number',
        'context_definition_name',
        'context_definition_level',
        'context_definition_is_implementing',
        'context_definition_is_active',
        'context_definition_created_date',
        'context_definition_last_modified_date',
        'context_definition_created_by',
        'context_definition_last_modified_by',
        'context_definition_deleted_at',
    ];
    protected $useTimestamps      = true;
    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'context_definition_created_date';
    protected $updatedField  = 'context_definition_last_modified_date';
    protected $deletedField  = 'context_definition_deleted_at';
}

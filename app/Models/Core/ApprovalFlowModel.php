<?php

namespace App\Models\Core;

use CodeIgniter\Model;
use App\Interfaces\ModelInterface;

class ApprovalFlowModel extends Model implements ModelInterface
{
    protected $table            = 'approval_flow';
    protected $primaryKey       = 'approval_flow_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $DBGroup = 'write';
    protected $allowedFields    = [
        'approval_flow_name',
        'approval_flow_track_number',
        'fk_approve_item_id',
        'fk_account_system_id',
        'approval_flow_is_active',
        'approval_flow_created_by',
        'approval_flow_created_date',
        'approval_flow_last_modified_by',
        'approval_flow_last_modified_date',
        'fk_approval_id',
        'fk_status_id'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'approval_flow_created_date';
    protected $updatedField  = 'approval_flow_last_modified_date';
    protected $deletedField  = 'approval_flow_deleted_at';

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

    public function all():array{
        $users = $this->select($this->allowedFields)->findAll();
        return $users;
    }

    public function one($id):array{
        $user = $this->select($this->allowedFields)->find($id);
        return $user;
    }

    public function search($condition, $first = false){
        if($first){
            return $this->select($this->allowedFields)->where($condition)->first();
        }else{
            return $this->select($this->allowedFields)->where($condition)->findAll();
        }
    }

    function append_creator_id(){

    }
}

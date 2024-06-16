<?php

namespace App\Models\Core;

use CodeIgniter\Model;
use App\Interfaces\ModelInterface;

class StatusModel extends Model implements ModelInterface
{
    protected $table            = 'status';
    protected $primaryKey       = 'status_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $DBGroup = 'write';
    protected $allowedFields    = [
        'status_track_number', 'status_name', 'status_button_label', 
        'status_decline_button_label', 'fk_approval_flow_id', 
        'status_approval_sequence', 'status_backflow_sequence', 
        'status_approval_direction', 'status_is_requiring_approver_action',
        'status_created_date', 'status_created_by', 'status_last_modified_by'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'status_created_date';
    protected $updatedField  = 'status_last_modified_date';
    protected $deletedField  = 'status_deleted_at';

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

<?php

namespace App\Models\Core;

use CodeIgniter\Model;
use App\Interfaces\ModelInterface;

class ApproveItemModel extends Model implements ModelInterface
{
    protected $table            = 'approve_item';
    protected $primaryKey       = 'approve_item_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'approve_item_track_number',
        'approve_item_name',
        'approve_item_is_active',
        'approve_item_created_date',
        'approve_item_created_by',
        'approve_item_last_modified_date',
        'approve_item_last_modified_by',
        'fk_status_id',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'approve_item_created_date';
    protected $updatedField  = 'approve_item_last_modified_date';
    protected $deletedField  = 'approve_item_deleted_at';

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

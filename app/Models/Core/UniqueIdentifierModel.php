<?php

namespace App\Models\Core;

use CodeIgniter\Model;
use App\Interfaces\ModelInterface;

class UniqueIdentifierModel extends Model implements ModelInterface
{
    protected $table            = 'unique_identifier';
    protected $primaryKey       = 'unique_identifier_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $DBGroup = 'write';
    protected $allowedFields    = ['unique_identifier_id', 'unique_identifier_name', 'fk_account_system_id', 'unique_identifier_is_active'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'unique_identifier_created_date';
    protected $updatedField  = 'unique_identifier_last_modified_date';
    protected $deletedField  = 'unique_identifier_deleted_at';

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

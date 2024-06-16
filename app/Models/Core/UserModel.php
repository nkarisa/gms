<?php

namespace App\Models\Core;

use CodeIgniter\Model;
use App\Interfaces\ModelInterface;

class UserModel extends Model implements ModelInterface
{
    protected $table            = 'user';
    protected $primaryKey       = 'user_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $DBGroup = 'write';
    protected $allowedFields    = [
        'user_id', 'user_firstname', 'user_lastname', 'user_name', 'user_email', 'user_is_context_manager', 
        'user_is_system_admin', 'user_is_active', 'fk_context_definition_id', 'fk_language_id', 'fk_role_id', 
        'fk_account_system_id', 'fk_country_currency_id', 'fk_status_id', 'user_employment_date', 'user_unique_identifier', 
        'user_password', 'user_personal_data_consent_date', 'user_is_switchable'
    ];
    

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'user_created_date';
    protected $updatedField  = 'user_last_modified_date';
    protected $deletedField  = 'user_deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['append_creator_id'];
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

<?php

namespace App\Models\Core;

use CodeIgniter\Model;
use App\Interfaces\ModelInterface;
use App\Libraries\Core\ApprovalLibrary;

class ApprovalModel extends Model implements ModelInterface
{
    protected $table            = 'approval';
    protected $primaryKey       = 'approval_id';
    protected $DBGroup          = 'write';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'approval_track_number',
        'approval_name',
        'fk_approve_item_id',
        'fk_status_id',
        'approval_created_by',
        'approval_created_date',
        'approval_last_modified_date',
        'approval_last_modified_by'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'approval_created_date';
    protected $updatedField  = 'approval_last_modified_date';
    protected $deletedField  = 'approval_deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['actionBeforeInsert'];
    protected $afterInsert    = ['actionAfterInsert'];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function actionBeforeInsert($postArray){
        $approvalLibrary = new ApprovalLibrary();
        if(method_exists($approvalLibrary, 'actionBeforeInsert')){
            $postArray = $approvalLibrary->actionBeforeInsert($postArray);
        }

        return $postArray;
    }

    public function actionAfterInsert($postArray, $approval_id, $header_id){
        $approvalLibrary = new ApprovalLibrary();
        if(method_exists($approvalLibrary, 'actionAfterInsert')){
            $postArray = $approvalLibrary->actionAfterInsert($postArray, $approval_id, $header_id);
        }

        return $postArray;
    }

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

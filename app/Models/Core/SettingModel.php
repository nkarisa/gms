<?php

namespace App\Models\Core;

use CodeIgniter\Model;
use App\Interfaces\ModelInterface;

class SettingModel extends Model implements ModelInterface
{

    protected $table            = 'setting';
    protected $primaryKey       = 'setting_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $DBGroup = 'write';
    protected $allowedFields    = [];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    // protected $useTimestamps = false;
    // protected $dateFormat    = 'datetime';
    // protected $createdField  = 'created_at';
    // protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at';

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
        $settingsAll = $this->findAll();
        $settings_types = array_column($settingsAll, 'type');
        $settings_descriptions = array_column($settingsAll, 'description');
        $settings = array_combine($settings_types, $settings_descriptions);

        return $settings;
    }

    public function one($id):array{
        return [];
    }

    public function append_creator_id(){
        
    }
}

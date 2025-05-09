<?php

namespace App\Models\Grants;

use App\Libraries\Core\StatusLibrary;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;
use Config\Database;
use Config\GrantsConfig;
use Config\Services;

class BeneficiaryModel extends Model
{
    protected $table = 'beneficiary';
    protected $primaryKey = 'beneficiary_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'beneficiarycreated_date';
    protected $updatedField = 'beneficiary_last_modified_date';
    protected $deletedField = 'beneficiary_deleted_date';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    private $session;

    private $request;

    private GrantsConfig $config;

    public function __construct(?ConnectionInterface $db = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($db, $validation);
        $this->readDB = Database::connect('read')->table("reimbursement_claim");
        $this->write_db = Database::connect('write')->table("reimbursement_claim");
        $this->writeDB = Database::connect('write')->table("reimbursement_claim");

        $this->session = Services::session();
        $this->request = Services::request();
        $this->config = config(GrantsConfig::class);
    }

    public function queryBuilder(string $option, string $table = '', string $alias = ''): \CodeIgniter\Database\BaseBuilder|\CodeIgniter\Database\BaseConnection|null
    {
        if (strlen($table) > 0) {
            if ($option == 'read') {
                return Database::connect('read')->table("$table $alias");
            }
            if ($option == 'write') {
                return Database::connect('write')->table("$table $alias");
            }
        } else {
            if ($option == 'read') {
                return Database::connect('read');
            }
            if ($option == 'write') {
                return Database::connect('write');
            }
        }

        return null;

    }

    public function getNationalOffices(): array
    {
        $queryBuilder = $this->queryBuilder('read', 'account_system');

        $queryBuilder->select(array('account_system_id', 'account_system_name'));

        $queryBuilder->where(['account_system_is_active' => 1]);

        $country_offices_and_ids = $queryBuilder->get()->getResultArray();

        $account_system_id = array_column($country_offices_and_ids, 'account_system_id');

        $account_system_name = array_column($country_offices_and_ids, 'account_system_name');

        return array_combine($account_system_id, $account_system_name);

    }

}




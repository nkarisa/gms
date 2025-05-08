<?php

namespace App\Models\Grants;

use App\Libraries\Core\StatusLibrary;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;
use Config\Database;
use Config\Services;

class MedicalClaimSettingModel extends Model
{
    protected $table = 'medical_claim_setting';
    protected $primaryKey = 'medical_claim_setting_id';
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
    protected $createdField = 'medicalclaimsettingcreated_date';
    protected $updatedField = 'medicalclaimsetting_last_modified_date';
    protected $deletedField = 'medicalclaimsetting_deleted_date';

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

    private \CodeIgniter\Database\BaseBuilder $readDB;

    private \CodeIgniter\Database\BaseBuilder $write_db;

    private $statusLibrary;

    private $session;

    private $request;


    public function __construct(?ConnectionInterface $db = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($db, $validation);
        $this->readDB = Database::connect('read')->table("medical_claim_setting");
        $this->write_db = Database::connect('write')->table("medical_claim_setting");
        $this->statusLibrary = new StatusLibrary();
        $this->session = Services::session();
        $this->request = Services::request();

    }

    private function queryBuilder(string $option, string $table = '', string $alias = '')
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


    }

    public function lookup_tables()
    {
        return array('account_system', 'medical_claim_admin_setting');
    }

    public function getThresholdAmountOrReimburseAllFlag($setting_id = '')
    {
        $medical_setting_id = $setting_id;

        if ($setting_id == '') {
            $medical_setting_id = 5;
        }

        $query = $this->readDB->where(['fk_medical_claim_admin_setting_id' => $medical_setting_id, 'fk_account_system_id' => $this->session->user_account_system_id]);
        $test = $query->get();

        $value = 0;

        if ($test->getNumRows() > 0) {
            $value = $test->getRowObject()->medical_claim_setting_value;
        }

        return $value;
    }

    public function check_if_record_exists($fk_admin_claim_setting_value)
    {

        $setting_value = 0;
        $this->read_db->select(['fk_medical_claim_admin_setting_id']);
        $this->read_db->where(['fk_medical_claim_admin_setting_id' => $fk_admin_claim_setting_value, 'fk_account_system_id' => $this->session->user_account_system_id]);
        $result = $this->read_db->get('medical_claim_setting')->row_array();

        if (!empty($result)) {
            $setting_value = $result['fk_medical_claim_admin_setting_id'];
        }

        return $setting_value;
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

    public function list_table_visible_columns()
    {

        return ['medical_claim_admin_setting_name', 'medical_claim_setting_value'];
    }


    function get_medical_setting_for_edit()
    {

        $this->read_db->select(array('medical_claim_setting_id', 'medical_claim_setting_name', 'fk_medical_claim_admin_setting_id', 'medical_claim_setting_value', 'medical_claim_admin_setting_name', 'fk_account_system_id', 'account_system_name'));

        $this->read_db->where(['medical_claim_setting_id' => hash_id($this->id, 'decode')]);

        if (!$this->session->system_admin) {
            $this->read_db->where(array('fk_account_system_id' => $this->session->user_account_system_id));
        }
        $this->read_db->join('medical_claim_admin_setting', 'medical_claim_admin_setting.medical_claim_admin_setting_id=medical_claim_setting.fk_medical_claim_admin_setting_id');
        $this->read_db->join('account_system', 'account_system.account_system_id=medical_claim_setting.fk_account_system_id');
        $medical_records = $this->read_db->get('medical_claim_setting')->row_array();

        return $medical_records;
    }

    public function edit_medical_claim_setting_record(array $post_arr)
    {

        $message = 0;
        //Update/Modify medical claim setting records
        $medical_claim_setting_id = $post_arr['medical_claim_setting_id'];

        $edit_data['medical_claim_setting_name'] = $post_arr['medical_claim_setting_name'];
        $edit_data['fk_medical_claim_admin_setting_id'] = $post_arr['medical_claim_setting_type_id'];
        $edit_data['medical_claim_setting_value'] = $post_arr['medical_claim_setting_value'];
        $edit_data['fk_account_system_id'] = $post_arr['fk_account_system_id'];

        //Include history fields
        $update_data = $this->grants_model->merge_with_history_fields('medical_claim_setting', $edit_data, false);

        $this->write_db->where(array('medical_claim_setting_id' => $medical_claim_setting_id));
        $this->write_db->update('medical_claim_setting', $update_data);

        if ($this->write_db->affected_rows() > 0) {
            $message = 1;
        }
        return $message;


    }

    function retrieve_account_systems()
    {

        $this->read_db->select(array('account_system_id', 'account_system_name'));

        $this->read_db->where(['account_system_is_active' => 1]);

        $account_systems = $this->read_db->get('account_system')->result_array();

        $account_system_id = array_column($account_systems, 'account_system_id');

        $account_system_name = array_column($account_systems, 'account_system_name');

        return array_combine($account_system_id, $account_system_name);
    }

    public function admin_settings()
    {

        $this->read_db->select(['medical_claim_admin_setting_id', 'medical_claim_admin_setting_name']);

        // if(!$this->session->system_admin){
        //     $this->read_db->where(array('medical_claim_admin_setting.fk_account_system_id'=>$this->session->user_account_system_id));
        // }

        $admin_settings = $this->read_db->get('medical_claim_admin_setting');

        $settings_at_admin_level_arr_combined = [];

        if ($admin_settings) {
            $settings_at_admin_level = $admin_settings->result_array();

            $admin_setting_ids = array_column($settings_at_admin_level, 'medical_claim_admin_setting_id');

            $admin_setting_names = array_column($settings_at_admin_level, 'medical_claim_admin_setting_name');

            $settings_at_admin_level_arr_combined = array_combine($admin_setting_ids, $admin_setting_names);
        }


        return $settings_at_admin_level_arr_combined;
    }
}

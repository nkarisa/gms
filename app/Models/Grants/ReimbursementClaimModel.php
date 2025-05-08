<?php

namespace App\Models\Grants;

use App\Libraries\Core\StatusLibrary;
use App\Libraries\Grants\MedicalClaimScenariosLibrary;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;
use Config\Database;
use Config\GrantsConfig;
use Config\Services;

class ReimbursementClaimModel extends Model
{
    protected $table = 'reimbursement_claim';
    protected $primaryKey = 'reimbursement_claim_id';
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
    protected $createdField = 'reimbursementclaimcreated_date';
    protected $updatedField = 'reimbursementclaim_last_modified_date';
    protected $deletedField = 'reimbursementclaim_deleted_date';

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

    private \CodeIgniter\Database\BaseBuilder $writeDB;

    private $statusLibrary;

    private $session;

    private $request;
    private GrantsConfig $config;


    public function __construct(?ConnectionInterface $db = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($db, $validation);
        $this->readDB = Database::connect('read')->table("reimbursement_claim");
        $this->write_db = Database::connect('write')->table("reimbursement_claim");
        $this->writeDB = Database::connect('write')->table("reimbursement_claim");
        $this->statusLibrary = new StatusLibrary();
        $this->session = Services::session();
        $this->request = Services::request();
        $this->config = config(GrantsConfig::class);

    }

    public function queryBuilder(string $option, string $table = '', string $alias = '')
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


    /**
     * medical_claim_scenerios(): This method calls the medical claim
     * settings library and assign the settngs with related values
     * @param float $voucher_amount : passes amount on voucher;
     *        float $total_receipt_amount: total receipt amount;
     *        string $card_number: insurance number
     * @return array
     * @author Livingstone Onduso
     * @access public
     */


    public function medicalClaimScenerios(float $voucher_amount, float $total_receipt_amount, string $card_number = ''): array
    {
        $card_setting_arr = $this->getCountryMedicalSettings(2);
        $threshold_setting_arr = $this->getCountryMedicalSettings(5);
        $threshold_met = $this->getCountryMedicalSettings(6);
        $ctrbtn = $this->getCountryMedicalSettings(1);
        $ctrbtn_with_card = $this->getCountryMedicalSettings(7);


        //Get the settings
        $allow_use_insurance_card = !empty($card_setting_arr) && ($card_setting_arr[0] > 0) ? true : false;
        $threshold_amount = !empty($threshold_setting_arr) ? $threshold_setting_arr[0] : 0;
        $reimburse_all_when_threshold_met = !empty($threshold_met) && ($threshold_met[0] > 0) ? true : false;
        $caregiver_contribution_percentage = !empty($ctrbtn) && ($ctrbtn[0] > 0) ? $ctrbtn[0] : 0;
        $caregiver_with_card_contribution_percentage = !empty($ctrbtn_with_card) && ($ctrbtn_with_card[0] > 0) ? $ctrbtn_with_card[0] : 0;


        $params = compact('allow_use_insurance_card', 'threshold_amount', 'reimburse_all_when_threshold_met', 'caregiver_contribution_percentage', 'caregiver_with_card_contribution_percentage');

        $medicalClaimSecenarios = new MedicalClaimScenariosLibrary($params);


        //Pass values from the form
        //$v_amount=2000;
        //$total_receipt_amount=2000;
        //$insurance_card=9887676;

        $medical_claim_scenerios = $medicalClaimSecenarios->compute_contribution_and_reibursement_amount($voucher_amount, $total_receipt_amount, $card_number);

        return $medical_claim_scenerios;
    }

    /**
     * get_user_clusters(): This method return clusters that user logged in user has visibility to
     * @param bool $get_clusters_ids : passes a boolean true or false;
     * @return array
     * @author Livingstone Onduso
     * @access public
     */

    public function getUserClusters(bool $get_clusters_ids = false): array
    {
        $hierarchy_offices = array_column($this->session->hierarchy_offices, 'office_id');
        $queryBuilder = $this->queryBuilder('read', 'context_center');
        $queryBuilder->select(array('office.office_name', 'office.office_id', 'context_cluster.context_cluster_id'));
        $queryBuilder->join('context_cluster', 'context_cluster.context_cluster_id=context_center.fk_context_cluster_id');
        $queryBuilder->join('office', 'office.office_id=context_cluster.fk_office_id');
        $queryBuilder->whereIn('context_center.fk_office_id', $hierarchy_offices);
        $clusters = $queryBuilder->get()->getResultArray();

        $array_column_clusters = array_column($clusters, 'office_name');

        //If Context Cluster Ids
        if ($get_clusters_ids) {

            $cluster_ids = array_column($clusters, 'context_cluster_id');

            return array_combine($cluster_ids, $array_column_clusters);

        } else { //If Office Ids

            $office_id = array_column($clusters, 'office_id');

            return array_combine($office_id, $array_column_clusters);
        }

    }

    /**
     * get_fcp_number(): This method returns fcp for the user
     * @return array
     * @author Livingstone Onduso
     * @access public
     */

    public function getFcpNumber(): array
    {
        $user_fcps = [];

        // if (!$this->session->system_admin) {

        $queryBuilder = $this->queryBuilder('read', 'office');
        $hierarchy_offices = array_column($this->session->hierarchy_offices, 'office_id');

        $queryBuilder->select(array('office_code', 'office_id'));
        $queryBuilder->whereIn('office_id', $hierarchy_offices);
        $queryBuilder->where(array('fk_context_definition_id' => 1)); //FCPs only
        $user_fcps = $queryBuilder->get()->getResultArray();
        // }

        $office_code = array_column($user_fcps, 'office_code');

        $office_id = array_column($user_fcps, 'office_id');

        return array_combine($office_id, $office_code);
    }

    /**
     * pull_fcp_beneficiaries(): This method returns participants/beneficiaries for a given fcp.
     * @param string $fcp_number : passes the FCP code
     * @return array
     * @author Livingstone Onduso
     * @access public
     */

    public function pullFcpBeneficiaries(string $fcp_number = ''): array
    {
        $queryBuilder = $this->queryBuilder('read', 'beneficiary');
        $beneficiaries = [];

        $queryBuilder->select(array('beneficiary_number', 'beneficiary_name'));

        if ($fcp_number != '') {
            $queryBuilder->like('beneficiary_number', $fcp_number);
        }

        $beneficiaries = $queryBuilder->get()->getResultArray();

        return $beneficiaries;
    }

    /**
     * pull_health_facility_types(): This method returns health facility types.
     * @return array
     * @author Livingstone Onduso
     * @access public
     */

    public function pullHealthFacilityTypes(): array
    {

        $health_facility_types = [];

        $queryBuilder = $this->queryBuilder('read', 'health_facility');

        $queryBuilder->select(array('health_facility_name', 'health_facility_id'));

        $queryBuilder->where(array('fk_account_system_id' => $this->session->user_account_system_id));

        $health_facility_types = $queryBuilder->get()->getResultArray();

        $health_facility_name = array_column($health_facility_types, 'health_facility_name');

        $health_facility_id = array_column($health_facility_types, 'health_facility_id');


        return array_combine($health_facility_id, $health_facility_name);
    }

    /**
     * get_medical_rembursable_expense_account(): This method gets the reimbursement expense account.
     * @return array
     * @author Livingstone Onduso
     * @access private
     */

    private function getMedicalRembursableExpenseAccount(): array
    {

        //Get medical rembursable expense account
        $queryBuilder = $this->queryBuilder('read', 'expense_account');
        $queryBuilder->select(['expense_account_code']);
        $queryBuilder->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
        $queryBuilder->join('account_system', 'account_system.account_system_id=income_account.fk_account_system_id');
        $queryBuilder->where(['expense_account_is_medical_rembursable' => 1, 'expense_account_is_active' => 1]);
        if (!$this->session->system_admin) {
            $queryBuilder->where(['income_account.fk_account_system_id' => $this->session->user_account_system_id]);
        }

        $medical_rembursable_expense_acc = $this->readDB->get();
        $medical_rembursable_expense_acc_array_col = [];
        if ($medical_rembursable_expense_acc) {
            $expense_acc = $medical_rembursable_expense_acc->getResultArray();

            $medical_rembursable_expense_acc_array_col = array_column($expense_acc, 'expense_account_code');
        }

        return $medical_rembursable_expense_acc_array_col;
    }

    /**
     * get_country_medical_settings(): This method return medical settings.
     * @param int $medical_setting : passes the id of the setting.
     * @return array
     * @author Livingstone Onduso
     * @access public
     */

    public function getCountryMedicalSettings(int $medical_setting): array
    {
        /*

        Note: These values will be always the same since they are static in the table created by super admin)

          1) percentage_caregiver_contribution=1

          2) national_health_cover_flag=2

          3) valid_claiming_days=3

          4) medical_claiming_expense_accounts=4 [This was modified to get the expense accounts
                                                  from expense account table.
                                                  The table column'expense_account_is_medical_rembursable'
                                                  was added to the table allow the change]

          5) minimum_claimable_amount=5

        */

        $queryBuilder = $this->queryBuilder('read', 'medical_claim_setting');
        $medical_claim_country_setting = [];

        $queryBuilder->select(array('medical_claim_setting_value'));

        $queryBuilder->join('medical_claim_admin_setting', 'medical_claim_admin_setting.medical_claim_admin_setting_id=medical_claim_setting.fk_medical_claim_admin_setting_id');

        $queryBuilder->where(array('medical_claim_setting.fk_medical_claim_admin_setting_id' => $medical_setting));

        if (!$this->session->system_admin) {
            $queryBuilder->where(array('medical_claim_setting.fk_account_system_id' => $this->session->user_account_system_id, 'medical_claim_setting.fk_medical_claim_admin_setting_id' => $medical_setting));
        }
        $medical_settings = $queryBuilder->get();
        $medical_claim_country_setting = $medical_settings->getResultArray();


        return array_column($medical_claim_country_setting, 'medical_claim_setting_value');
    }

    /**
     * get_vouchers_for_medical_claim(): This method returns vouchers related to reimbursement claims.
     * @return array
     * @author Livingstone Onduso
     * @access public
     */
    public function getVouchersForMedicalClaim(): array
    {
        $queryBuilder = $this->queryBuilder('read', 'voucher_detail', 'vd');
        $max_approved_status = $this->statusLibrary->getMaxApprovalStatusId('voucher');

        $medicalSettingModel = new MedicalClaimSettingModel();


        //Get Threshold amount and reimburse_all_flag
        $medical_threshold_amount = $medicalSettingModel->getThresholdAmountOrReimburseAllFlag(5);

        $medical_reimburse_all_flag = $medicalSettingModel->getThresholdAmountOrReimburseAllFlag(6);

        //Get the Expense account codes for medical reimbursement from the medical_claim_setting table

        $medical_claims_expense_acc = $this->getMedicalRembursableExpenseAccount();


        $valid_days_for_medical_claims = $this->getCountryMedicalSettings(3);


        //Default number of valid days for a claim
        $valid_days_for_claiming = 60;
        // ToDo: Not able to find this in current config library
        //$valid_days_for_claiming = $this->config->item('valid_days_for_medical_claims');

        if (sizeof($valid_days_for_medical_claims) > 0) {

            $valid_days_for_claiming = $valid_days_for_medical_claims[0];
        }
        //Get the office
        $office_ids = array_column($this->session->hierarchy_offices, 'office_id');

        $queryBuilder->select(array('v.voucher_number', 'v.voucher_id', 'vd.voucher_detail_id', 'vd.voucher_detail_total_cost', 'e.expense_account_code')); //,'e.expense_account_code'
        //$this->read_db->select_sum('vd.voucher_detail_total_cost');
        $queryBuilder->join('voucher as v', 'v.voucher_id=vd.fk_voucher_id');
        $queryBuilder->join('expense_account as e', 'e.expense_account_id=vd.fk_expense_account_id');
        $queryBuilder->join('office as o', 'o.office_id=v.fk_office_id');
        //$this->read_db->where('voucher_created_date BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE()');
        $queryBuilder->where("DATEDIFF(CURRENT_DATE(), voucher_created_date) BETWEEN -1 AND " . $valid_days_for_claiming);
        $queryBuilder->where(['v.fk_status_id' => $max_approved_status[0]]);
        $queryBuilder->whereIn('o.office_id', $office_ids);

        if (sizeof($medical_claims_expense_acc) > 0) {
            $queryBuilder->whereIn('e.expense_account_code', $medical_claims_expense_acc);
        }


        //$this->read_db->group_by(array('v.voucher_number'));

        $vouchers_with_medical_expense_related_accs = $queryBuilder->get(); //->result_array();

        $results = [];

        if ($vouchers_with_medical_expense_related_accs) {
            $results = $vouchers_with_medical_expense_related_accs->getResultArray();
        }

        $rebuild_results = [];
        foreach ($results as $result) {

            if ($medical_reimburse_all_flag == false) {//for Malawi case
                $result['voucher_detail_total_cost'] = $result['voucher_detail_total_cost'] - $medical_threshold_amount;
            } else {
                $result['voucher_detail_total_cost'] = $result['voucher_detail_total_cost'];
            }


            $rebuild_results[] = $result;
        }


        return $rebuild_results;


    }

    /**
     * check_if_connect_id_exists(): This method verifies if the connect id exists
     * @param int
     * @return int
     * @author Livingstone Onduso
     * @access public
     */
    public function checkIfConnectIDExists(int $connect_incident_id): int
    {

        $transform_incident_id = 'I-' . $connect_incident_id;

        $this->readDB->select(['reimbursement_claim_incident_id']);
        $this->readDB->where(['reimbursement_claim_incident_id' => trim($transform_incident_id)]);
        $medical_claim_incident_id = $this->readDB->get()->getRowArray();

        if ($medical_claim_incident_id) {
            return 1;
        }
        return 0;
    }

    /**
     * get_already_reimbursed_amount(): This method returns the a mount in associative array form.
     * @return array
     * @author Livingstone Onduso
     * @access public
     */

    public function get_already_reimbursed_amount(): array
    {

        //Get the office
        $office_ids = array_column($this->session->hierarchy_offices, 'office_id');

        return $this->amountReimbursableToFcp('reimbursement_claim_amount_reimbursed', $office_ids);
    }

    /**
     * fcp_rembursable_amount_from_caregiver(): This method returns the amount for caregiver contribution in associative array form.
     * @return array
     * @author Livingstone Onduso
     * @access public
     */

    public function fcp_rembursable_amount_from_caregiver(): array
    {

        //Get the office
        $office_ids = array_column($this->session->hierarchy_offices, 'office_id');

        return $this->amountReimbursableToFcp('reimbursement_claim_caregiver_contribution', $office_ids);
    }

    /**
     * amount_rembursable_to_fcp(): This method verifies if the connect id exists
     * @param string $table_column_name : passes column name; array $office_ids: passes an array of office ids.
     * @return array
     * @author Livingstone Onduso
     * @access private
     */

    private function amountReimbursableToFcp(string $table_column_name, array $office_ids): array
    {

        $this->readDB->select($table_column_name);
        $this->readDB->select(array('fk_voucher_detail_id'));
        $this->readDB->whereIn('fk_office_id', $office_ids);
        //$this->read_db->group_by(array('fk_voucher_id'));
        $already_reimbursed_amount = $this->readDB->get()->getResultArray();

        $voucher_id_arr = array_column($already_reimbursed_amount, 'fk_voucher_detail_id');

        $medical_claim_amount_reimbursed_arr = array_column($already_reimbursed_amount, $table_column_name);

        return array_combine($voucher_id_arr, $medical_claim_amount_reimbursed_arr);
    }

    /**
     * populate_cluster_name(): This method returns cluster name to be displayed on the list
     * @param int $office_id : passes office id of an fcp.
     * @return array
     * @author Livingstone Onduso
     * @access public
     */

    public function populateClusterName(int $office_id): array
    {
        $queryBuilder = $this->queryBuilder('read', 'office');
        $queryBuilder->select(array('context_cluster.context_cluster_name', 'context_cluster.context_cluster_id'));
        $queryBuilder->join('context_center', 'context_center.fk_office_id=office.office_id');
        $queryBuilder->join('context_cluster', 'context_cluster.context_cluster_id= context_center.fk_context_cluster_id');
        $queryBuilder->where(['office.office_id' => $office_id]);
        $cluster_name = $queryBuilder->get()->getResultArray();

        $cluster_name_with_context = $cluster_name[0]['context_cluster_name'];
        $cluster_id_key = $cluster_name[0]['context_cluster_id'];

        //remove context from the cluster name
        $cluster_name_with_no_context = explode('Context for office', $cluster_name_with_context)[1];

        $cluster_id[0]['context_cluster_id'] = $cluster_id_key;
        $cluster_id[0]['context_cluster_name'] = $cluster_name_with_no_context;

        $cluster_i = array_column($cluster_id, 'context_cluster_id');
        $cluster_n = array_column($cluster_id, 'context_cluster_name');

        return array_combine($cluster_i, $cluster_n);
    }

    public function getVoucherNumberForARow($voucher_detail_id)
    {

        // $this->read_db->select(array('voucher_number'));
        // $this->read_db->where(array('voucher_id' => $voucher_id));
        // return $this->read_db->get('voucher')->row()->voucher_number;

        $queryBuilder = $this->queryBuilder('read', 'voucher');

        $queryBuilder->select(array('voucher_number'));
        $queryBuilder->join('voucher_detail', 'voucher_detail.fk_voucher_id=voucher.voucher_id');
        $queryBuilder->where(array('voucher_detail_id' => $voucher_detail_id));
        return $queryBuilder->get()->getRow()->voucher_number;
    }


    public function update_medical_claim_attachment_id($last_insert_id)
    {
        $post = $this->input->post();

        $data['fk_attachment_id'] = $last_insert_id;
        $this->write_db->where(array('reimbursement_claim_id' => $post['reimbursement_claim_id']));
        $this->write_db->update('reimbursement_claim', $data);
    }

    function getReimbursementComments($reimbursement_id)
    {
        $query = $this->queryBuilder('read', 'reimbursement_comment');
        $query->select(['reimbursement_comment_id', 'reimbursement_comment_detail', 'reimbursement_comment_created_date', 'user_lastname', 'fk_reimbursement_claim_id']);
        $query->join('user', 'user.user_id=reimbursement_comment.reimbursement_comment_created_by');
        $query->where(['fk_reimbursement_claim_id' => $reimbursement_id]);
        $reimbursement_comments = $query->get()->getResultArray();

        return $reimbursement_comments;
    }


    public function getReimbursementClaimAttachments($medicalID = ''): array
    {

        $approveQuery = $this->queryBuilder('read', 'approve_item');
        $approveItemId = $approveQuery->where('approve_item_name', 'reimbursement_claim')->get()->getRowObject()->approve_item_id;

        //Get medical_claims_attachments
        $attachmentQuery = $this->queryBuilder('read', 'attachment');
        $attachments = $attachmentQuery->select(['attachment_id', 'attachment_name', 'attachment_url', 'attachment_primary_id']); //'medical_claim.support_documents_need_flag'
        //$this->read_db->join('medical_claim','medical_claim.fk_attachment_id= attachment.attachment_id');
        $attachments->where(['fk_approve_item_id' => $approveItemId]);
        $attachments->where(['fk_account_system_id' => $this->session->user_account_system_id]);

        if ($medicalID != '') {
            $attachments->where(['attachment_primary_id' => $medicalID]);
        }

        $claimAttachments = $attachments->get()->getResultArray();

        return $claimAttachments;
    }


    public function getMedicalClaims($reimbursement_claim_id = '', $filter_medical_records_by_cluster = [], $filter_medical_records_by_status = [])
    {

        //Get the previous month and current month
        $previous_month_arr = $this->readDB->select("DATE_FORMAT( CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-20')")->get()->getResultArray()[0];

        $current_month_arr = $this->readDB->select("LAST_DAY(CURRENT_DATE)")->get()->getResultArray()[0];

        $previous_month = array_values($previous_month_arr)[0];

        $current_month = array_values($current_month_arr)[0];

        //Get maximum id
        $max_status_id = $this->statusLibrary->getMaxApprovalStatusId('reimbursement_claim');


        $data = [];

        $office_ids = array_column($this->session->hierarchy_offices, 'office_id');


        $rcQuery = $this->readDB->select(['reimbursement_claim_id', 'reimbursement_claim_name',
            'reimbursement_app_type_name', 'reimbursement_funding_type_name',
            'voucher_number', 'status_name', 'reimbursement_claim_track_number',
            'reimbursement_claim_facility', 'reimbursement_claim_incident_id',
            'reimbursement_claim_beneficiary_number', 'reimbursement_claim_count',
            'reimbursement_claim_treatment_date', 'reimbursement_claim_created_date',
            'reimbursement_claim_diagnosis', 'reimbursement_claim_amount_reimbursed', 'reimbursement_claim_amount_spent',
            'reimbursement_claim_caregiver_contribution', 'reimbursement_claim_amount_reimbursed',
            'fk_context_cluster_id', 'office_name', 'reimbursement_claim.fk_status_id', "CONCAT(u.user_firstname, ' ', u.user_lastname) as last_modified_by", 'reimbursement_claim_last_modified_date',
            'support_documents_need_flag', 'fk_voucher_detail_id', 'voucher_number', 'FORMAT(
    IFNULL(reimbursement_claim_amount_reimbursed, 0) + 
    IFNULL(reimbursement_claim_caregiver_contribution, 0), 
    2
) AS amount']);


        $rcQuery->join('voucher_detail', 'voucher_detail.voucher_detail_id=reimbursement_claim.fk_voucher_detail_id');
        $rcQuery->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');

        $rcQuery->join('context_cluster', 'reimbursement_claim.fk_context_cluster_id=context_cluster.context_cluster_id');

        $rcQuery->join('office', 'office.office_id=context_cluster.fk_office_id');

        $rcQuery->join('status', 'reimbursement_claim.fk_status_id=status.status_id');
        $rcQuery->join('reimbursement_app_type', 'reimbursement_app_type.reimbursement_app_type_id=reimbursement_claim.fk_reimbursement_app_type_id');

        $rcQuery->join('reimbursement_funding_type', 'reimbursement_funding_type.reimbursement_funding_type_id=reimbursement_claim.fk_reimbursement_funding_type_id');
        $rcQuery->join('user u', 'u.user_id = reimbursement_claim.reimbursement_claim_last_modified_by', 'left');

        $rcQuery->groupStart();
        $rcQuery->where(['reimbursement_claim_created_date >=' => $previous_month, 'reimbursement_claim_created_date <' => $current_month]);
        $rcQuery->groupEnd();
        $rcQuery->orGroupStart();
        $rcQuery->where(['reimbursement_claim.fk_status_id<>' => $max_status_id[0]]);
        $rcQuery->groupEnd();
        $rcQuery->groupBy('reimbursement_claim.reimbursement_claim_id');

        //$this->read_db->order_by('medical_claim.medical_claim_created_date', 'desc');

        if (!$this->session->system_admin) {
            $rcQuery->whereIn('reimbursement_claim.fk_office_id', $office_ids);
        }

        if ($reimbursement_claim_id != '') {
            $rcQuery->where(['reimbursement_claim_id' => $reimbursement_claim_id]);
        }
        //$filter_cluster=implode(",",$filter_medical_records_by_cluster);
        //log_message('error',$filter_medical_records_by_cluster);
        //$filter_medical_records_by_cluster=explode(',',$filter_medical_records_by_cluster);
        //$filter_medical_records_by_status=explode(',',$filter_medical_records_by_status);

        if (!empty($filter_medical_records_by_cluster) && empty($filter_medical_records_by_status)) {

            $rcQuery->whereIn('reimbursement_claim.fk_context_cluster_id', $filter_medical_records_by_cluster);
            //log_message('error', json_encode($test));


            $data = $rcQuery->get()->getResultArray();
        }
        if (!empty($filter_medical_records_by_status) && empty($filter_medical_records_by_cluster)) {
            $rcQuery->whereIn('reimbursement_claim.fk_status_id', $filter_medical_records_by_status);
            $data = $rcQuery->get()->getResultArray();
        }
        if (!empty($filter_medical_records_by_status) && !empty($filter_medical_records_by_cluster)) {

            $rcQuery->whereIn('reimbursement_claim.fk_context_cluster_id', $filter_medical_records_by_cluster);
            $rcQuery->whereIn('reimbursement_claim.fk_status_id', $filter_medical_records_by_status);


            // log_message('error',json_encode($filter_medical_records_by_cluster));

            // log_message('error',json_encode($filter_medical_records_by_status));

            $data = $rcQuery->get()->getResultArray();
        }
        if (empty($filter_medical_records_by_cluster) && empty($filter_medical_records_by_status)) {
            $data = $rcQuery->get()->getResultArray();
        }


        //Filter results based on role
        $roleModel = $this->queryBuilder('read', 'role');

        $roleQuery = $roleModel->select(['role_name',]);
        $roleQuery->where(['role_id' => $this->session->role_id, 'fk_context_definition_id' => 4]);
        $role = $roleQuery->get()->getRowObject();

        $rebuild_data = [];

        if ($role) {
            foreach ($data as $medical_info) {

                $app_type = $medical_info['reimbursement_app_type_name'];

                if ($app_type === 'HVC-CPR' && (strpos(strtoupper($role->role_name), 'CPS'))) {

                    $rebuild_data[] = $medical_info;
                } elseif (($app_type === 'MEDICAL-CLAIM' || $app_type === 'CIV-MEDICAL' || $app_type === 'MED-TFI') && strpos(strtoupper($role->role_name), 'HEALTH')) {
                    $rebuild_data[] = $medical_info;
                } elseif (!strpos(strtoupper($role->role_name), 'CPS') && !strpos(strtoupper($role->role_name), 'HEALTH')) {
                    $rebuild_data = $data;
                }
            }
        } else {
            $rebuild_data = $data;
        }


        return $rebuild_data;
    }


    public function delete_reciept_or_support_docs($attacheme_id)
    {

        $this->write_db->trans_start();

        $this->write_db->where(['attachment_id' => $attacheme_id]);
        $attachement_to_delete = $this->write_db->delete('attachment');

        $this->write_db->trans_complete();

        if ($this->write_db->trans_status() == FALSE) {
            return 0;
        } else {
            return 1;
        }
    }

    public function deleteReceiptOrSupportDocs($id): bool|string
    {

        $queryBuilder = $this->queryBuilder('write', 'attachment');
        $queryBuilder->where(['attachment_id' => $id]);
        return $queryBuilder->delete();

    }

    public function check_if_medical_app_only()
    {

        $this->readDB->where(['account_system_id' => $this->session->user_account_system_id]);
        $account_system_has_medical_app_only = $this->readDB->get('account_system')->row()->account_system_has_medical_app_only;

        return $account_system_has_medical_app_only;
    }

    public function reimbursementType()
    {
        $queryBuilder = $this->queryBuilder('read', 'reimbursement_funding_type');
        $queryBuilder->select(['reimbursement_funding_type_id', 'reimbursement_funding_type_name']);
        $queryBuilder->where(['reimbursement_funding_type_is_active' => 1]);
        $reimbursement_types = $queryBuilder->get()->getResultArray();

        $reimbursement_types_id = array_column($reimbursement_types, 'reimbursement_funding_type_id');

        $reimbursement_types_name = array_column($reimbursement_types, 'reimbursement_funding_type_name');

        return array_combine($reimbursement_types_id, $reimbursement_types_name);
    }

    public function reimbursementAppTypes(): array
    {
        $queryBuilder = $this->queryBuilder('read', 'reimbursement_app_type');
        $queryBuilder->select(['reimbursement_app_type_id', 'reimbursement_app_type_name']);

        $queryBuilder->where(['fk_account_system_id' => $this->session->user_account_system_id]);
        $queryBuilder->where(['reimbursement_app_type_is_active' => 1]);

        $app_types = $queryBuilder->get()->getResultArray();

        $reimbursement_app_type_id = array_column($app_types, 'reimbursement_app_type_id');

        $reimbursement_app_type_name = array_column($app_types, 'reimbursement_app_type_name');


        return array_combine($reimbursement_app_type_id, $reimbursement_app_type_name);
    }

    /**
     * reimbursement_illiness_category(): This method calls a model and renders the
     *                                    settings for claims for a given country
     * @param int $diagnosis_type : passes the diagnosis type;
     * @return array
     * @author Livingstone Onduso
     * @access public
     */

    public function reimbursementIllinessCategory(int $diagnosis_type): array
    {
        $queryBuilder = $this->queryBuilder('read', 'reimbursement_illiness_category');
        $queryBuilder->select(['reimbursement_illiness_category_id', 'reimbursement_illiness_category_name']);
        $queryBuilder->where(['reimbursement_illiness_category_is_active' => 1]);
        $queryBuilder->where(['fk_reimbursement_diagnosis_type_id' => $diagnosis_type]);

        $illiness = $queryBuilder->get()->getResultArray();


        $reimbursement_illiness_category_id = array_column($illiness, 'reimbursement_illiness_category_id');

        $reimbursement_illiness_category_name = array_column($illiness, 'reimbursement_illiness_category_name');

        return array_combine($reimbursement_illiness_category_id, $reimbursement_illiness_category_name);
    }

    public function reimbursementDiagnosisType()
    {
        $queryBuilder = $this->queryBuilder('read', 'reimbursement_diagnosis_type');
        $queryBuilder->select(['reimbursement_diagnosis_type_id', 'reimbursement_diagnosis_type_name']);
        $queryBuilder->where(['reimbursement_diagnosis_type_is_active' => 1]);

        $reimbursement_diagnosis_type = $queryBuilder->get()->getResultArray();


        $reimbursement_diagnosis_type_id = array_column($reimbursement_diagnosis_type, 'reimbursement_diagnosis_type_id');

        $reimbursement_diagnosis_type_name = array_column($reimbursement_diagnosis_type, 'reimbursement_diagnosis_type_name');

        return array_combine($reimbursement_diagnosis_type_id, $reimbursement_diagnosis_type_name);
    }

    public function getHealthFacilityByID($reimbursement_claim_id)
    {
        // Get the foreign key for health facility
        $this->readDB->where(['reimbursement_claim_id' => $reimbursement_claim_id]);
        $claim = $this->readDB->get()->getRowObject();

        $health_facility_name = get_phrase('NA', 'Not Applicable');

        if ($claim && isset($claim->fk_health_facility_id)) {
            $queryBuilder = $this->queryBuilder('read', 'health_facility');
            $facility = $queryBuilder
                ->where(['health_facility_id' => $claim->fk_health_facility_id])
                ->get()->getRowObject();

            if ($facility && isset($facility->health_facility_name)) {
                $health_facility_name = $facility->health_facility_name;
            }
        }

        return $health_facility_name;
    }

    // public function get_voucher_number_id($voucher_id)
    // {
    //     //Get by id

    //     $this->read_db->where(['voucher_id' => $voucher_id]);
    //     $voucher_number = $this->read_db->get('voucher')->row()->voucher_number;

    //     return $voucher_number;
    // }


    public function getOfficeCode($fcp_ids)
    {
        $queryBuilder = $this->queryBuilder('read', 'office');
        $queryBuilder->select(array('office_code'));
        $queryBuilder->whereIn('office_id', $fcp_ids);
        $office_code = $queryBuilder->get()->getResultArray();

        $fcp_number = $office_code ?: [];

        if (sizeof($fcp_number) == 1) {

            return ['message' => 1, 'fcps' => $fcp_number[0]['office_code']];
        } elseif ($fcp_number == 0) {
            return ['message' => 0];
        } else {
            return ['message' => -1];
        }
    }

    //Delete Comments

    function delete_reimbursement_comment()
    {

        $comment_id = $this->input->post('reimbursement_comment_id');

        $this->write_db->where(['reimbursement_comment_id' => $comment_id]);
        $this->write_db->delete('reimbursement_comment');

        $deleted = 0;

        if ($this->write_db->affected_rows()) {
            $deleted = 1;
        }
        return $deleted;
    }


    //EDIT METHODS
    function getReimbursementClaimRecordToEdit($id)
    {

        //To be implemented when Staff supports more than one fcp [Use office_hierachy]to get the data

        //When Staff support one fcp
        $queryBuilder = $this->readDB->select([
            'reimbursement_claim_id', 'office.office_code', 'office.office_id', 'reimbursement_claim_name', 'reimbursement_claim_beneficiary_number',
            'reimbursement_claim_diagnosis', 'reimbursement_claim_treatment_date', 'fk_voucher_id', 'voucher_number', 'reimbursement_claim_govt_insurance_number', 'reimbursement_claim_amount_reimbursed',
            'reimbursement_claim_facility', 'fk_health_facility_id', 'support_documents_need_flag', 'reimbursement_claim_incident_id', 'reimbursement_claim_caregiver_contribution'
        ]);
        $queryBuilder->join('office', 'reimbursement_claim.fk_office_id=office.office_id');
        $queryBuilder->join('voucher_detail', 'voucher_detail.voucher_detail_id=reimbursement_claim.fk_voucher_detail_id');
        $queryBuilder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        //$this->read_db->join('beneficiary', 'beneficiary.beneficiary_number=reimbursement_claim.medical_beneficiary_number');
        $queryBuilder->where(['reimbursement_claim_id' => hash_id($id, 'decode')]);
        return $queryBuilder->get()->getResultArray();

    }


    public function addReimbursementComment(): int
    {

        $queryBuilder = $this->queryBuilder('write', 'reimbursement_comment');

        $data['fk_reimbursement_claim_id'] = $this->request->getPost('fk_reimbursement_claim_id');
        $data['reimbursement_comment_detail'] = $this->request->getPost('reimbursement_comment_detail');
        $data['reimbursement_comment_created_by'] = $this->session->user_id;
        $data['reimbursement_comment_created_date'] = date('Y-m-d');
        $data['reimbursement_comment_track_number'] = $this->statusLibrary->generateItemTrackNumberAndName('reimbursement_comment')['reimbursement_comment_track_number'];
        $data['reimbursement_comment_modified_by'] = $this->session->user_id;

        /* ToDo: not sure why we are using transactions here
        $writeQueryBuilder->transBegin();
        $writeQuery = $writeQueryBuilder->table('reimbursement_comments')->insert($data);

        if ($writeQueryBuilder->transStatus() === FALSE) {
            $writeQueryBuilder->transRollback();
            return false;
        } else {
            $writeQueryBuilder->transCommit();
            return true;
        }
        */

        return (bool)$queryBuilder->insert($data);
    }

    public function updateReimbursementClaim($data, $claimID): bool
    {
        //Todo: Realised that editing claims was not fully implemented in v2. Need to rally with team on way forward so let this be disabled for by return false
        //return $this->write_db->where('reimbursement_claim_id', $claimID)->update($data);
        return false;

    }

    public function getCountryCurrencyCode()
    {


        $country_currency = 'USD';
        $queryBuilder = $this->queryBuilder('read', 'country_currency');

        $queryBuilder->select(array('country_currency_code'));

        if (!$this->session->system_admin) {
            $queryBuilder->where(array('fk_account_system_id' => $this->session->user_account_system_id));
        }
        $country_currency = $queryBuilder->get()->getRowObject()->country_currency_code;

        return $country_currency;
    }

    public function getSupportNeededFocsFlag($health_facility_id)
    {
        $queryBuilder = $this->queryBuilder('read', 'health_facility');
        $queryBuilder->select(array('support_docs_needed'));
        $queryBuilder->where(array('health_facility_id' => $health_facility_id));
        return $queryBuilder->get()->getRowArray()['support_docs_needed'];
    }

    public function getOfficeAccountSystemIDByClaimID($claim_id)
    {
        $queryBuilder = $this->queryBuilder('read', 'reimbursement_claim');
        $queryBuilder->select(['office_id', 'office_code', 'fk_account_system_id as account_system_id']);
        $queryBuilder->where(['reimbursement_claim_id' => $claim_id]);
        $queryBuilder->join('office', 'office.office_id=reimbursement_claim.fk_office_id');
        $office = $queryBuilder->get()->getRowArray();

        return $office;
    }

}

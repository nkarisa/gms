<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\Core\Office;
use App\Controllers\Web\Core\Status;
use App\Controllers\Web\WebController;
use App\Helpers\ReimbursementHelper;
use App\Libraries\Core\ApprovalLibrary;
use App\Libraries\Core\AttachmentLibrary;
use App\Libraries\Core\StatusLibrary;
use App\Libraries\Core\UserLibrary;
use App\Libraries\Grants\CountryCurrencyLibrary;
use App\Libraries\Grants\ReimbursementClaimLibrary;
use App\Libraries\System\AwsAttachmentLibrary;
use App\Libraries\System\GrantsLibrary;
use App\Models\Core\OfficeModel;
use App\Models\Grants\ReimbursementClaimModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ReimbursementClaim extends WebController
{
    private StatusLibrary $statusLibrary;
    private $grantsLib;
    protected $userLibrary;
    private $rcLibrary;

    private ReimbursementHelper $rcHelper;

    private AttachmentLibrary $attachmentLibrary;


    private ReimbursementClaimModel $rcModel;

    private CountryCurrencyLibrary $currencyLibrary;

    private ApprovalLibrary $approvalLibrary;

    private OfficeModel $officeModel;

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->grantsLib = new GrantsLibrary();
        $this->statusLibrary = new StatusLibrary();
        $this->userLibrary = new UserLibrary();
        $this->rcLibrary = new ReimbursementClaimLibrary();
        $this->rcHelper = new ReimbursementHelper();
        $this->rcModel = new ReimbursementClaimModel();
        $this->attachmentLibrary = new AttachmentLibrary();
        $this->currencyLibrary = new CountryCurrencyLibrary();
        $this->approvalLibrary = new ApprovalLibrary();
        $this->officeModel = new OfficeModel();


    }


    public function claims()
    {
        $fkClusterIDs = $this->request->getPost('fk_cluster_ids[]');
        $fkStatusIDs = $this->request->getPost('fk_status_ids[]');

        //var_dump($fkClusterIDs);

        $start = intval($_POST['start'] ?? 0); // Starting point for pagination
        $length = intval($_POST['length'] ?? 10); // Number of records per page
        $searchValue = $_POST['search']['value'] ?? ''; // Search value
        $columnIndex = intval($_POST['order'][0]['column'] ?? 0); // Column index for sorting
        $sortDirection = $_POST['order'][0]['dir'] ?? 'desc'; // Sorting direction

        $rcModel = new ReimbursementClaimModel();
        $reimbursementClaims = $rcModel->getMedicalClaims('', $fkClusterIDs, $fkStatusIDs);

        $totalRecords = count($reimbursementClaims);

        // Apply search filter if search value is provided
        if ($searchValue !== '') {
            $reimbursementClaims = array_filter($reimbursementClaims, function ($claim) use ($searchValue) {
                return stripos($claim['reimbursement_claim_track_number'], $searchValue) !== false
                    || stripos($claim['voucher_number'], $searchValue) !== false
                    || stripos($claim['reimbursement_claim_name'], $searchValue) !== false
                    || stripos($claim['reimbursement_claim_facility'], $searchValue) !== false
                    || stripos($claim['reimbursement_claim_beneficiary_number'], $searchValue) !== false
                    || stripos($claim['reimbursement_app_type_name'], $searchValue) !== false
                    || stripos($claim['reimbursement_funding_type_name'], $searchValue) !== false
                    || stripos($claim['reimbursement_claim_incident_id'], $searchValue) !== false
                    || stripos($claim['reimbursement_claim_treatment_date'], $searchValue) !== false
                    || stripos($claim['amount'], $searchValue) !== false
                    || stripos($claim['reimbursement_claim_caregiver_contribution'], $searchValue) !== false
                    || stripos($claim['reimbursement_claim_amount_reimbursed'], $searchValue) !== false
                    || stripos($claim['reimbursement_claim_created_date'], $searchValue) !== false
                    || stripos($claim['last_modified_by'], $searchValue) !== false
                    || stripos($claim['office_name'], $searchValue) !== false;

                // Add other fields to search as needed
            });
        }

        $filteredCount = count($reimbursementClaims);

        usort($reimbursementClaims, function ($a, $b) use ($columnIndex, $sortDirection) {
            $columns = ['reimbursement_claim_id']; // Adjust column names based on your array structure
            $columnKey = $columns[$columnIndex] ?? 'reimbursement_claim_id'; // Default to 'claim_id' if column index is out of bounds

            if ($sortDirection === 'asc') {
                return $a[$columnKey] <=> $b[$columnKey];
            } else {
                return $b[$columnKey] <=> $a[$columnKey];
            }
        });

        $paginatedData = array_slice($reimbursementClaims, $start, $length);

        $rcClaimAttachments = $rcModel->getReimbursementClaimAttachments();

        $actionButtonData = $this->grantsLib->actionButtonData('reimbursement_claim', $this->session->user_account_system_id);

        $statusData = $actionButtonData;

        $initialStatus = $this->statusLibrary->initialItemStatus('reimbursement_claim');


        $hasPermissionForAddClaimButton = $this->userLibrary->checkRoleHasPermissions('reimbursement_claim', 'create');

        $medicalClaimTypeFontStyle = "color:black;";

        $medtfiFontStyle = "color:green;";

        $hvcCprFontStyle = "color:brown;";

        $civMedicalFontStyle = "color:blue;";

        foreach ($paginatedData as &$claim) {


            $track_number_style = $tr_style = '';

            $trackNumberStyle = $trStyle = '';

            $appType = $claim['reimbursement_app_type_name'];

            if ($appType === 'MED-TFI') {
                $trStyle = $medtfiFontStyle;
                $trackNumberStyle = $medtfiFontStyle;
            } elseif ($appType === 'HVC-CPR') {
                $trStyle = $hvcCprFontStyle;
                $trackNumberStyle = $hvcCprFontStyle;
            } elseif ($appType === 'CIV-MEDICAL') {
                $trStyle = $civMedicalFontStyle;
                $trackNumberStyle = $civMedicalFontStyle;
            } else {
                $trStyle = $medicalClaimTypeFontStyle;
                $trackNumberStyle = $medicalClaimTypeFontStyle;
            }

            $claim['tr_style'] = $trStyle;

            // Calculate 'fcp_number' based on 'reimbursement_claim_beneficiary_number'
            $claim['fcp_number'] = isset($claim['reimbursement_claim_beneficiary_number'])
                ? substr($claim['reimbursement_claim_beneficiary_number'], 1, 6)
                : '';

            // Calculate 'amount' as the sum of 'reimbursement_claim_amount_reimbursed' and 'reimbursement_claim_caregiver_contribution'
            $claim['amount'] = number_format(floatval(($claim['reimbursement_claim_amount_reimbursed'] ?? 0) + ($claim['reimbursement_claim_caregiver_contribution'] ?? 0)), 2);

            //

            // Optional: Populate 'action' and 'uploads' columns if required
            $trackNumber = '<a href="' . base_url() . 'reimbursement_claim/view/' . hash_id($claim['reimbursement_claim_id']) . '" style="' . $track_number_style . '" >' . $claim['reimbursement_claim_track_number'] . '</a>';

            $claim['reimbursement_claim_track_number'] = $trackNumber;

            //count of claums & action buttons

            $statusID = $claim['fk_status_id'];

            //die();

            $statusBackflowSequence = '';
            $statusButtonLabel = '';
            $statusDeclineButtonLabel = '';


            $actionHtml = '<div>';

            if (!empty($statusData['item_status'])) {
                $statusBackflowSequence = $statusData['item_status'][$statusID]['status_backflow_sequence'];
                $statusButtonLabel = $statusData['item_status'][$statusID]['status_button_label'];
                $statusDeclineButtonLabel = $statusData['item_status'][$statusID]['status_decline_button_label'];

                $actionHtml .= $this->rcHelper->approvalActionButton(
                    'reimbursement_claim',
                    $statusData['item_status'],
                    $claim['reimbursement_claim_id'],
                    $statusID,
                    $statusData['item_initial_item_status_id'],
                    $statusData['item_max_approval_status_ids'],
                    true
                );
            }
            $actionHtml .= '</div>';

            if ($statusDeclineButtonLabel != '' || $statusButtonLabel === 'Reinstate') {
                $actionHtml .= "<i style='cursor:alias; width:30px; height:30px; color:brown; font-size:20pt;'"
                    . " id='trigger_comment_area_{$claim['reimbursement_claim_id']}'"
                    . " data-reimbursement_id_comment_btn='{$claim['reimbursement_claim_id']}'"
                    . " class='fa fa-comment trigger_comment_area'>"
                    . "</i>"

                    . "<div class='hidden'"
                    . " id='claim_decline_reason_div_{$claim['reimbursement_claim_id']}'>"
                    . " <textarea data-reimbursement_id_txt_area='{$claim['reimbursement_claim_id']}'"
                    . " id='claim_decline_reason_{$claim['reimbursement_claim_id']}'"
                    . " class='claim_decline_reason'></textarea>"
                    . "</div>"

                    . "<div class='hidden' id='saved_comments_div_{$claim['reimbursement_claim_id']}'>"
                    . "</div>";

            }

            $actionHtml .= "<input id='support_documents_need_flag_{$claim['reimbursement_claim_id']}'"
                . " name='flag' class='hidden'"
                . " data-suppoort_doc_hidden_field='{$claim['support_documents_need_flag']}' />";


            $claim['action'] = $actionHtml;

            //end action buttons

            //start Cordion for uploads and downloads

            $patternReceipt = '/receipts/';
            $uploadContent = '';
            $disabledStatus = '';
            $supportDocumentsHtml = '';

            if ($claim['support_documents_need_flag'] == 1) {
                // Determine the disabled class for the button
                $disabledBtnClass = (
                    in_array($claim['fk_status_id'], $this->statusLibrary->getMaxApprovalStatusId('reimbursement_claim')) ||
                    ($statusID != $initialStatus && $statusBackflowSequence == 0)
                ) ? 'disabled' : '';

                // Build the button
                $supportDocumentsHtml .= '<button id="support_docs_upload_btn_' . $claim['reimbursement_claim_id'] . '" style="font-size:18px" '
                    . 'data-store_voucher_number="' . $claim['fk_voucher_detail_id'] . '" '
                    . 'data-document_type="support_documents" '
                    . 'data-reimbursement_claim_id="' . $claim['reimbursement_claim_id'] . '" '
                    . 'class="btn docs ' . $disabledBtnClass . '">';
                $supportDocumentsHtml .= '<i class="fa fa-upload"></i>' . get_phrase('support_docs') . ' </button>';

                // Build the Dropzone for Support Documents
                $supportDocumentsHtml .= '<!-- Dropzone For Support Documents -->';
                $supportDocumentsHtml .= '<div id="upload_support_docs_' . $claim['reimbursement_claim_id'] . '" class="col-xs-12 hidden" style="margin-bottom:20px;">';
                $supportDocumentsHtml .= '<form id="drop_support_documents_' . $claim['reimbursement_claim_id'] . '" class="dropzone">';
                $supportDocumentsHtml .= '<div class="fallback">';
                $supportDocumentsHtml .= '<input id="support_document_upload_area_' . $claim['reimbursement_claim_id'] . '" name="file" type="file" multiple />';
                $supportDocumentsHtml .= '</div>';
                $supportDocumentsHtml .= '</form>';
                $supportDocumentsHtml .= '</div>';

                // Build the attachments table
                $supportDocumentsHtml .= '<p>';
                $supportDocumentsHtml .= '<table id="tbl_render_uploaded_docs_' . $claim['reimbursement_claim_id'] . '"><tbody>';

                foreach ($rcClaimAttachments as $reimbursement_claim_attachment) {
                    $reimbursement_claim_id = $claim['reimbursement_claim_id'];
                    $attachment_url = $reimbursement_claim_attachment['attachment_url'];
                    $pattern = "/support_documents/";

                    // Only process attachments for support documents and matching the current claim ID
                    if ($reimbursement_claim_attachment['attachment_primary_id'] == $reimbursement_claim_id && preg_match($pattern, $attachment_url)) {
                        $objectKey = $attachment_url . '/' . $reimbursement_claim_attachment['attachment_name'];
                        $url = $this->config->upload_files_to_s3 ? $this->awsAttachmentLibrary->s3PreassignedUrl($objectKey) : $this->attachmentLibrary->getLocalFilesystemAttachmentUrl($objectKey);

                        $supportDocumentsHtml .= '<tr>';
                        $supportDocumentsHtml .= '<td><i id="' . $reimbursement_claim_attachment['attachment_id'] . '" class="btn fa fa-trash delete_attachment '
                            . (($statusID == $initialStatus || $statusBackflowSequence == 1) ? '' : 'disabled') . '" aria-hidden="true"></i></td>';
                        $supportDocumentsHtml .= '<td><a target="__blank" href="' . $url . '">' . $reimbursement_claim_attachment['attachment_name'] . '</a></td>';
                        $supportDocumentsHtml .= '</tr>';
                    } else {
                        continue;
                    }

                    $supportDocsFlag = $claim['support_documents_need_flag'];

                    // Toggle the ready-to-submit button if necessary
                    if ($supportDocsFlag == 1 && $reimbursement_claim_attachment['attachment_primary_id'] == $reimbursement_claim_id) {
                        $supportDocumentsHtml .= $this->rcHelper->disableReadytoSubmitBtn($reimbursement_claim_id);
                    }
                }

                $supportDocumentsHtml .= '</tbody></table>';
                $supportDocumentsHtml .= '</p>';
            }


            if (
                in_array($claim['fk_status_id'], $this->statusLibrary->getMaxApprovalStatusId('reimbursement_claim')) ||
                ($statusID != $initialStatus && $statusBackflowSequence == 0) ||
                !$hasPermissionForAddClaimButton
            ) {
                $disabledStatus = 'disabled';
            }

// Start building the main HTML structure
            $uploadContent .= "
    <p>
        <a type='button' data-toggle='collapse' data-target='#receipts_{$claim['reimbursement_claim_id']}' aria-expanded='false' aria-controls='collapseExample'>
            <i class='fa fa-plus-circle plus' style='font-size:20px; width:20px; height:20px; color:green'></i>
        </a>
    </p>
    <div class='collapse' id='receipts_{$claim['reimbursement_claim_id']}'>
        <div class='card card-body'>
            <!-- Upload button -->
            <p>
                <button id='receipt_upload_btn_{$claim['reimbursement_claim_id']}'
                    style='font-size:18px'
                    data-store_voucher_number='{$claim['fk_voucher_detail_id']}'
                    data-document_type='receipts'
                    data-reimbursement_claim_id='{$claim['reimbursement_claim_id']}'
                    class='btn reciepts $disabledStatus'>
                    <i class='fa fa-upload'></i>" . get_phrase('receipts') . "
                </button>
            </p>
            <!-- Dropzone for receipts -->
            <div id='upload_receipt_{$claim['reimbursement_claim_id']}' class='col-xs-12 hidden' style='margin-bottom:20px;'>
                <form id='drop_receipts_{$claim['reimbursement_claim_id']}' class='dropzone'>
                    <div class='fallback'>
                        <input id='receipt_upload_area_{$claim['reimbursement_claim_id']}' name='file' type='file' multiple />
                    </div>
                </form>
            </div>
            <p>
                <table id='tbl_render_uploaded_receipts_{$claim['reimbursement_claim_id']}'>
                    <tbody>";


// Loop through attachments and generate table rows dynamically
            foreach ($rcClaimAttachments as $attachment) {
                $attachmentURL = $attachment['attachment_url'];
                $reimbursementClaimID = $claim['reimbursement_claim_id'];


                if ($attachment['attachment_primary_id'] == $reimbursementClaimID && preg_match($patternReceipt, $attachmentURL) == 1) {
                    $objectKey = $attachmentURL . '/' . $attachment['attachment_name'];
                    $url = $this->config->upload_files_to_s3 ? $this->awsAttachmentLibrary->s3PreassignedUrl($objectKey) : $this->attachmentLibrary->getLocalFilesystemAttachmentUrl($objectKey);

                    // Add each row to the table within $uploadContent
                    $uploadContent .= "<tr>
                                <td>
                                    <i style='height: 20px; width: 20px;' id='{$attachment['attachment_id']}' class='btn fa fa-trash delete_attachment " .
                        ($statusID == $initialStatus || $statusBackflowSequence == 1 ? '' : 'disabled') .
                        "' aria-hidden='true'></i>
                                </td>
                                <td><a target='__blank' href='{$url}'>{$attachment['attachment_name']}</a></td>
                           </tr>";
                } else {
                    continue;
                }

                $supportDocsFlag = $claim['support_documents_need_flag'];

                // Toggle submit button if $supportDocsFlag
                if ($attachment['attachment_primary_id'] == $reimbursementClaimID && preg_match($patternReceipt, $attachmentURL) == 1 && $supportDocsFlag == 0) {
                    $uploadContent .= $this->rcHelper->disableReadytoSubmitBtn($reimbursementClaimID);
                }
            }

// Close table and HTML structure
            $uploadContent .= "
                    </tbody>
                </table>
            </p>
           
            
            $supportDocumentsHtml
                 
        </div>
    </div>";
            //end cordion

            $claim['uploads'] = $uploadContent;


        } //end foreach loop

        $response = [
            "draw" => intval($_POST['draw'] ?? 0),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $filteredCount,
            "hasPermissionForAddClaimButton" => $hasPermissionForAddClaimButton,
            "status" => $statusData['item_status'],
            "fkClusters" => $this->rcModel->getUserClusters(true),
            "data" => $paginatedData,
        ];


        return $this->response->setJSON($response);
    }

    public function get_reimbursement_comments($id)
    {

        $rcComments = $this->rcModel->getReimbursementComments($id);
        return $this->response->setJSON($rcComments);

    }

    public function add_reimbursement_comment()
    {
        $status = $this->rcModel->addReimbursementComment();
        $json['insert'] = $status;
        return $this->response->setJSON($json);

    }

    public function upload_reimbursement_claims_documents()
    {

        $result = [];

        $doc_type = $this->request->getPost('document_type');
        $reimbursement_claim_id = $this->request->getPost('reimbursement_claim_id');
        $voucher_id = $this->request->getPost('store_voucher_number');

        //Get Office Code
        $fcp_ids = array_column($this->session->hierarchy_offices, 'office_id');
        $office_code = $this->rcModel->getOfficeCode($fcp_ids);

        //get voucher number
        $voucher_number = $this->rcModel->getVoucherNumberForARow($voucher_id);

        // log_message('error',json_encode($office_code));

        if ($office_code['message'] == 1) {
            //Pass the store folder path
            $record_identfyer = lcfirst($doc_type) . '-' . $reimbursement_claim_id;
            $storeFolder = upload_url($this->controller, $record_identfyer, [$this->session->user_account_system, $office_code['fcps'], $voucher_number]);

            $result = $this->attachmentLibrary->uploadFiles($storeFolder);

        }


        echo json_encode($result);
    }

    function get_support_needed_docs_flag($health_facility_id)
    {
        echo $this->rcModel->getSupportNeededFocsFlag($health_facility_id);
    }


    public function result($id = '', $parentTable = null): array
    {


        if ($this->action == 'singleFormAdd') {

            $fcps_arr = $this->rcModel->getFcpNumber();

            $allowed_claim_days = $this->rcModel->getCountryMedicalSettings(3);

            $caregiver_amount = $this->rcModel->fcp_rembursable_amount_from_caregiver();

            $result['caregiver_contribution_flag'] = $this->rcModel->getCountryMedicalSettings(1);

            $result['national_health_cover_flag'] = $this->rcModel->getCountryMedicalSettings(2);
            $result['country_medical_settings_allowed_claimable_days'] = $allowed_claim_days;
            $result['minmum_rembursable_amount'] = $this->rcModel->getCountryMedicalSettings(5);
            $result['user_fcps'] = $fcps_arr;
            $result['country_currency_code'] = $this->rcModel->getCountryCurrencyCode();
            $result['health_facility_types'] = $this->rcModel->pullHealthFacilityTypes();
            $result['vouchers_and_total_costs'] = $this->rcModel->getVouchersForMedicalClaim();
            $result['already_reimbursed_amount'] = $this->rcModel->get_already_reimbursed_amount();
            $result['fcp_rembursable_amount_from_caregiver'] = $caregiver_amount;

            $result['reimbursement_app_types'] = $this->rcModel->reimbursementAppTypes();

            $result['rcModel'] = $this->rcModel;
            $result['statusLibrary'] = $this->statusLibrary;

            $result['rcHelper'] = $this->rcHelper;

            return $result;
        } elseif ($this->action == 'view') {

            $reimbursement_claim_id = hash_id($this->id, 'decode');


            $account_system_id = $this->rcModel->getOfficeAccountSystemIDByClaimID($reimbursement_claim_id)['account_system_id'];

            $attachments = $this->rcModel->getReimbursementClaimAttachments();

            $reimbursement_data = $this->rcModel->getMedicalClaims($reimbursement_claim_id);

            $result['medical_claim_attachments'] = $attachments;

            $result['status_data'] = $this->statusLibrary->actionButtonData('reimbursement_claim', $account_system_id);

            $result['rcHelper'] = $this->rcHelper;
            $result['rcModel'] = $this->rcModel;
            $result['statusLibrary'] = $this->statusLibrary;

            $result['awsAttachmentLibrary'] = new \App\Libraries\System\AwsAttachmentLibrary();
            $result['attachmentLibrary'] = new \App\Libraries\Core\AttachmentLibrary();
            //$userLibrary
            $result['userLibrary'] = $this->userLibrary;
            $result['logged_role_id'] = $this->session->role_ids;

            $result['reimbursement_claim_data'] = $reimbursement_data;

            $result['claimID'] = $this->id;
            $result['config'] = $this->config;


            return $result;

        } elseif ($this->action == 'list') {

            /* $status_data = [
                 'item_status' => [],
                 'item_initial_item_status_id' => "",
                 'item_max_approval_status_ids' => []
             ];

             if (!$this->session->system_admin) {
                 $status_data = $this->statusLibrary->actionButtonData('reimbursement_claim', $this->session->user_account_system_id);
             }

             $attachments = $this->rcModel->getReimbursementClaimAttachments();

             $columns = $this->columns();
             array_shift($columns);
             $result['columns'] = $columns;
             $result['has_details_table'] = false;
             $result['has_details_listing'] = false;
             $result['is_multi_row'] = false;
             $result['show_add_button'] = true;
             $result['status_data'] = $status_data;
             $result['reimbursement_claim_data'] = $this->rcModel->getMedicalClaims();
             $result['reimbursement_claim_attachments'] = $attachments;
             $result['initial_status'] = $this->statusLibrary->initialItemStatus('reimbursement_claim');

             //Filter records details [Get clusters and status]
             $result['clusters'] = $this->rcModel->getUserClusters(true);

             return $result;*/
            return [];
        } elseif ($this->action == 'edit') {


            $allowed_claim_days = $this->rcModel->getCountryMedicalSettings(3);

            $result['medical_info'] = $this->getReimbursementClaimRecordToEdit($this->id);

            $result['country_medical_settings_allowed_claimable_days'] = $allowed_claim_days;
            $result['health_facility_types'] = $this->rcModel->pullHealthFacilityTypes();

            //var_dump($result['health_facility_types']);
            //die();

            //$result['status_data'] = $this->statusLibrary->actionButtonData('reimbursement_claim', $account_system_id);

            $result['rcHelper'] = $this->rcHelper;
            $result['rcModel'] = $this->rcModel;
            $result['statusLibrary'] = $this->statusLibrary;

            return $result;
        } else {
            return parent::result($id = '');
        }
    }

    function columns()
    {
        $columns = [
            'reimbursement_claim_id',
            'reimbursement_claim_track_number',
            'reimbursement_claim_name',
            'status_name'
        ];

        return $columns;
    }

    public function reimbursement_illiness_category(int $diagnosis_type): void
    {

        echo json_encode($this->rcModel->reimbursementIllinessCategory($diagnosis_type));
    }

    public function check_if_connect_id_exists(int $connect_incident_id = 0): void
    {

        echo json_encode($this->rcModel->checkIfConnectIDExists($connect_incident_id));

    }

    public function medical_claim_scenerios(float $v_amount, float $claim_amount, string $card_number = ''): void
    {

        $bal_amt_on_voucher = $v_amount;

        $amt_to_claim = $claim_amount;

        $card_no = $card_number;

        $settings = $this->rcModel->medicalClaimScenerios($bal_amt_on_voucher, $amt_to_claim, $card_no);

        echo json_encode($settings);
    }

    public function add_reimbursement_claim()
    {

        $country_currency_code = 'USD';

        if (!$this->session->system_admin_id) {
            $country_currency_code = $this->currencyLibrary->getCountryCurrencyCode();
        }

        //Remove currency code and remove commas from digits
        $strip_currency_code_from_caregiver_contribution = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->request->getPost('reimbursement_claim_caregiver_contribution'))[1]));

        $strip_currency_code_from_amount_reimbursed = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->request->getPost('reimbursement_claim_amount_reimbursed'))[1]));

        $reimbursement_claim_amount_spent = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->request->getPost('reimbursement_claim_amount_spent'))[1]));

        //Medical claim count
        $medicalClaimCount = $this->rcModel->queryBuilder('read', 'reimbursement_claim')
            ->selectMax('reimbursement_claim_count')
            ->where(array('reimbursement_claim_beneficiary_number' => $this->request->getPost('reimbursement_claim_beneficiary_number')));

        $reimbursement_claim_count = $medicalClaimCount->get()->getRowObject()->reimbursement_claim_count;
        //echo $this->input->post('fk_office_id'); exit();
        $data['fk_office_id'] = $this->request->getPost('fk_office_id');
        $data['fk_reimbursement_app_type_id'] = $this->request->getPost('fk_reimbursement_app_type_id');
        if ($this->request->getPost('fk_reimbursement_funding_type_id') != 'NULL') {
            $data['fk_reimbursement_funding_type_id'] = $this->request->getPost('fk_reimbursement_funding_type_id');
        }

        $support_documents_need_flag = $this->request->getPost('support_documents_need_flag');

        $data['reimbursement_claim_name'] = $this->request->getPost('reimbursement_claim_name');
        $data['reimbursement_claim_beneficiary_number'] = $this->request->getPost('reimbursement_claim_beneficiary_number');
        $data['reimbursement_claim_track_number'] = $this->statusLibrary->generateItemTrackNumberAndName('reimbursement_claim')['reimbursement_claim_track_number'];
        $data['reimbursement_claim_treatment_date'] = $this->request->getPost('reimbursement_claim_treatment_date');
        $data['reimbursement_claim_facility'] = $this->request->getPost('reimbursement_claim_facility');
        $data['fk_health_facility_id'] = $this->request->getPost('fk_health_facility_id');
        $data['fk_context_cluster_id'] = $this->request->getPost('fk_context_cluster_id');
        $data['support_documents_need_flag'] = $support_documents_need_flag == 1 ? $support_documents_need_flag : 0;
        $data['reimbursement_claim_incident_id'] = $this->request->getPost('reimbursement_claim_incident_id');
        $data['fk_voucher_detail_id'] = $this->request->getPost('fk_voucher_detail_id');
        $data['reimbursement_claim_diagnosis'] = $this->request->getPost('reimbursement_claim_diagnosis');
        $data['reimbursement_claim_govt_insurance_number'] = $this->request->getPost('reimbursement_claim_govt_insurance_number');
        $data['reimbursement_claim_caregiver_contribution'] = $strip_currency_code_from_caregiver_contribution;
        $data['reimbursement_claim_amount_reimbursed'] = $strip_currency_code_from_amount_reimbursed;
        $data['reimbursement_claim_amount_spent'] = $reimbursement_claim_amount_spent;
        $data['reimbursement_claim_created_by'] = $this->session->user_id;
        $data['reimbursement_claim_created_date'] = date('Y-m-d');
        $data['reimbursement_claim_last_modified_by'] = $this->session->user_id;
        $data['fk_approval_id'] = $data['fk_approval_id'] = $this->approvalLibrary->insertApprovalRecord('reimbursement_claim');
        $data['fk_status_id'] = $this->statusLibrary->initialItemStatus('reimbursement_claim');
        $data['reimbursement_claim_count'] = $reimbursement_claim_count + 1;

        //echo json_encode($data);
        $this->write_db->trans_begin();

        $this->write_db->insert('reimbursement_claim', $data);

        $insert_id = $this->write_db->insert_id();

        if ($this->write_db->trans_status() === FALSE) {
            $this->write_db->trans_rollback();
            echo 0;
        } else {
            $this->write_db->trans_commit();

            echo $insert_id;
        }
    }

    private function getReimbursementClaimRecordToEdit($id): array
    {

        //Fetch medical data
        $medical_record = $this->rcModel->getReimbursementClaimRecordToEdit($id);


        //Get office_id and Office_code
        $office_id = array_column($medical_record, 'office_id');

        $fcp_number = array_column($medical_record, 'office_code');

        $medical_id = array_column($medical_record, 'reimbursement_claim_id');

        //Get the beneficiary number and name
        $beneficiary_number = array_column($medical_record, 'reimbursement_claim_beneficiary_number');
        $beneficiary_name = array_column($medical_record, 'reimbursement_claim_name');

        //Diagnosis
        $diagnosis = array_column($medical_record, 'reimbursement_claim_diagnosis');
        //Treatment Date
        $treatment_date = array_column($medical_record, 'reimbursement_claim_treatment_date');
        //   //Facility Name
        $health_facility = array_column($medical_record, 'reimbursement_claim_facility');

        //Health Facility Id and Type
        $health_facility_type_id = array_column($medical_record, 'fk_health_facility_id');


        //support_documents_need_flag
        $support_documents_need_flag = array_column($medical_record, 'support_documents_need_flag');

        //reimbursement_claim_incident_id
        $connect_incident_id = array_column($medical_record, 'reimbursement_claim_incident_id');

        //fk_voucher_id
        $voucher_id = array_column($medical_record, 'fk_voucher_id');

        //Govt_insurance_number
        $govt_insurance_number = array_column($medical_record, 'reimbursement_claim_govt_insurance_number');

        //Caregiver_contribution
        $caregiver_contribution = array_column($medical_record, 'reimbursement_claim_caregiver_contribution');

        //reimbursement_claim_amount_reimbursed
        $amount_reimbursed = array_column($medical_record, 'reimbursement_claim_amount_reimbursed');


        return [
            'medical_id' => $medical_id,
            'fcp_no' => array_combine($office_id, $fcp_number),
            'beneficiary_info' => array_combine($beneficiary_number, $beneficiary_name),
            'diagnosis' => $diagnosis,
            'treatment_date' => $treatment_date,
            'health_facility' => $health_facility,
            'health_facility_type' => $health_facility_type_id,
            'support_documents_need_flag' => $support_documents_need_flag,
            'connect_incident_id' => $connect_incident_id,
            'fk_voucher_id' => $voucher_id,
            'govt_insurance_number' => $govt_insurance_number,
            'caregiver_contribution' => $caregiver_contribution,
            'amount_reimbursed' => $amount_reimbursed,
            'country_currency_code' => $this->currencyLibrary->getCountryCurrencyCode(),
            'vouchers_and_total_costs' => $this->rcModel->getVouchersForMedicalClaim(),
            'already_reimbursed_amount' => $this->rcModel->get_already_reimbursed_amount(),
            'national_health_cover_card' => $this->rcModel->getCountryMedicalSettings(2),
            'percentage_caregiver_contribution' => $this->rcModel->getCountryMedicalSettings(1),
            'caregiver_contribution_with_health_cover_card_percentage' => $this->rcModel->getCountryMedicalSettings(7),
            'minimum_claimable_amount' => $this->rcModel->getCountryMedicalSettings(5),
            'reimburse_all_when_therhold_met' => $this->rcModel->getCountryMedicalSettings(6),
        ];
    }

    public function edit_medical_claim()
    {

        $country_currency_code = 'USD';

        if (!$this->session->system_admin_id) {
            $country_currency_code = $this->currencyLibrary->getCountryCurrencyCode();
        }


        //Remove currency code and remove commas from digits
        $strip_currency_code_from_caregiver_contribution = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->request->getPost('reimbursement_claim_caregiver_contribution'))[1]));

        $strip_currency_code_from_amount_reimbursed = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->request->getPost('reimbursement_claim_amount_reimbursed'))[1]));

        $reimbursement_claim_amount_spent = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->request->getPost('reimbursement_claim_amount_spent'))[1]));

        $data['fk_office_id'] = $this->request->getPost('fk_office_id');
        $data['reimbursement_claim_name'] = $this->request->getPost('reimbursement_claim_name');
        $data['reimbursement_claim_beneficiary_number'] = $this->request->getPost('reimbursement_claim_beneficiary_number');
        //$data['medical_claim_track_number']=$this->grants_model->generate_item_track_number_and_name('medical_claim')['medical_claim_track_number'];
        $data['reimbursement_claim_treatment_date'] = $this->request->getPost('reimbursement_claim_treatment_date');
        $data['reimbursement_claim_facility'] = $this->request->getPost('reimbursement_claim_facility');
        $data['fk_health_facility_id'] = $this->request->getPost('fk_health_facility_id');
        $data['support_documents_need_flag'] = $this->request->getPost('support_documents_need_flag');
        $data['reimbursement_claim_incident_id'] = $this->request->getPost('reimbursement_claim_incident_id');

        //ToDo: This key is not found in reimbursement_claim
        //$data['fk_voucher_id'] = $this->request->getPost('fk_voucher_id');

        $data['reimbursement_claim_diagnosis'] = $this->request->getPost('reimbursement_claim_diagnosis');
        $data['reimbursement_claim_govt_insurance_number'] = $this->request->getPost('reimbursement_claim_govt_insurance_number');
        $data['reimbursement_claim_caregiver_contribution'] = $strip_currency_code_from_caregiver_contribution;
        $data['reimbursement_claim_amount_reimbursed'] = $strip_currency_code_from_amount_reimbursed;
        $data['reimbursement_claim_amount_spent'] = $reimbursement_claim_amount_spent;
        $data['reimbursement_claim_created_by'] = $this->session->user_id;

        // //ToDo: Disabled this to avoid overwriting created date
        //$data['reimbursement_claim_created_date'] = date('Y-m-d');
        $data['reimbursement_claim_last_modified_by'] = $this->session->user_id;


        /* changed this to use method in RC model: updateReimbursementClaim($data, $claimID)

        $this->write_db->trans_start();

        $this->write_db->where(['reimbursement_claim_id' => $this->request->getPost('reimbursement_claim_id')]);
        $this->write_db->upda('reimbursement_claim', $data);

        $this->write_db->trans_complete();

        if ($this->write_db->trans_status() == FALSE) {
            echo 0;
        } else {
            echo 1;
        }
        */

        if ($this->rcModel->updateReimbursementClaim($data, $this->request->getPost('reimbursement_claim_id'))) {
            echo 1;
        } else {
            echo 0;
        }


    }

    function get_medical_claim_attachment_by_Id($medical_id, $document_type, $support_doc_flag)
    {

        $attachments = $this->rcModel->getReimbursementClaimAttachments($medical_id);

        $reconstruct_attachments_array = [];

        $array_column_url = array_column($attachments, 'attachment_url');

        $check_receipts_or_docs_exist = 0;

        //Check if docs already uploaded
        $check_receipts_or_docs_exist = $this->flag_up_if_medical_docs_uploaded($array_column_url, $support_doc_flag);

        //Loop and repopulate the array with attachements from the table to display
        foreach ($attachments as $attachment) {

            $explode_url = explode('/', $attachment['attachment_url']);

            //check receipts or support docs

            if (in_array($document_type, $explode_url)) {

                $attachment_url = $attachment['attachment_url'];

                $objectKey = $attachment_url . '/' . $attachment['attachment_name'];

                $url = $this->config->upload_files_to_s3 ? $this->awsAttachmentLibrary->s3PreassignedUrl($objectKey) : $this->attachmentLibrary->getLocalFilesystemAttachmentUrl($objectKey);

                $attachment['attachment_url'] = $url;

                $attachment['receipt_or_support_doc_flag'] = $check_receipts_or_docs_exist;


                $reconstruct_attachments_array[] = $attachment;
            }
        }
        echo json_encode($reconstruct_attachments_array);
    }

    private function flag_up_if_medical_docs_uploaded($array_column_url, $support_doc_flag = 0)
    {

        $receipts = 'false';
        $support_docs = 'false';

        $required_medical_documents_already_uploaded = 'false';

        foreach ($array_column_url as $url) {
            $url_exploded = explode('/', $url);

            if (in_array('receipts', $url_exploded)) {
                $receipts = 'true';
            } else if (in_array('support_documents', $url_exploded)) {
                $support_docs = 'true';
            }
        }

        if ($receipts == 'true' && $support_docs == 'true') {

            $required_medical_documents_already_uploaded = 'true';
        } else if ($receipts == 'true' && $support_docs == 'false' && $support_doc_flag == 0) {
            $required_medical_documents_already_uploaded = 'true';
        }

        return $required_medical_documents_already_uploaded;
    }

    public function delete_reciept_or_support_docs($attachement_id)
    {

        $deletion_message = $this->rcModel->deleteReceiptOrSupportDocs($attachement_id);

        echo $deletion_message;
    }


}


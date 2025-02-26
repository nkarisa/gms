<?php

namespace App\Libraries\Grants;

use App\Libraries\Core\ApprovalLibrary;
use App\Libraries\Core\StatusLibrary;
use App\Libraries\Core\UserLibrary;
use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ReimbursementClaimModel;
class ReimbursementClaimLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ReimbursementClaimModel();

        $this->table = 'reimbursement_claim';
    }

    function additionalListColumns(): array {
        $columns = [
            "action" => "reimbursement_claim_id",
            'uploads'=> "action",
        ];

        return $columns;
    }

    function formatColumnsValues(string $columnName, mixed $columnValue, array $rowArray, array $dependancyData = []): mixed {

        $statusLibrary = new StatusLibrary();

        switch($columnName){

            case "action":
                $columnValue = "";
                break;
            case "uploads":
                $data['data']['max_approval_status_id'] = $statusLibrary->getMaxApprovalStatusId('reimbursement_claim');
                $data['data']['initial_status_id'] = $statusLibrary->initialItemStatus('reimbursement_claim');
                $data['data']['status_backflow_sequence'] = 0;
                $data['data']['has_permission_for_add_claim_button'] = true;
                $data['data'] = $rowArray;
                $columnValue = view('reimbursement_claim/upload', $data);
                break;
            default:
                break;
        }

        return $columnValue;
    }

    function listTableVisibleColumns(): array {
        return ['reimbursement_claim_track_number', 'voucher_detail_id', 'reimbursement_claim_name', 'reimbursement_claim_beneficiary_number', 'reimbursement_claim_treatment_date', 'reimbursement_claim_diagnosis','reimbursement_claim_amount_spent', 'office_name'];
    }



}
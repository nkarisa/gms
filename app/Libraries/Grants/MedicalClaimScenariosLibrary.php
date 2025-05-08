<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\MedicalClaimSettingModel;

class MedicalClaimScenariosLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    /**
     * ******************************************************************************************
     *                                  Medical Claiming Scenarios                              *
     *                                  --------------------------                              *
     * 1. Fully reimbursement                                                                   *
     * 2. Fully reimursement with card [Full Waiver Percentage]                                 *
     * 3. Fully reimursement with card [Variable Waiver Percentage]                             *
     * 4. Fully reimbursement with Caregiver Contribution                                       *
     * 5. Fully reimbursement upon meeting threshold                                            *
     * 6. Fully reimbursement upon meeting threshold with card [Waiver Percentage]              *
     * 7. Fully reimbursement upon meeting threshold with card [Variable Waiver Percentage]     *
     * 8. Fully reimbursement upon meeting threshold with Caregiver Contribution                *
     * 9. Partial Reimbursement above threshold                                                 *
     * 10. Partial Reimbursement above threshold with card [Full Waiver Percentage]             *
     * 11. Partial Reimbursement above threshold with card [Variable Waiver Percentage]         *
     * ******************************************************************************************
     */

    // Options are to be provided as settings

    private $allow_use_insurance_card = false;
    private $threshold_amount = 0;
    private $reimburse_all_when_threshold_met = false;
    private $caregiver_contribution_percentage = 0; // Percentage 0 -100
    private $caregiver_with_card_contribution_percentage = 0; // Percentage 0 - 100

    private $contribution_amount = 0;
    private $reimbursable_amount = 0;

    function __construct($params)
    {

        $this->allow_use_insurance_card = $params['allow_use_insurance_card'];
        $this->threshold_amount = $params['threshold_amount'];
        $this->reimburse_all_when_threshold_met = $params['reimburse_all_when_threshold_met'];
        $this->caregiver_contribution_percentage = $params['caregiver_contribution_percentage'];
        $this->caregiver_with_card_contribution_percentage = $params['caregiver_with_card_contribution_percentage'];
    }


    // function __construct(
    //         $allow_use_insurance_card,
    //         $threshold_amount,
    //         $caregiver_contribution_percentage,
    //         $reimburse_all_when_threshold_met,
    //         $caregiver_with_card_contribution_percentage
    //     ){

    //     $this->allow_use_insurance_card = $allow_use_insurance_card;
    //     $this->threshold_amount = $threshold_amount;
    //     $this->reimburse_all_when_threshold_met = $reimburse_all_when_threshold_met;
    //     $this->caregiver_contribution_percentage = $caregiver_contribution_percentage;
    //     $this->caregiver_with_card_contribution_percentage = $caregiver_with_card_contribution_percentage;
    // }

    function compute_threshold_care_contribution($total_receipt_amount, $is_threshold_met, $insurance_card = '')
    {

        $this->contribution_amount = 0;

        if ($is_threshold_met) {
            $this->compute_caregiver_contribution($total_receipt_amount, $insurance_card);
        }
    }

    function compute_non_threshold_care_contribution($total_receipt_amount, $is_threshold_met, $insurance_card = '')
    {
        $this->contribution_amount = 0;
        $this->compute_caregiver_contribution($total_receipt_amount, $insurance_card);
    }

    function compute_caregiver_contribution($total_receipt_amount, $insurance_card = '')
    {
        if ($insurance_card != '' && $this->allow_use_insurance_card) {
            if ($this->caregiver_with_card_contribution_percentage > 0) {
                $this->contribution_amount = $total_receipt_amount * ($this->caregiver_with_card_contribution_percentage / 100);
            }
        } elseif ($this->caregiver_contribution_percentage > 0) {
            $this->contribution_amount = $total_receipt_amount * ($this->caregiver_contribution_percentage / 100);
        }
    }


    function compute_contribution_and_reibursement_amount($voucher_amount, $total_receipt_amount, $insurance_card)
    {

        $is_threshold_met = $total_receipt_amount > $this->threshold_amount ? true : false;


        // if($voucher_amount >= $total_receipt_amount){

        if ($is_threshold_met) {

            if ($this->reimburse_all_when_threshold_met) {
                $this->compute_threshold_care_contribution($total_receipt_amount, $is_threshold_met, $insurance_card);
            } else {
                $total_receipt_amount = $total_receipt_amount - $this->threshold_amount;
                $this->compute_threshold_care_contribution($total_receipt_amount, $is_threshold_met, $insurance_card);
            }
            $this->reimbursable_amount = $total_receipt_amount - $this->contribution_amount;

        } elseif ($this->threshold_amount == 0) {
            $this->compute_non_threshold_care_contribution($total_receipt_amount, $insurance_card);
            $this->reimbursable_amount = $total_receipt_amount - $this->contribution_amount;
        }

        //}

        $contribution_amount = $this->contribution_amount;
        $reimbursable_amount = $this->reimbursable_amount;

        $allow_use_insurance_card = $this->allow_use_insurance_card;
        $threshold_amount = $this->threshold_amount;
        $reimburse_all_when_threshold_met = $this->reimburse_all_when_threshold_met;
        $caregiver_contribution_percentage = $this->caregiver_contribution_percentage;
        $caregiver_with_card_contribution_percentage = $this->caregiver_with_card_contribution_percentage;

        $result['computations'] = compact('voucher_amount', 'total_receipt_amount', 'contribution_amount', 'reimbursable_amount', 'insurance_card');
        $result['settings'] = compact('allow_use_insurance_card', 'threshold_amount', 'reimburse_all_when_threshold_met', 'caregiver_contribution_percentage', 'caregiver_with_card_contribution_percentage');

        return $result;
    }


}

// /**
//  * Below is the implementation
//  */

// // Inputs from the form
// $voucher_amount = 4200;
// $amount_to_claim = 1500;
// $insurance_card = '568746648485';

// // Settings - Kenya
// $ke_allow_use_insurance_card = true;
// $ke_threshold_amount = 1000;
// $ke_reimburse_all_when_threshold_met = true;
// $ke_caregiver_contribution_percentage = 20; // Percentage 0 -100
// $ke_caregiver_with_card_contribution_percentage = 10; // Percentage 0 -100

// // Settings - Malawi
// $mw_allow_use_insurance_card = false;
// $mw_threshold_amount = 0;
// $mw_reimburse_all_when_threshold_met = false;
// $mw_caregiver_contribution_percentage = 0; // Percentage 0 -100
// $mw_caregiver_with_card_contribution_percentage = 0; // Percentage 0 -100

// $kenya_claim = new Claim_library(
//                             $ke_allow_use_insurance_card,
//                             $ke_threshold_amount,
//                             $ke_caregiver_contribution_percentage,
//                             $ke_reimburse_all_when_threshold_met,
//                             $ke_caregiver_with_card_contribution_percentage
//                         );


// echo '</br>*********************************************************KENYA SCENARIO*****************************************************************</br>';

// echo json_encode($kenya_claim->compute_contribution_and_reibursement_amount($voucher_amount, $amount_to_claim, $insurance_card));

// echo '</br>****************************************************************************************************************************************</br>';

// $malawi_claim = new Claim_library(
//                             $mw_allow_use_insurance_card,
//                             $mw_threshold_amount,
//                             $mw_caregiver_contribution_percentage,
//                             $mw_reimburse_all_when_threshold_met,
//                             $mw_caregiver_with_card_contribution_percentage
//                         );

// echo '</br>**********************************************************MALAWI SCENARIO****************************************************************</br>';

// echo json_encode($malawi_claim->compute_contribution_and_reibursement_amount($voucher_amount, $amount_to_claim, $insurance_card));

// echo '</br>*****************************************************************************************************************************************</br>';


// /**
//  * Concerns
//  * 1. Does Malawi record the full expense in E320 or the excess above the threshold?
//  * 2. If they record the full expense and then the system only picks the excess above the threshold, does it mean the threshold amount will remain as E320 expense or automatically moved to E30
//  * 3. As a follow up to question 2, if the threshold amount is not moved to E30, how will the negative balance in E320 be handled?
//  * 4. I suggest that the FCPs to only record the excess over the threshold in E320 and the threshold be recorded in E30, then the FCP provides the receipts for the full amount
//  */
<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use App\Libraries\Grants\JournalLibrary;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Journal extends WebController
{

    protected $library;
    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->library = new JournalLibrary();
    }

    function result($id = "", $parentId = null){
        $result = parent::result($id, $parentId);

        if($this->action == 'view'){
            $financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();
            $userLibrary = new \App\Libraries\Core\UserLibrary();

            $journal_id = hash_id($this->id,'decode');
            $office_data_from_journal = $this->library->getOfficeDataFromJournal($journal_id);
            $office_id = $office_data_from_journal->office_id;
            $transacting_month = $office_data_from_journal->journal_month;
            $account_system_id = $office_data_from_journal->fk_account_system_id;
      
            $status_data = $this->libs->actionButtonData('voucher', $account_system_id);
            $result['vouchers']=$this->library->getVouchersOfTheMonth($office_id,$transacting_month,$journal_id);
            $result['status_data'] = $status_data;
            $result['transacting_month']=$transacting_month;
            $result['role_has_journal_update_permission'] = $userLibrary->checkRoleHasPermissions(ucfirst($this->controller), 'update');
            $result['check_if_financial_report_is_submitted'] = $financialReportLibrary->checkIfFinancialReportIsSubmitted([$office_id], $transacting_month);
            // Users should be able to reverse voucher even if the MFRs are submitted. This is important to allow handling stale cheques and invalid transactions
            $result['mfr_submited_status'] = $financialReportLibrary->checkIfFinancialReportIsSubmitted([$office_id], $transacting_month);; // A stop gap waiting a discussion with Development Team on this matter so that ticket INC0218239 can be resolved. 
            $result['month_used_accrual_ledgers'] = ['receivables' => 100,'payables' => 200,'prepayments' => 300,'depreciation' => 400,'payroll_liability' => 500];
          
          }

        return $result;
    }

    function reverseVoucher($voucher_id, $is_reuse_cheque_transaction = 1, $reusing_and_cancel_eft_or_chq = '', $journal_month = '')
    {
        $chequeBookLibrary = new \App\Libraries\Grants\ChequeBookLibrary();
        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $journalLibrary = new JournalLibrary();
        $financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();

        $message = get_phrase("transaction_failed");
        $message_code = 'fail';

        if ($reusing_and_cancel_eft_or_chq == 'eft') {
            $message = 'Reusing/Cancellation of EFT Completed';
        } else if ($reusing_and_cancel_eft_or_chq == 'cheque') {
            $message = 'Reusing/Cancellation of Cheque Completed';
        } else {

            $message = 'Cancellation Completed';

        }

        // Get the voucher and voucher details
        $voucher = $this->read_db->table('voucher')->where(array('voucher_id' => $voucher_id))->get()->getRowArray();

        //Count  of reuse
        $count_of_reuse = $chequeBookLibrary->getReusedChequeCount($voucher['fk_office_bank_id'], $voucher['voucher_cheque_number'], $reusing_and_cancel_eft_or_chq);

        if ($count_of_reuse < $this->config->cheque_cancel_and_resuse_limit || $this->session->system_admin) {
            $this->write_db->transStart();

            $insert_voucher = $voucherLibrary->insertVoucherReversalRecord($voucher, $is_reuse_cheque_transaction, $journal_month);

            $new_voucher_id = $insert_voucher['new_voucher_id'];

            $this->updateCashRecipientAccount($new_voucher_id, $voucher);

            $journalLibrary->createNewJournal($insert_voucher['new_voucher']['voucher_date'], $voucher['fk_office_id']);

            $financialReportLibrary->createFinancialReport(date("Y-m-01", strtotime($insert_voucher['new_voucher']['voucher_date'])), $voucher['fk_office_id']);

            $this->write_db->transComplete();

            if ($this->write_db->transStatus() == true) {
                $message = get_phrase($message);
                $message_code = 'success';
            }
        } else {
            $message = get_phrase('exceed_reuse_limit', "You have exceeded the reuse limit of 3 for this bank reference. Kindly contact PF");
        }

        echo json_encode(['message_code' => $message_code, 'message' => $message, 'next_voucher_number' => $insert_voucher['next_voucher_number']]);
    }

    function updateCashRecipientAccount($new_voucher_id,$voucher){
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        $voucher_id = array_shift($voucher);
        // Insert a cash_recipient_account record if reversing voucher is bank to bank contra
        
        $voucherTypeEffectReadBuilder = $this->read_db->table('voucher_type_effect');
        $voucherTypeEffectReadBuilder->where(array('voucher_type_id'=>$voucher['fk_voucher_type_id']));
        $voucherTypeEffectReadBuilder->join('voucher_type','voucher_type.fk_voucher_type_effect_id=voucher_type_effect.voucher_type_effect_id');
        $voucher_type_effect_code = $voucherTypeEffectReadBuilder->get()->getRow()->voucher_type_effect_code;
    
        if($voucher_type_effect_code == 'bank_to_bank_contra'){
    
          $this->read_db->where(array('fk_voucher_id'=>$voucher_id));
          $original_cash_recipient_account = $this->read_db->get('cash_recipient_account')->row_array();
    
          $item_track_number_and_name = $this->libs->generateItemTrackNumberAndName('cash_recipient_account');
          $cash_recipient_account_data['cash_recipient_account_name'] = $item_track_number_and_name['cash_recipient_account_name'];
          $cash_recipient_account_data['cash_recipient_account_track_number'] = $item_track_number_and_name['cash_recipient_account_track_number'];
          $cash_recipient_account_data['fk_voucher_id'] = $new_voucher_id;
    
          if($voucher['fk_office_bank_id'] > 0){
            $cash_recipient_account_data['fk_office_bank_id'] = $original_cash_recipient_account['fk_office_bank_id'];
          }elseif($voucher['fk_office_cash_id'] > 0){
            $cash_recipient_account_data['fk_office_cash_id'] = $original_cash_recipient_account['fk_office_cash_id'];
          }
    
          $cash_recipient_account_data['cash_recipient_account_created_date'] = date('Y-m-d');
          $cash_recipient_account_data['cash_recipient_account_created_by'] = $this->session->user_id;
          $cash_recipient_account_data['cash_recipient_account_last_modified_by'] = $this->session->user_id;
    
          $cash_recipient_account_data['fk_approval_id'] = $this->libs->insertApprovalRecord('cash_recipient_account');
          $cash_recipient_account_data['fk_status_id'] = $statusLibrary->initialItemStatus('cash_recipient_account');
    
          $this->write_db->insert('cash_recipient_account',$cash_recipient_account_data);
        }
    
      }

      function checkIfVoucherIsReversedOrCancelled($voucher_id){
       
        $voucherToReverse=$this->library->checkIfVoucherIsReversedOrCancelled($voucher_id);

        //log_message('error', json_encode($voucherToReverse));
        echo $voucherToReverse;
      }
}

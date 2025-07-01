<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use App\Enums\AccrualLedgerAccounts;
use App\Enums\AccrualVoucherTypeEffects;
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
            $journalLibrary = new JournalLibrary();
            $userLibrary = new \App\Libraries\Core\UserLibrary();

            $journal_id = hash_id($this->id,'decode');
            $office_data_from_journal = $this->library->getOfficeDataFromJournal($journal_id);
            $office_id = $office_data_from_journal->office_id ?? 0;
            $transacting_month = $office_data_from_journal->journal_month ?? "";
            $account_system_id = $office_data_from_journal->fk_account_system_id ?? 0;
      
            $status_data = $this->libs->actionButtonData('voucher', $account_system_id);
            $result['vouchers']=$this->library->getVouchersOfTheMonth($office_id,$transacting_month,$journal_id);
            $result['status_data'] = $status_data;
            $result['transacting_month']=$transacting_month;
            $result['role_has_journal_update_permission'] = $userLibrary->checkRoleHasPermissions(ucfirst($this->controller), 'update');
            $result['check_if_financial_report_is_submitted'] = $financialReportLibrary->checkIfFinancialReportIsSubmitted([$office_id], $transacting_month);
            // Users should be able to reverse voucher even if the MFRs are submitted. This is important to allow handling stale cheques and invalid transactions
            $result['mfr_submited_status'] = $financialReportLibrary->checkIfFinancialReportIsSubmitted([$office_id], $transacting_month); // A stop gap waiting a discussion with Development Team on this matter so that ticket INC0218239 can be resolved.           
            $result['accrual_activated'] = $journalLibrary->checkIfAccountingSystemAccrualIsActivated($account_system_id, $office_id, $transacting_month);
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

      function clearAccrualTransaction(){
        $post = $this->request->getPost();
        ['voucherId' => $voucherId, 'bankRef' => $bankRef] = $post;

        // Check if a voucher type effect clearance require a bank reference. In the meantime only Payable Disbursements requires a bank reference
        $checkIfRefRequired = $this->checkIfAccrualLedgerClearanceRequiresBankRefByVoucherId($voucherId);

        if($checkIfRefRequired && $bankRef == ""){
          return $this->response->setJSON(['success' => true ,'message' => '', 'requireBankRef' => true]);
        }
        
        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $response = $voucherLibrary->clearAccrualTransaction($voucherId, $bankRef);

        return $this->response->setJSON(['success' => $response['flag'] ,'message' => $response['message'], 'requireBankRef' => false]);
      }

      function checkIfAccrualLedgerClearanceRequiresBankRefByVoucherId(int $voucherId){

        $checkIf = false;

        $voucherReadBuilder = $this->read_db->table('voucher');

        $voucherReadBuilder->where(['voucher_id' => $voucherId]);
        $voucherReadBuilder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $voucherReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $incurredVoucher = $voucherReadBuilder->get()->getRowArray();

        $clearingVoucherEffect = AccrualLedgerAccounts::tryFrom($incurredVoucher['voucher_type_effect_code'])->accrualLedgerClearingEffect();

        if(in_array($clearingVoucherEffect, [AccrualVoucherTypeEffects::PAYABLE_DISBURSEMENTS->value])){
          $checkIf = true;
        }

        return $checkIf;
      }
      
      public function getBankAndRefViews(){
        $post = $this->request->getPost();
       
        ['voucherId' => $voucherId, 'accrualClearingEffect' => $accrualClearingEffect, 'officeId' => $officeId] = $post;
        
        // Voucher details
        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $voucher = $voucherLibrary->getTransactionVoucher(hash_id($voucherId, 'encode'));

        // Get active office bank details
        $officeBankLibrary = new \App\Libraries\Grants\OfficeBankLibrary();
        $activeOfficeBanks  = $officeBankLibrary->getActiveOfficeBank($officeId);

        $isBankReferenced = false;
        // Initialized as empty but obtain data in getOfficeBankRefByOfficeBank method
        $validChequeNumbers = [];
        
        if($accrualClearingEffect == AccrualVoucherTypeEffects::PAYABLE_DISBURSEMENTS->value){
          $voucherTypeLibrary = new \App\Libraries\Grants\VoucherTypeLibrary();
          $isBankReferenced = $voucherTypeLibrary->checkIfPayableDisbursementVoucherTypeIsBankReferencedByOfficeId($officeId);
        }

        // Compute voucher uncleared amount
        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary(); // 1739942
        $clearedAmount = $voucherLibrary->getClearedAccrualAmountByAccount('1739942', AccrualVoucherTypeEffects::RECEIVABLES_PAYMENTS->value);

        // $unclearedAmount = $voucherLibrary = $voucherLibrary->validateRefundFromVoucher($officeId, $voucher['header']['voucher_type_id'], $voucher['header']['voucher_number']);
        log_message('error', json_encode($clearedAmount));

        $modalBodyContents = view('journal/components/accrualClearanceView', compact('accrualClearingEffect','activeOfficeBanks','validChequeNumbers','isBankReferenced', 'voucher'));

        return $this->response->setJSON(['view' => $modalBodyContents, ...$post]);
      }

      function getOfficeBankRefByOfficeBank(){
        $post = $this->request->getPost();
        $office_bank_id = $post['office_bank_id'];

        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $options = $voucherLibrary->checkChequeValidity($office_bank_id);
        
        // Always true when in this method
        $isBankReferenced = true;

        return $this->response->setJSON(compact('isBankReferenced','options'));
      }
}

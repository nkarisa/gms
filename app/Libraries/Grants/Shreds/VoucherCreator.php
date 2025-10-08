<?php

namespace App\Libraries\Grants\Shreds;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Config\Services;
use App\Libraries\Grants\JournalLibrary;
use App\Libraries\Grants\FinancialReportLibrary;
use App\Libraries\Grants\ChequeInjectionLibrary;
use App\Libraries\Grants\ChequeBookLibrary;
use App\Libraries\Core\StatusLibrary;
use App\Libraries\Core\ApprovalLibrary;
use App\Libraries\Grants\ExpenseAccountLibrary;
use App\Enums\VoucherTypeEffectEnum;
use App\Libraries\Grants\VoucherLibrary;
use App\Libraries\Grants\OfficeBankLibrary;

class VoucherCreator
{
    /**
     * @var BaseConnection
     */
    protected $write_db;

    /**
     * @var BaseConnection
     */
    protected $read_db;

    // These libraries should ideally be injected via the constructor
    // but are instantiated here for a direct refactoring of the original code.
    protected JournalLibrary $journalLibrary;
    protected FinancialReportLibrary $financialReportLibrary;
    protected ChequeInjectionLibrary $chequeInjectionLibrary;
    protected StatusLibrary $statusLibrary;
    protected ApprovalLibrary $approvalLibrary;
    protected ExpenseAccountLibrary $expenseAccountLibrary;
    protected ChequeBookLibrary $chequeBookLibrary;
    protected VoucherLibrary $voucherLibrary;
    protected OfficeBankLibrary $officeBankLibrary;
    public function __construct()
    {
        $this->read_db = \Config\Database::connect('read');
        $this->write_db = \Config\Database::connect('write');
        // Instantiate dependent libraries
        $this->journalLibrary = new JournalLibrary();
        $this->financialReportLibrary = new FinancialReportLibrary();
        $this->chequeInjectionLibrary = new ChequeInjectionLibrary();
        $this->statusLibrary = new StatusLibrary();
        $this->approvalLibrary = new ApprovalLibrary();
        $this->expenseAccountLibrary = new ExpenseAccountLibrary();
        $this->chequeBookLibrary = new ChequeBookLibrary();
        $this->officeBankLibrary = new OfficeBankLibrary();
        $this->voucherLibrary = new VoucherLibrary();
    }

    /**
     * Checks if a voucher number already exists and gets a new one if necessary.
     *
     * @param int $officeId
     * @param string $voucherNumber
     * @return string
     */
    private function _getVoucherNumber(int $officeId, string $voucherNumber): string
    {
        $builder = $this->read_db->table("voucher");
        $voucher_obj = $builder->where(['fk_office_id' => $officeId, 'voucher_number' => $voucherNumber])->get();

        if ($voucher_obj->getNumRows() > 0) {
            return $this->voucherLibrary->getVoucherNumber($officeId);
        }
        return $voucherNumber;
    }

    /**
     * Manages monthly journal and financial report creation.
     *
     * @param int $officeId
     * @param string $voucherDate
     * @return void
     */
    private function _handleMonthlyFinancials(int $officeId, string $voucherDate): void
    {
        $month = date("Y-m-01", strtotime($voucherDate));

        if (!$this->voucherLibrary->officeHasVouchersForTheTransactingMonth($officeId, $voucherDate)) {
            $this->journalLibrary->createNewJournal($month, $officeId);
            $this->financialReportLibrary->createFinancialReport($month, $officeId);
        }

        if (!$this->getJournalForCurrentVouchingMonth($month, $officeId)) {
            $this->journalLibrary->createNewJournal($month, $officeId);
        }

        if (!$this->getFinancialReportForCurrentVouchingMonth($month, $officeId)) {
            $this->financialReportLibrary->createFinancialReport($month, $officeId);
        }
    }

    /**
     * Retrieves the voucher type details.
     *
     * @param int $voucherTypeId
     * @return object
     */
    private function _getVoucherTypeDetails(int $voucherTypeId): object
    {
        // Placeholder for the actual method
        return (object) ['voucher_type_effect_code' => 'expense', 'voucher_type_account_code' => ''];
    }

    /**
     * Prepares the voucher header data array.
     *
     * @param array $post
     * @param string $voucherNumber
     * @param object $voucherType
     * @return array
     */
    private function _prepareVoucherHeaderData(array $post, string $voucherNumber, object $voucherType): array
    {
        $track = $this->voucherLibrary->generateItemTrackNumberAndName('voucher');
        $header = [
            'voucher_track_number' => $track['voucher_track_number'],
            'voucher_name' => $track['voucher_name'],
            'fk_office_id' => $post['fk_office_id'],
            'voucher_date' => $post['voucher_date'],
            'voucher_number' => $voucherNumber,
            'fk_voucher_type_id' => $post['fk_voucher_type_id'],
            'fk_office_bank_id' => $this->getOfficeBankIdToPost($post['fk_office_id'], $post['fk_office_bank_id'] ?? 0),
            'fk_office_cash_id' => $post['fk_office_cash_id'] ?? 0,
            'voucher_cheque_number' => $post['voucher_cheque_number'] ?? 0,
            'voucher_vendor' => $post['voucher_vendor'],
            'voucher_vendor_address' => $post['voucher_vendor_address'],
            'voucher_created_by' => Services::session()->user_id,
            'voucher_created_date' => date('Y-m-d'),
            'voucher_last_modified_by' => Services::session()->user_id,
        ];

        // Handle reversal/clearing description and ID
        $this->_handleVoucherReversal($header, $post, $voucherType->voucher_type_effect_code);

        return $header;
    }

    /**
     * Handles voucher reversal logic, updates header data accordingly.
     *
     * @param array $header
     * @param array $post
     * @param string $voucherTypeEffectCode
     * @return void
     */
    private function _handleVoucherReversal(array &$header, array $post, string $voucherTypeEffectCode): void
    {
        $reverse_from_voucher_id = 0;
        $reverse_from_voucher_number = '';

        $reversalVoucherTypes = [
            'bank_refund',
            VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode(),
            VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode(),
            VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode()
        ];

        if (in_array($voucherTypeEffectCode, $reversalVoucherTypes)) {
            $reverse_from_voucher = $this->getRefundFromVoucher($post['fk_office_id'], $post['bank_refund']);
            $reverse_from_voucher_number = $reverse_from_voucher['voucher_number'];
            $reverse_from_voucher_id = $reverse_from_voucher['voucher_id'];

            if ($voucherTypeEffectCode == 'bank_refund') {
                $header['voucher_reversal_from'] = $reverse_from_voucher_id;
                $header['voucher_description'] = '<strike>' . $post['voucher_description'] . '</strike>';
                $header['voucher_is_reversed'] = 1;
            } else {
                $title = match ($voucherTypeEffectCode) {
                    VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() => VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getName(),
                    VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() => VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getName(),
                    VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode() => VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getName(),
                };
                $header['voucher_cleared_from'] = $reverse_from_voucher_id;
                $header['voucher_description'] = $post['voucher_description'] . ' [' . get_phrase('voucher_number') . ' ' . $reverse_from_voucher_number . ' ' . $title . ']';
            }
        } else {
            $header['voucher_description'] = $post['voucher_description'];
        }
    }

    /**
     * Inserts the voucher header record into the database.
     *
     * @param array $header
     * @param int $fullyApprovedStatusId
     * @return int
     */
    private function _insertVoucherHeader(array $header, int $fullyApprovedStatusId): int
    {
        $header['fk_approval_id'] = $this->approvalLibrary->insertApprovalRecord('voucher');
        $header['fk_status_id'] = $fullyApprovedStatusId > 0 ? $fullyApprovedStatusId : $this->statusLibrary->initialItemStatus('voucher');

        $this->write_db->table('voucher')->insert($header);

        return $this->write_db->insertID();
    }

    /**
     * Prepares and inserts all voucher detail records.
     *
     * @param int $headerId
     * @param array $post
     * @param string $voucherTypeEffectCode
     * @return float
     */
    private function _insertVoucherDetails(int $headerId, array $post, string $voucherTypeEffectCode): float
    {
        $total_voucher_cost = 0;
        if (empty($post['voucher_detail_quantity'])) {
            return $total_voucher_cost;
        }

        $detail_batch = [];
        for ($i = 0; $i < sizeof($post['voucher_detail_quantity']); $i++) {
            $detail = $this->_prepareVoucherDetailData($headerId, $post, $i, $voucherTypeEffectCode);
            $total_voucher_cost += $detail['voucher_detail_total_cost'];
            $detail_batch[] = $detail;
        }

        $this->write_db->table('voucher_detail')->insertBatch($detail_batch);

        return $total_voucher_cost;
    }

    /**
     * Prepares a single voucher detail data array.
     *
     * @param int $headerId
     * @param array $post
     * @param int $index
     * @param string $voucherTypeEffectCode
     * @return array
     */
    private function _prepareVoucherDetailData(int $headerId, array $post, int $index, string $voucherTypeEffectCode): array
    {
        $quantity = str_replace(",", "", $post['voucher_detail_quantity'][$index]);
        $unitCost = str_replace(",", "", $post['voucher_detail_unit_cost'][$index]);
        $totalCost = str_replace(",", "", $post['voucher_detail_total_cost'][$index]);

        $tracking = $this->voucherLibrary->generateItemTrackNumberAndName('voucher_detail');

        $detail = [
            'fk_voucher_id' => $headerId,
            'voucher_detail_track_number' => $tracking['voucher_detail_track_number'],
            'voucher_detail_name' => $tracking['voucher_detail_name'],
            'voucher_detail_quantity' => $quantity,
            'voucher_detail_description' => $post['voucher_detail_description'][$index],
            'voucher_detail_unit_cost' => $unitCost,
            'voucher_detail_total_cost' => $totalCost,
            'fk_project_allocation_id' => $post['fk_project_allocation_id'][$index] ?? 0,
            'fk_request_detail_id' => $post['fk_request_detail_id'][$index] ?? 0,
            'fk_approval_id' => $this->approvalLibrary->insertApprovalRecord('voucher_detail'),
            'fk_status_id' => $this->statusLibrary->initialItemStatus('voucher_detail'),
        ];

        // Assign correct account IDs based on voucher type effect code
        $this->_assignDetailAccountIds($detail, $post, $index, $voucherTypeEffectCode);

        // Update request detail status if applicable
        if ($detail['fk_request_detail_id'] > 0) {
            $this->updateRequestDetailStatusOnVouching($detail['fk_request_detail_id'], $headerId);
            $this->updateRequestOnPayingAllDetails($detail['fk_request_detail_id']);
        }

        return $detail;
    }

    /**
     * Assigns the correct account IDs to a voucher detail record.
     *
     * @param array $detail
     * @param array $post
     * @param int $index
     * @param string $voucherTypeEffectCode
     * @return void
     */
    private function _assignDetailAccountIds(array &$detail, array $post, int $index, string $voucherTypeEffectCode): void
    {
        $account_id = $post['voucher_detail_account'][$index] ?? 0;
        $detail['fk_expense_account_id'] = 0;
        $detail['fk_income_account_id'] = 0;
        $detail['fk_contra_account_id'] = 0;

        $expenseRelatedTypes = [
            'expense',
            'bank_refund',
            VoucherTypeEffectEnum::PAYABLES->getCode(),
            VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode(),
            VoucherTypeEffectEnum::PREPAYMENTS->getCode(),
            VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode(),
            VoucherTypeEffectEnum::DEPRECIATION->getCode(),
            VoucherTypeEffectEnum::PAYROLL_LIABILITY->getCode(),
        ];

        $incomeRelatedTypes = [
            'income',
            'bank_to_bank_contra',
            VoucherTypeEffectEnum::RECEIVABLES->getCode(),
            VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode(),
        ];

        $contraRelatedTypes = [
            'bank_contra',
            'cash_contra'
        ];

        if (in_array($voucherTypeEffectCode, $expenseRelatedTypes)) {
            $detail['fk_expense_account_id'] = $account_id;
            $detail['fk_income_account_id'] = $this->expenseAccountLibrary->getExpenseIncomeAccountId($account_id);
        } elseif (in_array($voucherTypeEffectCode, $incomeRelatedTypes)) {
            $detail['fk_income_account_id'] = $account_id;
        } elseif (in_array($voucherTypeEffectCode, $contraRelatedTypes)) {
            $detail['fk_contra_account_id'] = $account_id;
        }
    }


    /**
     * Handles all related updates after the voucher has been inserted.
     *
     * @param int $headerId
     * @param array $post
     * @param array $header
     * @param float $totalVoucherCost
     * @param string $voucherTypeEffectCode
     * @return void
     */
    private function _handleRelatedUpdates(int $headerId, array $post, array $header, float $totalVoucherCost, string $voucherTypeEffectCode): void
    {
        $chequeNumber = $header['voucher_cheque_number'] ?? 0;
        $officeBankId = $header['fk_office_bank_id'] ?? 0;

        if ($chequeNumber) {
            $this->chequeInjectionLibrary->updateInjectedChequeStatus($officeBankId, $chequeNumber);
            $chequeBookId = $this->chequeBookLibrary->getChequeBookIdForChequeNumber($chequeNumber, $officeBankId);
            if ($chequeBookId) {
                $this->write_db->table('cheque_book')->where('cheque_book_id', $chequeBookId)->update(['cheque_book_is_used' => 1]);
            }
        }

        if (isset($post['cash_recipient_account']) && $post['cash_recipient_account'] != NULL) {
            $this->createCashRecipientAccountRecord($headerId, $post);
        }

        $reverseFromVoucherId = $header['voucher_reversal_from'] ?? $header['voucher_cleared_from'] ?? 0;
        $reverseFromVoucherNumber = ''; // This would need to be passed in or retrieved

        if ($reverseFromVoucherId > 0) {
            $reverse_from_voucher_number = $this->read_db->table('voucher')->select('voucher_number')->where('voucher_id', $reverseFromVoucherId)->get()->getRow()->voucher_number;

            $updateType = ($voucherTypeEffectCode == 'bank_refund') ? 'bank_refund' : 'accrual';
            $this->updateReversalFromVoucher(
                $reverseFromVoucherId,
                $headerId,
                $reverseFromVoucherNumber,
                $header['voucher_description'],
                $totalVoucherCost,
                $updateType
            );
        }
    }

    /**
     * Placeholder method for the original logic.
     * Checks if all details have an account.
     *
     * @param array $detailsAccounts
     * @return bool
     */
    private function allDetailsHaveAccounts(array $detailsAccounts): bool
    {
        foreach ($detailsAccounts as $detailsAccount) {
            if (!$detailsAccount || $detailsAccount == 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Placeholder method for the original logic.
     * Checks the posting condition of the voucher.
     *
     * @param array $post
     * @return bool
     */
    private function voucherPostingCondition(array $post): bool
    {
        $has_details = count($post['voucher_detail_quantity'] ?? []) > 0;
        $all_details_have_accounts = $this->allDetailsHaveAccounts($post['voucher_detail_account'] ?? []);

        return $has_details && $all_details_have_accounts;
    }

    private function getJournalForCurrentVouchingMonth($voucher_date, $office_id)
    {
        $builder = $this->read_db->table('journal');
        $builder->select(['journal_month']);
        $builder->where(['fk_office_id' => $office_id]);
        $builder->where(['journal_month' => $voucher_date]);
        $this_month_journal_obj = $builder->get();

        $this_month_journal = [];

        if ($this_month_journal_obj->getNumRows() > 0) {
            $this_month_journal = $this_month_journal_obj->getRowArray();
        }

        //check if journal exists
        $journal_exists = false;

        if (sizeof($this_month_journal) == 1) {
            $journal_exists = true;
        }

        return $journal_exists;
    }

    private function getFinancialReportForCurrentVouchingMonth($voucher_date, $office_id)
    {
        $builder = $this->read_db->table("financial_report");
        $builder->select(['financial_report_month']);
        $builder->where(['fk_office_id' => $office_id]);
        $builder->where(['financial_report_month' => $voucher_date]);
        $this_month_mfr_obj = $builder->get();

        $this_month_mfr = [];

        if ($this_month_mfr_obj->getNumRows() > 0) {
            $this_month_mfr = $this_month_mfr_obj->getRowArray();
        }

        $financial_report_exists = false;

        if (sizeof($this_month_mfr) == 1) {

            $financial_report_exists = true;
        }

        return $financial_report_exists;
    }

    private function getOfficeBankIdToPost($office_id, $office_bank_id = 0)
    {

        if ($office_bank_id == 0) {
            // Get id of active office bank
            $office_bank_id = $this->officeBankLibrary->getActiveOfficeBanks($office_id)[0]['office_bank_id'];
        }

        return $office_bank_id;
    }

    private function getRefundFromVoucher($office_id, $reverse_from_voucher_number)
    {
        $voucherReadBuilder = $this->read_db->table('voucher');
        $voucherReadBuilder->select(['voucher_id', 'voucher_number']);
        $voucherReadBuilder->where(['fk_office_id' => $office_id, 'voucher_number' => $reverse_from_voucher_number]);
        $voucher_obj = $voucherReadBuilder->get();

        $voucher = [];

        if ($voucher_obj->getNumRows() > 0) {
            $voucher = $voucher_obj->getRowArray();
        }

        return $voucher;
    }


    private function createCashRecipientAccountRecord($voucher_id, $post)
    {

        $tracking = $this->voucherLibrary->generateItemTrackNumberAndName('cash_recipient_account');
        $cash_recipient_account_data['cash_recipient_account_name'] = $tracking['cash_recipient_account_name'];
        $cash_recipient_account_data['cash_recipient_account_track_number'] = $tracking['cash_recipient_account_track_number'];
        $cash_recipient_account_data['fk_voucher_id'] = $voucher_id;

        if (isset($post['fk_office_bank_id']) && $post['fk_office_bank_id'] > 0) {
            $cash_recipient_account_data['fk_office_bank_id'] = $post['cash_recipient_account'];
        } elseif ($post['fk_office_cash_id'] > 0) {
            $cash_recipient_account_data['fk_office_cash_id'] = $post['cash_recipient_account'];
        }

        $cash_recipient_account_data['cash_recipient_account_created_date'] = date('Y-m-d');
        $cash_recipient_account_data['cash_recipient_account_created_by'] = Services::session()->user_id;
        $cash_recipient_account_data['cash_recipient_account_last_modified_by'] = Services::session()->user_id;

        $cash_recipient_account_data['fk_approval_id'] = $this->approvalLibrary->insertApprovalRecord('cash_recipient_account');
        $cash_recipient_account_data['fk_status_id'] = $this->statusLibrary->initialItemStatus('cash_recipient_account');

        $this->write_db->table('cash_recipient_account')->insert($cash_recipient_account_data);
    }

    private function updateRequestDetailStatusOnVouching($request_detail_id, $voucher_id)
    {
        // Update the request detail record
        $builder = $this->write_db->table("request_detail");
        $builder->where(array('request_detail_id' => $request_detail_id));
        $builder->update(array('fk_voucher_id' => $voucher_id));
    }

    private function updateRequestOnPayingAllDetails($request_detail_id)
    {
        $request_id = $this->read_db->table('request_detail')->getWhere(array('request_detail_id' => $request_detail_id))->getRow()->fk_request_id;
        $unpaid_request_details = $this->read_db->table('request_detail')->getWhere(array('fk_request_id' => $request_id, 'fk_voucher_id' => 0))->getNumRows();

        if ($unpaid_request_details == 0) {
            $builder = $this->write_db->table("request");
            $builder->where(array('request_id' => $request_id));
            $builder->update(array('request_is_fully_vouched' => 1));
        }
    }

    private function updateReversalFromVoucher($from_id, $to_id, $voucher_number_from, $new_voucher_description, $total_voucher_cost, $settlementType = 'bank_refund')
    {

        $unrefunded_amount = $this->voucherLibrary->unrefundedAmountByFromVoucherId($from_id, $settlementType);
        // Get existing voucher_refunding_to ids
        $voucher_refunding_to_json = $this->read_db->table('voucher')->where(['voucher_id' => $from_id])
            ->get()->getRow()->voucher_refunding_to;

        $voucher_refunding_to_ids = [];

        if ($voucher_refunding_to_json != null) {
            $voucher_refunding_to_ids = json_decode($voucher_refunding_to_json);
            array_push($voucher_refunding_to_ids, $to_id);
        } else {
            $voucher_refunding_to_ids = [$to_id];
        }

        $data['voucher_refunding_to'] = json_encode($voucher_refunding_to_ids);
        $voucherWriteBuilder = $this->write_db->table('voucher');

        if (($total_voucher_cost - $unrefunded_amount) == 0) {
            if ($settlementType == 'bank_refund') {
                // log_message('error', 'We are on this');
                $desc['voucher_description'] = "$new_voucher_description [Refunded to $voucher_number_from]";
                $data['voucher_reversal_to'] = $to_id;
                $data['voucher_is_reversed'] = 1;
            } else {
                // log_message('error', 'We are here');
                $desc['voucher_description'] = "$new_voucher_description";
                $data['voucher_cleared_to'] = $to_id;
                $data['voucher_transaction_cleared_date'] = date('Y-m-01');
            }

            $voucherWriteBuilder->where('voucher_id', $to_id);
            $voucherWriteBuilder->update($desc);

        }

        $voucherWriteBuilder->where(['voucher_id' => $from_id]);
        $voucherWriteBuilder->update($data);

    }

    /**
     * Main method to insert a voucher and its details.
     * This method orchestrates the refactored, smaller methods.
     *
     * @param array $post
     * @param int $fullyApprovedStatusId
     * @return array
     */
    public function insertVoucher(array $post, int $fullyApprovedStatusId): array
    {
        $office_id = $post['fk_office_id'];
        $voucher_date = $post['voucher_date'];
        $voucher_number = $this->_getVoucherNumber($office_id, $post['voucher_number']);

        // Step 1: Handle monthly financial records and journals
        $this->_handleMonthlyFinancials($office_id, $voucher_date);

        // Step 2: Get voucher type effects and accounts
        $voucherType = $this->_getVoucherTypeDetails($post['fk_voucher_type_id']);

        // Step 3: Prepare and insert voucher header
        $header = $this->_prepareVoucherHeaderData($post, $voucher_number, $voucherType);
        $headerId = $this->_insertVoucherHeader($header, $fullyApprovedStatusId);

        // Step 4: Prepare and insert voucher details
        $totalVoucherCost = $this->_insertVoucherDetails($headerId, $post, $voucherType->voucher_type_effect_code);

        // Step 5: Handle all related updates
        $this->_handleRelatedUpdates($headerId, $post, $header, $totalVoucherCost, $voucherType->voucher_type_effect_code);

        // Step 6: Validate and return
        $voucher_posting_condition = $this->voucherPostingCondition($post);

        return compact('voucher_posting_condition', 'headerId');
    }
}


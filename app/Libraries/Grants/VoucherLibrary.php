<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\VoucherModel;
use App\Enums\AccountSystemSettingEnum;
use App\Enums\AccrualVoucherTypeEffects;
use App\Enums\VoucherTypeEffectEnum;
use App\Enums\AccrualLedgerAccounts;

class VoucherLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{
    protected $table;
    protected $voucherModel;
    protected $voucherTypeLibrary;
    protected $journalLibrary;

    public $lookup_tables_with_null_values = ['office_bank','office_cash'];


    function __construct()
    {
        parent::__construct();

        $this->voucherModel = new VoucherModel();

        $this->table = 'voucher';

        $this->dependant_table = "voucher_detail";
        $this->voucherTypeLibrary = new \App\Libraries\Grants\VoucherTypeLibrary();
        $this->journalLibrary = new \App\Libraries\Grants\JournalLibrary();
    }

    function detachDetailTable(): bool
    {
        return true;
    }

    /**
     * get_transaction_voucher
     * @param int $id
     * @return array
     * @access private
     * @author: Livingstone Onduso
     * @Date: 24/9/2022
     */
    public function getTransactionVoucher(string $id): array
    {

        $raw_result = $this->transactionVoucher(hash_id($id, 'decode'));

        $office_bank = $this->getOfficeBank($raw_result[0]['fk_office_bank_id']);
        $office_cash = $this->getOfficeCash($this->officeAccountSystem($raw_result[0]['fk_office_id'])->account_system_id, $raw_result[0]['fk_office_cash_id']);
        $voucher_type = $this->getVoucherType($raw_result[0]['fk_voucher_type_id']);
        $cash_recipient_account = $this->getVoucherCashRecipients(hash_id($id, 'decode'));

        $header = [];
        $body = [];

        $office = $this->read_db->table('office')
            ->getWhere(array('office_id' => $raw_result[0]['fk_office_id']))
            ->getRow();

        $header['office_name'] = $office->office_code . ' - ' . $office->office_name;
        $header['office_code'] = $office->office_code;
        $header['office_id'] = $raw_result[0]['fk_office_id'];
        $header['voucher_id'] = $raw_result[0]['voucher_id'];
        $header['voucher_date'] = $raw_result[0]['voucher_date'];
        $header['voucher_number'] = $raw_result[0]['voucher_number'];
        $header['voucher_approvers'] = isset($raw_result[0]) && $raw_result[0]['voucher_approvers'] != null ? json_decode($raw_result[0]['voucher_approvers']) : [];

        $header['voucher_type_name'] = $voucher_type->voucher_type_name;

        $header['source_account'] = '';
        $header['destination_account'] = '';

        if ($voucher_type->voucher_type_account_code == 'bank' && ($voucher_type->voucher_type_effect_code == 'income' || $voucher_type->voucher_type_effect_code == 'expense')) {
            if (sizeof((array) $office_bank) > 0) {
                $header['source_account'] = $office_bank->bank_name . '(' . $office_bank->office_bank_account_number . ')';
            }
        } elseif ($voucher_type->voucher_type_account_code == 'bank' && $voucher_type->voucher_type_effect_code == 'bank_contra') {
            if (sizeof((array) $office_bank) > 0) {
                $header['source_account'] = $office_bank->bank_name . '(' . $office_bank->office_bank_account_number . ')';
            }
            if (sizeof((array) $office_cash) > 0) {
                $header['destination_account'] = $office_cash->office_cash_name;
            }
        } elseif ($voucher_type->voucher_type_account_code == 'bank' && $voucher_type->voucher_type_effect_code == 'bank_to_bank_contra') {
            if (sizeof((array) $office_bank) > 0) {
                $header['source_account'] = $office_bank->bank_name . '(' . $office_bank->office_bank_account_number . ')';
            }


            if (!empty($cash_recipient_account) && $cash_recipient_account['office_bank_id'] > 0) {
                $builder = $this->read_db->table("office_bank");
                $builder->where(array('office_bank_id' => $cash_recipient_account['office_bank_id']));
                $header['destination_account'] = $builder->get()->getRow()->office_bank_name;
            }
        } elseif ($voucher_type->voucher_type_account_code == 'cash' && ($voucher_type->voucher_type_effect_code == 'income' || $voucher_type->voucher_type_effect_code == 'expense')) {
            if (sizeof((array) $office_cash) > 0) {
                $header['destination_account'] = $office_cash->office_cash_name;
            }
        } elseif ($voucher_type->voucher_type_account_code == 'cash' && $voucher_type->voucher_type_effect_code == 'cash_contra') {
            if (sizeof((array) $office_cash) > 0) {
                $header['source_account'] = $office_cash->office_cash_name;
            }

            if (sizeof((array) $office_bank) > 0) {
                $header['destination_account'] = $office_bank->bank_name . '(' . $office_bank->office_bank_account_number . ')';
            }
        } elseif ($voucher_type->voucher_type_account_code == 'cash' && $voucher_type->voucher_type_effect_code == 'cash_to_cash_contra') {
            if (sizeof((array) $office_cash) > 0) {
                $header['source_account'] = $office_cash->office_cash_name;
            }

            if (!empty($cash_recipient_account) && $cash_recipient_account['office_cash_id'] > 0) {
                $builder = $this->read_db->table("office_cash");
                $builder->where(array('office_cash_id' => $cash_recipient_account['office_cash_id']));
                $header['destination_account'] = $builder->get()->getRow()->office_cash_name;
            }
        }

        $header['voucher_cheque_number'] = $raw_result[0]['voucher_cheque_number'] == 0 || $raw_result[0]['voucher_cheque_number'] == null ? 0 : $raw_result[0]['voucher_cheque_number'];
        $header['voucher_vendor'] = $raw_result[0]['voucher_vendor'];

        $header['voucher_reversal_from'] = $raw_result[0]['voucher_reversal_from'];
        $header['voucher_reversal_to'] = $raw_result[0]['voucher_reversal_to'];
        $header['voucher_is_reversed'] = $raw_result[0]['voucher_is_reversed'];

        $header['voucher_vendor_address'] = $raw_result[0]['voucher_vendor_address'];
        $header['voucher_description'] = $raw_result[0]['voucher_description'];
        $header['voucher_created_date'] = $raw_result[0]['voucher_created_date'];
        $header['voucher_status_id'] = $raw_result[0]['status_id'];
        $header['effect_type_code'] = $raw_result[0]['voucher_type_effect_code'];
        $header['account_type_code'] = $raw_result[0]['voucher_type_account_code'];
        $header['fk_office_cash_id'] = $raw_result[0]['fk_office_cash_id'];
        $header['fk_office_bank_id'] = $raw_result[0]['fk_office_bank_id'];

        $count = 0;
        foreach ($raw_result as $row) {
            $body[$count]['quantity'] = $row['voucher_detail_quantity'];
            $body[$count]['description'] = $row['voucher_detail_description'];
            $body[$count]['unitcost'] = $row['voucher_detail_unit_cost'];
            $body[$count]['totalcost'] = $row['voucher_detail_total_cost'];

            if ($row['fk_expense_account_id'] > 0) {
                $body[$count]['account_code'] = $this->read_db->table('expense_account')->getWhere(
                    array('expense_account_id' => $row['fk_expense_account_id'])
                )->getRow()->expense_account_code;
            } elseif ($row['fk_income_account_id'] > 0) {
                $body[$count]['account_code'] = $this->read_db->table("income_account")->getWhere(
                    array('income_account_id' => $row['fk_income_account_id'])
                )->getRow()->income_account_code;
            } elseif ($row['fk_contra_account_id'] > 0) {
                $body[$count]['account_code'] = $this->read_db->table("contra_account")->getWhere(
                    array('contra_account_id' => $row['fk_contra_account_id'])
                )->getRow()->contra_account_code;
            }

            $allocation = $this->getProjectAllocation($row['fk_project_allocation_id']);
            $body[$count]['project_allocation_code'] = !empty($allocation) ? $allocation->project_code : "";

            $count++;
        }

        $voucher_raiser = $this->recordRaiserInfo($raw_result[0]['voucher_created_by']);

        return [
            "header" => $header,
            "body" => $body,
            "signitories" => $this->getVoucherSignitories($raw_result[0]['fk_office_id']),
            'raiser_approver_info' => $voucher_raiser,
            'account_system_id' => $office->fk_account_system_id,
        ];
    }

    /**
     * Get the signitories
     *
     * Gives an array of the voucher signitories
     *
     * @param int $office - the id office
     * @return array - An array
     * @author LOnduso
     */
    public function getVoucherSignitories(int $office): array
    {

        $voucher_signatory = array();

        //Get the signitories of a given office of a given accounting system
        $builder = $this->read_db->table("voucher_signatory");
        $builder->select(array('voucher_signatory_name'));
        $builder->join('account_system', 'account_system.account_system_id=voucher_signatory.fk_account_system_id');
        $builder->join('office', 'office.fk_account_system_id=account_system.account_system_id');
        $builder->where(array('office_id' => $office, 'voucher_signatory_is_active' => 1));
        $voucher_signatory = $builder->get()->getResultArray();

        return $voucher_signatory;
    }

    function recordRaiserInfo($user_id)
    {
        $builder = $this->read_db->table("user");
        $builder->select(['user_firstname', 'user_lastname', 'role_name']);
        $builder->join('role', 'role.role_id=user.fk_role_id');
        $user_obj = $builder->getWhere(array('user_id' => $user_id));

        $user_info = [];

        if ($user_obj->getNumRows() > 0) {
            $user = $user_obj->getRow();
            $user_info['full_name'] = $user->user_firstname . ' ' . $user->user_lastname;
            $user_info['role_name'] = $user->role_name;
        }

        return $user_info;
    }

    /**
     * get_project_allocation
     * @param int $allocation_id
     * @return mixed [Object or Array]
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function getProjectAllocation(int $allocation_id)
    {
        $builder = $this->read_db->table("project_allocation");
        $builder->join('project', 'project.project_id=project_allocation.fk_project_id');
        $result = $builder->getWhere(array('project_allocation_id' => $allocation_id));

        if ($result->getNumRows() > 0) {
            return $result->getRow();
        } else {
            return [];
        }
    }

    /**
     * get_transaction_voucher
     * @param int $voucher_type_id
     * @return array
     * @access public
     * @author: Livingstone Onduso
     * @Date: 24/9/2022
     */
    function transactionVoucher(string $id): array
    {

        // Create approvers column 
        $this->createTableApproversColumns('voucher');

        $builder = $this->read_db->table('voucher');
        $builder->select(array(
            'voucher_id',
            'fk_office_id',
            'fk_office_cash_id',
            'voucher_date',
            'voucher_number',
            'fk_office_bank_id',
            'fk_voucher_type_id',
            'voucher_cheque_number',
            'voucher_vendor',
            'voucher_reversal_from',
            'voucher_reversal_to',
            'voucher_vendor_address',
            'voucher_description',
            'voucher_created_date',
            'voucher.fk_status_id as status_id',
            'voucher_created_by',
            'voucher_is_reversed',
            'voucher_type_effect_code',
            'voucher_type_account_code'
        ));

        $builder->select(array(
            'voucher_detail_quantity',
            'voucher_detail_description',
            'voucher_detail_unit_cost',
            'voucher_detail_total_cost',
            'fk_expense_account_id',
            'fk_income_account_id',
            'fk_contra_account_id',
            'fk_project_allocation_id',
            'voucher_approvers'
        ));

        $builder->join('voucher_detail', 'voucher_detail.fk_voucher_id=voucher.voucher_id');
        $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $builder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

        return $builder->getWhere(array('voucher_id' => $id))->getResultArray();
    }

    /**
     * get_office_bank
     * @param int $office_bank_id
     * @return mixed [Object or Array]
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function getOfficeBank(int $office_bank_id)
    {
        $builder = $this->read_db->table("office_bank");
        $builder->join('bank', 'bank.bank_id=office_bank.fk_bank_id');
        $result = $builder->getWhere(array('office_bank_id' => $office_bank_id));

        if ($result->getNumRows() > 0) {
            return $result->getRow();
        } else {
            return [];
        }
    }

    /**
     * get_office_cash
     * @param int $account_system_id, int $office_cash_id = 0
     * @return mixed [Object or Array]
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function getOfficeCash(int $account_system_id, int $office_cash_id = 0)
    {
        $result = $this->read_db->table('office_cash')
            ->getWhere(array('fk_account_system_id' => $account_system_id, 'office_cash_id' => $office_cash_id));

        if ($result->getNumRows() > 0) {
            return $result->getRow();
        } else {
            return [];
        }
    }

    function officeAccountSystem($office_id)
    {
        $builder = $this->read_db->table('office');
        $builder->join('account_system', 'account_system.account_system_id=office.fk_account_system_id');
        $office_accounting_system = $builder->getWhere(array('office_id' => $office_id))->getRow();

        return $office_accounting_system;
    }

    /**
     * get_voucher_type
     * @param int $voucher_type_id
     * @return object
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function getVoucherType(int $voucher_type_id): object
    {
        $builder = $this->read_db->table('voucher_type');
        $builder->select(array('voucher_type_effect_id', 'voucher_type_effect_code', 'voucher_type_id', 'voucher_type_name', 'voucher_type_abbrev', 'voucher_type_account_id', 'voucher_type_account_name', 'voucher_type_account_code'));
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $builder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $voucher_type = $builder->getWhere(
            array('voucher_type_id' => $voucher_type_id)
        )->getRow();

        return $voucher_type;
    }

    public function getVoucherCashRecipients($new_voucher_id)
    {
        $builder = $this->read_db->table("cash_recipient_account");
        $builder->select(array('fk_office_bank_id as office_bank_id', 'fk_office_cash_id as office_cash_id'));
        $builder->where(array('fk_voucher_id' => $new_voucher_id));
        $cash_recipient_account_obj = $builder->get();

        $cash_recipient_account = [];

        if ($cash_recipient_account_obj->getNumRows() > 0) {
            $cash_recipient_account = $cash_recipient_account_obj->getRowArray();
        }

        return $cash_recipient_account;
    }

    function isVoucherCancellable($status_data, $voucher_data)
    {
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        $is_voucher_cancellable = false;
        $initial_item_status = $statusLibrary->initialItemStatus('Voucher');
        $direction = $status_data['item_status'][$voucher_data['voucher_status_id']]['status_approval_direction'];
        $roles = $status_data['item_status'][$voucher_data['voucher_status_id']]['status_role'];
        $voucher_is_reversed = $voucher_data['voucher_is_reversed'];

        if (
            ($voucher_data['voucher_status_id'] == $initial_item_status || $direction == -1) &&
            $voucher_is_reversed == 0 &&
            array_intersect($roles, $this->session->role_ids)
        ) {
            $is_voucher_cancellable = true;
        }

        return $is_voucher_cancellable;
    }

    /**
     * @todo Checkifincome or expense
     */

    function checkPendingExpensesExceedsTotalIncome(array $voucher_data)
    {

        $financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();

        $is_expense_more_than_income = false;
        //Get the selected voucher total_cost_amount
        $selected_voucher_income_amount = $this->selectedVoucherIncomeTotalCost(hash_id($this->id, 'decode'));
        //Bank Income vs Expenses
        if ($voucher_data['effect_type_code'] == 'income' && $voucher_data['account_type_code'] == 'bank') {
            //Income Totals
            $unapproved_income_voucher_total = $this->unapprovedMonthVouchers($voucher_data['office_id'], $voucher_data['voucher_date'], 'income', 'bank', $voucher_data['fk_office_cash_id'], $voucher_data['fk_office_bank_id']);

            $full_bank_income_voucher_total = $financialReportLibrary->computeCashAtBank([$voucher_data['office_id']], $voucher_data['voucher_date'], [], [$voucher_data['fk_office_bank_id']], true);

            $total_income_bal = $unapproved_income_voucher_total + $full_bank_income_voucher_total;

            //Get all expenses
            $total_current_expense_voucher = $this->unapprovedMonthVouchers($voucher_data['office_id'], $voucher_data['voucher_date'], 'expense', 'bank', $voucher_data['fk_office_cash_id'], $voucher_data['fk_office_bank_id']);

            //Less amount of the selected voucher
            $total_income_bal -= $selected_voucher_income_amount;

            if (($total_current_expense_voucher > $total_income_bal) && $total_current_expense_voucher > 0) {

                $is_expense_more_than_income = true;
            }
        } else if ($voucher_data['effect_type_code'] == 'bank_contra' && $voucher_data['account_type_code'] == 'bank') {
            //Full cash vouchers
            $full_cash_income_voucher_total = $financialReportLibrary->computeCashAtHand([$voucher_data['office_id']], $voucher_data['voucher_date'], [], [], $voucher_data['fk_office_cash_id'], true);
            //Petty Cash Deposit
            $total_petty_cash_deposit_voucher = $this->unapprovedMonthVouchers($voucher_data['office_id'], $voucher_data['voucher_date'], 'bank_contra', 'bank', $voucher_data['fk_office_cash_id'], $voucher_data['fk_office_bank_id']);

            if (($total_petty_cash_deposit_voucher < 0 && $full_cash_income_voucher_total >= 0) || ($total_petty_cash_deposit_voucher >= 0 && $total_petty_cash_deposit_voucher >= 0)) {
                $total_petty_cash = $full_cash_income_voucher_total + $total_petty_cash_deposit_voucher;
            } else if ($total_petty_cash_deposit_voucher >= 0 && $full_cash_income_voucher_total < 0 || ($total_petty_cash_deposit_voucher >= 0 && $total_petty_cash_deposit_voucher >= 0)) {
                $total_petty_cash = $total_petty_cash_deposit_voucher + $full_cash_income_voucher_total;
            }

            //Get all expenses
            $total_current_expense_voucher = $this->unapprovedMonthVouchers($voucher_data['office_id'], $voucher_data['voucher_date'], 'expense', 'cash', $voucher_data['fk_office_cash_id'], $voucher_data['fk_office_bank_id']);

            //Check if total expenses > total cash
            $total_petty_cash -= $selected_voucher_income_amount;

            if ((number_format($total_current_expense_voucher, 2) > number_format($total_petty_cash, 2)) && number_format($total_current_expense_voucher, 2) > 0) {

                $is_expense_more_than_income = true;
            }
        }

        return $is_expense_more_than_income;
    }

    /**
     *Selected_voucher_income_total_cost(): Returns cash recieved in the bank or cash deposit in petty cash box on the selected voucher
     * @author Livingstone Onduso: Dated 08-04-2023
     * @access public
     * @param int $voucher_id - voucher id
     * @return float - returns summed up cash
     */

    public function selectedVoucherIncomeTotalCost(int $voucher_id): float
    {
        $builder = $this->read_db->table("voucher_detail");
        $builder->selectSum('voucher_detail_total_cost');
        $builder->where(['fk_voucher_id' => $voucher_id]);

        return $builder->get()->getRow()->voucher_detail_total_cost;
    }

    /**
     *unapproved_month_vouchers(): Returns the total of unapproved vouchers for current month for an office
     *
     * @author Livingstone Onduso: Dated 08-04-2023
     * @access public
     * @param int $office_id - Office primary key
     * @param string $reporting_month - Date of the month
     * @param string $effect_code - Effect code e.g. income or expense
     * @param string $account_code - Account code e.g cash or bank
     * @param int $cash_type_id - Cash type e.g. petty cash
     * @param int $office_bank_id - Cash type e.g. bank 1
     * @return float - True if reconciliation has been created else false
     */
    public function unapprovedMonthVouchers(int $office_id, string $reporting_month, string $effect_code, string $account_code, int $cash_type_id = 0, int $office_bank_id = 0): float
    {

        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        $max_approval_status_ids = $statusLibrary->getMaxApprovalStatusId('voucher');
        $start_of_reporting_month = date('Y-m-01', strtotime($reporting_month));

        $end_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

        $builder = $this->read_db->table('voucher_detail');
        $builder->selectSum('voucher_detail_total_cost');
        $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $builder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $builder->where(['voucher.fk_office_id' => $office_id, 'voucher.voucher_date >=' => $start_of_reporting_month, 'voucher.voucher_date <=' => $end_of_reporting_month, 'voucher.fk_status_id!=' => $max_approval_status_ids[0]]);
        $builder->where(['voucher_type_effect_code' => $effect_code, 'voucher_type_account_code' => $account_code]);

        if ($cash_type_id != 0) {
            $builder->where(['fk_office_cash_id' => $cash_type_id]);
        } else if ($office_bank_id != 0) {

            $builder->where(['fk_office_bank_id' => $office_bank_id]);
        }

        $builder->groupBy(array('voucher_detail.fk_voucher_id'));
        $results = $builder->get()->getResultArray();

        $totals_arr = array_column($results, 'voucher_detail_total_cost');
        return array_sum($totals_arr);
    }

    /**
     *get_voucher_header_to_edit(): Returns a row of voucher information from voucher table [Main header table]
     * @author Livingstone Onduso: Dated 08-05-2023
     * @access public
     * @param Int $voucher_id - voucher id
     * @return array - returns one row of array
     */

    public function getVoucherHeaderToEdit(int $voucher_id): array
    {
        $builder = $this->read_db->table("voucher");
        $builder->select(['voucher_id', 'office_id', 'office_code', 'voucher_type_account_name', 'voucher_type_effect_name','voucher_type_effect_code', 'voucher_type_is_cheque_referenced', 'voucher_number', 'voucher_date', 'fk_voucher_type_id', 'voucher_type_name', 'fk_office_bank_id', 'fk_office_cash_id', 'office_bank_name', 'voucher_cheque_number', 'voucher_vendor', 'voucher_vendor_address', 'voucher_description']);
        $builder->join('office', 'office.office_id=voucher.fk_office_id');
        $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $builder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $builder->join('office_bank', 'office_bank.office_bank_id=voucher.fk_office_bank_id');
        $builder->where(['voucher_id' => $voucher_id]);
        $voucher_to_edit = $builder->get()->getRowArray();

        //Add the office cash name in the array if fk_office_cash_id!=0
        if ($voucher_to_edit['fk_office_cash_id'] != 0) {
            $builder = $this->read_db->table("office_cash");
            $builder->select(['office_cash_name']);
            $builder->where(['office_cash_id' => $voucher_to_edit['fk_office_cash_id']]);
            $office_cash_name = $builder->get()->getRowArray();

            $voucher_to_edit = array_merge($voucher_to_edit, $office_cash_name);
        }

        return $voucher_to_edit;
    }


    function listTableVisibleColumns(): array
    {
        $columns = [
            'voucher_id',
            'voucher_track_number',
            'voucher_number',
            'voucher_description',
            'voucher_date',
            'voucher_cheque_number',
            'voucher_is_reversed',
            'voucher_created_date',
            'office_name',
            'voucher_type_name',
            'status_name',
        ];

        return $columns;
    }

    function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void
    {
        // parent::listTableWhere($queryBuilder);
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $queryBuilder->whereNotIn('voucher.fk_status_id', $statusLibrary->getMaxApprovalStatusId($this->controller));
        
    }

    function changeFieldType(): array
    {
        $change_field_type = array();

        $change_field_type['voucher_number']['field_type'] = 'text';

        return $change_field_type;
    }

    /**
     * Get Voucher Date
     *
     * This method computes the next valid vouching date for a given office
     * @param int $office_id - The primary key of the office
     * @return string - The next valid vouching date
     *
     */

    public function getVoucherDate(int $office_id, string $journal_month = ''): string
    {
        $builder = $this->read_db->table('office');
        $voucher_date = $builder->getWhere(array('office_id' => $office_id))->getRow()->office_start_date;
        $office_transaction_date = $this->getOfficeTransactingMonth($office_id);

        $getOfficeLastVoucher = $this->getOfficeLastVoucher($office_id);
        if (count($getOfficeLastVoucher) > 0) {
            $voucher_date = $getOfficeLastVoucher['voucher_date'];
        }

        if (strtotime($office_transaction_date) > strtotime($voucher_date)) {
            $voucher_date = $office_transaction_date;
        }

        return $voucher_date;
    }

    /**
     * Get Office Last Voucher
     *
     * The methods get the last voucher record for a given office
     *
     * @param int $office_id - Office in check
     * @return array - a voucher record
     */
    public function getOfficeLastVoucher($office_id, $journal_month = '')
    {
        $last_voucher = [];
        $office_has_started_transacting = $this->checkIfOfficeHasStartedTransacting($office_id);

        if ($office_has_started_transacting) {
            $financial_report_month = '';

            //If voucher_reversal use the journal month and not report month
            /*Scenerios:
            Scenerio 1: Report where reversal is happening is submitted [Find latest report and insert voucher there. Date should be computed for latest month]
            Scenerio 2: Repoert where reversal is happening is NOT submitted [Insert voucher in the same month where reversal happening
             */

            if ($journal_month != '') {
                //Check if report is submitted;if submitted get the max report then get lastest voucher date and there insert voucher
                $financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();
                $mfr_submitted = $financialReportLibrary->checkIfFinancialReportIsSubmitted([$office_id], $journal_month);

                if ($mfr_submitted == true) {
                    //get max unsubmitted report and get the last transaction voucher
                    $financial_report_month_obj = $this->selectMaxFinancialReport($office_id, true);
                    //if >0 get the last voucher date
                    if ($financial_report_month_obj->getNumRows() > 0) {
                        $financial_report_month = $financial_report_month_obj->getRow()->financial_report_month;
                        $last_voucher = $this->getMaxVoucher($office_id, $financial_report_month);

                        if (empty($last_voucher)) {
                            $last_voucher = $this->getCalculatedLastVoucher($office_id, $financial_report_month_obj->getRow()->financial_report_month);
                        }
                    }
                } else {
                    // Check the max voucher id of the oldest unsubmitted reporting month for the office
                    $last_voucher = $this->getMaxVoucher($office_id, $journal_month);
                }
            } else {
                // Get the oldest unsubmitted financial report for the office
                $financial_report_month_obj = $this->selectMaxFinancialReport($office_id, false);
                if ($financial_report_month_obj->getRow()->financial_report_month > 0) {
                    //log_message('error','Has unsubmitted MFR');
                    $financial_report_month = $financial_report_month_obj->getRow()->financial_report_month;
                    // Check the max voucher id of the oldest unsubmitted reporting month for the office
                    $last_voucher = $this->getMaxVoucher($office_id, $financial_report_month);

                    // Retrieve the voucher record for oldest unsubmitted reporting month
                    // If voucher_id is null then no vouchers in tha month [e.g. all month vouchers have been deleted]
                    if (empty($last_voucher)) {

                        $this->getCalculatedLastVoucher($office_id, $financial_report_month);
                    }
                } else {
                    //Get max voucher_id based on max financial_report_month
                    $financial_report_month_obj = $this->selectMaxFinancialReport($office_id, true);
                    $financial_report_month = $financial_report_month_obj->getRow()->financial_report_month;
                    $last_voucher = $this->getMaxVoucher($office_id, $financial_report_month);
                }
            }
        }

        return $last_voucher;
    }

    /**
     * get_calculated_last_voucher
     * Get the calculated last voucher of the months
     * @param int $office_id, string $financial_report_month
     * @author Livingstone Onduso.
     * @date 2024-03-15
     */
    private function getCalculatedLastVoucher(int $office_id, string $financial_report_month)
    {
        $office_transacting_month = $this->read_db->table('office')->getWhere(array('office_id' => $office_id))->getRow()->office_start_date;
        $start_office_month = date('Y-m-01', strtotime($office_transacting_month));
        $calculated_month_from_voucher = date('Y-m-01', strtotime($financial_report_month . '- 1 months'));

        //Check if the month calculated based on vouchers is below the office start date. If so use the the office_transacting_month to get the first voucher number
        if ($calculated_month_from_voucher > $start_office_month) {
            $builder = $this->read_db->table('voucher');
            $builder->where([
                'voucher_date >=' => date('Y-m-01', strtotime($financial_report_month . '- 1 months')),
                'voucher_date <=' => date('Y-m-t', strtotime($financial_report_month . '- 1 months')),
                'fk_office_id' => $office_id,
            ]);

            $voucher_id = $builder->selectMax('voucher_id')->get()->getRow()->voucher_id;
            $last_voucher = $builder->getWhere(['voucher_id' => $voucher_id])->getRowArray();
        } else {
            //Construct the first voucher of the month
            $year = date("y", strtotime($office_transacting_month));
            $month = date('m', strtotime($office_transacting_month));
            $voucher_number = $year . $month . '00';
            $last_voucher = ['voucher_date' => $office_transacting_month, 'voucher_number' => $voucher_number];
        }

        return $last_voucher;
    }

    /**
     * get_max_voucher
     * Get the maximum voucher of the month
     * @param int $office_id, string $financial_report_month
     * @author Livingstone Onduso.
     * @date 2024-03-15
     */
    private function getMaxVoucher(int $office_id, string $financial_report_month)
    {
        /**
         * 
         * PLEASE NOTE THE Query bellow to GET DATA HAS TO USE $this->write_db and NOT $this->read_db DUE TO WRITE and READ  DELAY !!!!!!
         */

        //Get the max voucher of the passed month
        $builder = $this->read_db->table("voucher");
        $builder->selectMax('voucher_id');
        $builder->where([
            'voucher.fk_office_id' => $office_id,
            'voucher_date >=' => date('Y-m-01', strtotime($financial_report_month)),
            'voucher_date <=' => date('Y-m-t', strtotime($financial_report_month))
        ]);

        $voucher_id = $builder->get()->getRow()->voucher_id;

        $builder = $this->read_db->table('voucher');
        $builder->select(['voucher_id', 'voucher_number', 'voucher_date']);
        $builder->where(['voucher_id' => $voucher_id]);
        return $builder->get()->getRowArray();
    }


    /**
     * select_max_financial_report
     * Get the maximum/minimum mfrs of the month
     * @param int $office_id, bool $max_mfr
     * @author Livingstone Onduso.
     * @date 2024-03-15
     */
    private function selectMaxFinancialReport(int $office_id, bool $max_mfr)
    {

        //Get the Max mfr or Min depending on the boolean '$max_mfr'
        $builder = $this->read_db->table("financial_report");
        if ($max_mfr == true) {
            $builder->selectMax('financial_report_month');
            $builder->where(array('fk_office_id' => $office_id));
            $financial_report_month_obj = $builder->get();
        } else {
            $builder->selectMin('financial_report_month');
            $builder->where(array('financial_report_is_submitted' => 0, 'fk_office_id' => $office_id));
            $financial_report_month_obj = $builder->get();
        }

        return $financial_report_month_obj;
    }

    /**
     * Check if Office Has Started Transacting
     *
     * Finds out if the argument offfice has began raising vouchers
     *
     * @param int $office_id - Office in check
     * @return bool - True if has began raising vouchers else false
     */
    public function checkIfOfficeHasStartedTransacting(int $office_id): bool
    {
        // If the office has not voucher yet, then the transacting month equals the office start date
        $count_of_vouchers = $this->read_db->table('voucher')
            ->getWhere(array('fk_office_id' => $office_id))->getNumRows();

        return $count_of_vouchers > 0 ? true : false;
    }

    /**
     * get_office_transacting_month
     *
     * This methods gives the date of the first day of the valid transaction month of an office
     *
     * @param int $office - Office in check
     * @return string - Date of the first day of the valid transacting month
     */
    public function getOfficeTransactingMonth(int $office_id): string
    {
        $office_transacting_month = date('Y-m-01');
        //If count_of_vouchers eq to 0 then get the start date if the office
        if (!$this->checkIfOfficeHasStartedTransacting($office_id)) {
            $office_transacting_month = $this->read_db->table('office')->getWhere(array('office_id' => $office_id))->getRow()->office_start_date;
        } else {
            // Get the last office voucher date
            $voucher_date = $this->getOfficeLastVoucher($office_id)['voucher_date'];
            // Check if the transacting month has been closed based on the last voucher date
            if ($this->checkIfOfficeTransactingMonthHasBeenClosed($office_id, $voucher_date)) {
                // echo $voucher_date; exit();
                $office_transacting_month = date('Y-m-d', strtotime('first day of next month', strtotime($voucher_date)));
            } else {
                $office_transacting_month = date('Y-m-01', strtotime($voucher_date));
            }
        }

        return $office_transacting_month;
    }

    /**
     * Check if Office Transaction Month Has Been Closed
     *
     * Finds out if the date passed as an argument belongs to a month whose vouching process has been closed based on whether the financial report (Bank Reconciliation)
     * has been created and submitted.
     *
     * @param int $office_id - Office primary key
     * @param string $date_of_month - Date of the month in check
     * @return bool - True if reconciliation has been created else false
     */
    public function checkIfOfficeTransactingMonthHasBeenClosed(int $office_id, string $date_of_month): bool
    {
        // If the reconciliation of the max date month has been done and submitted,
        // then use the start date of the next month as the transacting date
        // *** Modify the query by checking if it has been submitted - Not yet done ****

        $check_month_reconciliation = $this->read_db->table('financial_report')->getWhere(
            array(
                'financial_report_is_submitted' => 1,
                'fk_office_id' => $office_id,
                'financial_report_month' => date('Y-m-01', strtotime($date_of_month)),
            )
        )->getNumRows();

        return $check_month_reconciliation > 0 ? true : false;
    }

    function detailTables(): array
    {
        return ["voucher_detail"];
    }

    /**
     * Get Voucher Number
     *
     * The method computes the next valid voucher number. The voucher numbers are in the format YYMMSS where YY is the fiscal year and MM is the month whe transaction
     * belongs to. SS is the voucher serial number incremented from 1 (First Voucher of the month)
     *
     * @param int $office_id - The primary key of the office
     * @return int - The next valid voucher number
     */
    public function getVoucherNumber(int $office_id, string $journal_month = ''): int
    {

        $financialReportLibrary = new FinancialReportLibrary();
        $office_transacting_month = '';
        $office_transacting_month = $this->getOfficeTransactingMonth($office_id);

        /*New code added from here. Date of addition 26-02-2024
        If reversal use the date of voucher as the transacting month else use the get_office_transacting_month to compute the transacting months.*/
        /*If current month report is submitted=true get the date on curent month use it to compute next serial new CJ
        If current month not submitted use it to get the last voucher date and use it to compute next serial

         */

        if ($journal_month != '') {

            $mfr_submitted = $financialReportLibrary->checkIfFinancialReportIsSubmitted([$office_id], $journal_month);

            if ($mfr_submitted != true) {
                $office_transacting_month = $journal_month;
            }
        }
        $next_serial_number = $this->getVoucherNextSerialNumber($office_id, $office_transacting_month);
        return $this->computeVoucherNumber($office_transacting_month, $next_serial_number);
    }


    /**
     * Compute Voucher Number
     *
     * This method computes the next valid voucher number by concatenating the YY, MM and SS together.
     * YY - Vouching Year, MM - Vouching Month and SS - Voucher Serial Number in the month
     *
     * @param string $vouching_month - Date the voucher is being raised
     * @param int $next_voucher_serial - Next valid voucher serial number
     * @return int - A Voucher number
     */
    public function computeVoucherNumber(string $vouching_month, int $next_voucher_serial = 1): string
    {

        $chunk_year_from_date = date('y', strtotime($vouching_month));
        $chunk_month_from_date = date('m', strtotime($vouching_month));

        if ($next_voucher_serial < 10) {
            $next_voucher_serial = '0' . $next_voucher_serial;
        }

        return $chunk_year_from_date . $chunk_month_from_date . $next_voucher_serial;
    }


    /**
     * Get Voucher Next Serial Number
     *
     * Computes the next voucher serial number i.e. The 5th + digits in a voucher number
     *
     * @param int $office_id - Office in Check
     * @return int - Next voucher serial number
     */
    public function getVoucherNextSerialNumber(int $office_id, string $journal_month = ''): int
    {
        // Set default serial number to 1 unless adding to a series in a month
        $next_serial = 1;
        $last_voucher = $this->getOfficeLastVoucher($office_id, $journal_month);
        // Start checking if the office has a last voucher record
        if (count((array) $last_voucher) > 0) {
            $last_voucher_number = $last_voucher['voucher_number'];
            $last_voucher_date = $last_voucher['voucher_date'];

            $transacting_month_has_been_closed = $this->checkIfOfficeTransactingMonthHasBeenClosed($office_id, $last_voucher_date);
            if (!$transacting_month_has_been_closed) {
                // Get the serial number of the last voucher, replace the month and year part of the
                // voucher number with an empty string to remain with only the voucher serial number
                //voucher format - yymmss or yymmsss
                $current_voucher_serial_number = substr_replace($last_voucher_number, '', 0, 4);
                $next_serial = $current_voucher_serial_number + 1;
            }
        }

        return $next_serial;
    }

    // function accountsRecievables($officeId, $transactionDate){
    //     $voucherDetailReaderBuilder = $this->read_db->table('voucher_detail');
    //     $transactionDate = date('Y-m-t', strtotime($transactionDate));

    //     $voucherDetailReaderBuilder->selectSum('voucher_detail_total_cost');
    //     $voucherDetailReaderBuilder->select('voucher_type_effect_code,fk_income_account_id,voucher_id');
    //     $voucherDetailReaderBuilder->join('voucher','voucher.voucher_id=voucher_detail.fk_voucher_id');
    //     $voucherDetailReaderBuilder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    //     $voucherDetailReaderBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    //     $voucherDetailReaderBuilder->where(['fk_office_id' => $officeId]);
    //     $voucherDetailReaderBuilder->whereIn('voucher_type_effect_code', [VoucherTypeEffectEnum::RECEIVABLES->getCode(), VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode()]);
    //     $voucherDetailReaderBuilder->groupStart();
    //         $voucherDetailReaderBuilder->where('voucher_transaction_cleared_date', NULL);
    //         $voucherDetailReaderBuilder->orWhere('voucher_transaction_cleared_date <=', $transactionDate);
    //     $voucherDetailReaderBuilder->groupEnd();
    //     $voucherDetailReaderBuilder->groupBy('voucher_type_effect_code,fk_income_account_id,voucher_id');
    //     $queryResultObj = $voucherDetailReaderBuilder->get();

    //     $queryResult = [];

    //     if($queryResultObj->getNumRows() > 0){
    //         $queryResult = $queryResultObj->getResultArray();
    //     }

    //     return $queryResult;
    // }


    // function accountsRecievableBalance($officeId, $transactionDate){
    //     $this->accountsRecievables($officeId, $transactionDate);
    // }

    // function accountPayablesBalance($officeId){
    //     $voucherDetailReaderBuilder = $this->read_db->table('voucher_detail');
    // }

    // function prepaymentsBalance($officeId){
    //     $voucherDetailReaderBuilder = $this->read_db->table('voucher_detail');
    // }

    function getActiveVoucherTypes($account_system_id, $office_id, $transaction_date)
    {

        $officeBankLibrary = new OfficeBankLibrary();
        $chequeBookLibrary = new ChequeBookLibrary();
        $journalLibrary = new JournalLibrary();
        
        $accountSystemSettingLibrary = new \App\Libraries\Core\AccountSystemSettingLibrary();
        $account_system_settings = $accountSystemSettingLibrary->getAccountSystemSettings($this->session->user_account_system_id);
        $isAccrualActivated = $journalLibrary->checkIfAccountingSystemAccrualIsActivated($account_system_id, $office_id, $transaction_date, false);

        $office_banks_for_office = $officeBankLibrary->getOfficeBanksForOffice($office_id);
        // Do not show bank_to_bank_contra voucher effect types if the office has only 1 bank
        $builder = $this->read_db->table("voucher_type");
        if (count($office_banks_for_office['is_active']) < 2) {
            $builder->whereNotIn('voucher_type_effect_code', ['bank_to_bank_contra']);
        }

        if (!empty($office_banks_for_office['chequebook_exemption_expiry_date'])) {

            foreach ($office_banks_for_office['chequebook_exemption_expiry_date'] as $office_bank_id => $chequebook_exemption_expiry_date) {
                if ($chequebook_exemption_expiry_date > $transaction_date) {
                    $leaves = $chequeBookLibrary->getRemainingUnusedChequeLeaves($office_bank_id);
                    if (empty($leaves)) {
                        $builder->where(['voucher_type_is_cheque_referenced' => 0]);
                    }
                    break;
                }
            }

        }

        if(!$isAccrualActivated){
            $builder->where(['voucher_type_account_code <> ' => 'accrual']);
        }

        $builder->select(array('voucher_type_id', 'voucher_type_name', 'voucher_type_account_code', 'voucher_type_effect_code'));
        $builder->join('account_system', 'account_system.account_system_id=voucher_type.fk_account_system_id');
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $builder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        if(
            !array_key_exists(AccountSystemSettingEnum::ACCRUAL_SETTING_NAME->value,$account_system_settings)  ||
            $account_system_settings[AccountSystemSettingEnum::ACCRUAL_SETTING_NAME->value] == 0){
              $builder->whereIn('voucher_type_account_code', ['bank', 'cash']);
        }
        $voucher_types = $builder->getWhere(array('voucher_type_is_active' => 1, 'voucher_type_is_hidden' => 0, 'fk_account_system_id' => $account_system_id))
            ->getResultObject();

        return $voucher_types;
    }

    function voucherTypeRequiresChequeReferencing($voucher_type_id)
    {
        $builder = $this->read_db->table('voucher_type');
        $builder->select(array('voucher_type_is_cheque_referenced'));
        $voucher_type_is_cheque_referenced = $builder->getWhere(array('voucher_type_id' => $voucher_type_id))
            ->getRow()->voucher_type_is_cheque_referenced;

        return $voucher_type_is_cheque_referenced;
    }
    

    function voucherTypeEffectAndCode($voucher_type_id)
    {
        $builder = $this->read_db->table('voucher_type');
        $builder->select(array('voucher_type_account_code', 'voucher_type_effect_code'));
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $builder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $builder->where(array('voucher_type_id' => $voucher_type_id));
        $voucher_type_effect_and_code = $builder->get()
        ->getRow();

        return $voucher_type_effect_and_code;
    }

    function getOfficeBanks($office_id)
    {
        $builder = $this->read_db->table('office_bank');
        $builder->select(array('DISTINCT(office_bank_id) as item_id', 'bank_name', 'office_bank_name as item_name', 'office_bank_account_number '));
        $builder->join('bank', 'bank.bank_id=office_bank.fk_bank_id');
        $builder->join('office_bank_project_allocation', 'office_bank.office_bank_id=office_bank_project_allocation.fk_office_bank_id');

        $office_banks = $builder->getWhere(
            array('fk_office_id' => $office_id, 'office_bank_is_active' => 1)
        )->getResultObject();

        return $office_banks;
    }

    /**
     * get_count_of_request
     * @param
     * @return int
     * @author: Onduso
     * @Date: 4/12/2020
     */
    public function getCountOfUnvouchedRequest($office_id): int
    {
        $builder = $this->read_db->table("request");
        $builder->join('request_detail', 'request.request_id=request_detail.fk_request_id');
        $builder->where(array('fk_office_id' => $office_id));
        $builder->where(array('request_is_fully_vouched' => 0));
        $builder->where(array('fk_voucher_id' => 0));

        $unvouched_request = $builder->get()->getNumRows();

        return $unvouched_request;
    }

    /**
     *total_cost_for_voucher_to_edit(): Returns the total cost for the voicuher being edited
     * @author Livingstone Onduso: Dated 03-11-2023
     * @access public
     * @return float - json string
     */
    public function totalCostForVoucherToEdit(int $voucher_id): float
    {
        //Get the sum of voucher_detail_total_cost
        $total_cost = 0.00;

        $builder = $this->read_db->table("voucher_detail");
        $builder->selectsum('voucher_detail_total_cost');
        $builder->where(['fk_voucher_id' => $voucher_id]);
        $arr_result = $builder->get()->getResultArray();

        if ($arr_result[0]['voucher_detail_total_cost'] != null) {
            $total_cost = $arr_result[0]['voucher_detail_total_cost'];
        }

        return $total_cost;
    }

    /**
     * Get Approved Unvouched Request Details
     *
     * List all the request details that have been finalised in the approval workflow
     * @return Array
     */
    public function getApprovedUnvouchedRequestDetails($office_id): array
    {
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        $max_approval_status_ids = $statusLibrary->getMaxApprovalStatusId('request');

        $builder = $this->read_db->table("request_detail");
        $builder->select(array('request_detail_id', 'request_track_number', 'request_detail_description', 'request_detail_quantity', 'request_detail_unit_cost', 'request_detail_total_cost', 'expense_account_name', 'project_name'));
        $builder->join('expense_account', 'expense_account.expense_account_id=request_detail.fk_expense_account_id');
        $builder->join('project_allocation', 'project_allocation.project_allocation_id=request_detail.fk_project_allocation_id');
        $builder->join('project', 'project.project_id=project_allocation.fk_project_id');
        $builder->join('request', 'request.request_id=request_detail.fk_request_id');
        $builder->join('status', 'status.status_id=request.fk_status_id');
        $builder->whereIn('request.fk_status_id', $max_approval_status_ids);
        $builder->where(array('fk_voucher_id' => 0));
        return $builder->get()->getResultArray();
    }

    public function getDuplicateChequesForAnOffice($office_id, $cheque_number, $office_bank_id, $hold_cheque_number_for_edit = 0, $has_eft = 0)
    {

        $duplicate_cheque_exist = 0;

        //get duplicate cheques
        $builder = $this->read_db->table("voucher");
        $builder->select(array('voucher_cheque_number'));
        $builder->where(array('fk_office_id' => $office_id, 'voucher_cheque_number' => $cheque_number, 'fk_office_bank_id' => $office_bank_id));

        //Added by Onduso on 24th May 2023 to seperate EFT numbers with cheques
        if ($has_eft == 1) {
            $builder->where(['voucher_type_is_cheque_referenced' => 0]);
        } else {
            $builder->where(['voucher_type_is_cheque_referenced' => 1]);
        }
        $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');

        //End of addition
        $duplicate_cheque_number = $builder->get()->getNumRows();
        if ($hold_cheque_number_for_edit != $cheque_number) {
            //get duplicate cheques
            $builder = $this->read_db->table("voucher");
            $builder->select(array('voucher_cheque_number'));
            $builder->where(array('fk_office_id' => $office_id, 'voucher_cheque_number' => $cheque_number, 'fk_office_bank_id' => $office_bank_id));
            $duplicate_cheque_number = $builder->get()->getNumRows();

            //if greater than zero then duplicates exists
            if ($duplicate_cheque_number > 0) {
                $duplicate_cheque_exist = 1;
            }
        }

        return $duplicate_cheque_exist;
    }

    /**
     *get_effect_code_and_account_code(): Returns a row of voucher_type_effect_code and voucher_type_account_code
     * @author Livingstone Onduso: Dated 08-04-2023
     * @access public
     * @param int $voucher_type_id - voucher type id id
     * @return object - returns a row with effect code and account code
     */
    public function getAccountAndEffectCodes(int $voucher_type_id): object
    {
        $builder = $this->read_db->table("voucher_type");
        $builder->select(array('voucher_type_effect_code', 'voucher_type_account_code'));
        $builder->where(array('voucher_type_id' => $voucher_type_id));
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $builder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        return $builder->get()->getRow();
    }

    // function add()
    // {

    //     $journalLibrary = new JournalLibrary();
    //     $financialReportLibrary = new FinancialReportLibrary();
    //     $chequeInjectionLibrary = new ChequeInjectionLibrary();
    //     $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    //     $approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
    //     $expenseAccountLibrary = new ExpenseAccountLibrary();
    //     $post = $this->request->getPost();

    //     $flag = false;
    //     $message = get_phrase('voucher_creation_failed');

    //     $header = [];
    //     $detail = [];
    //     $row = [];
    //     $office_id = $post['fk_office_id'];
    //     $voucher_number = $post['voucher_number'];
    //     $voucher_date = $post['voucher_date'];

    //     $builder = $this->write_db->table("voucher");
    //     $builder->where(array('fk_office_id' => $office_id, 
    //     'voucher_number' => $voucher_number));
    //     $voucher_obj = $builder->get();

    //     if ($voucher_obj->getNumRows() > 0) {
    //         $voucher_number = $this->getVoucherNumber($office_id);
    //     }

    //     $this->write_db->transBegin();

    //     // Check if this is the first voucher in the month, if so create a new journal record for the month
    //     // This must be run before a voucher is created
    //     if (!$this->officeHasVouchersForTheTransactingMonth($office_id, $voucher_date)) {

    //         // Create a journal record
    //         $journalLibrary->createNewJournal(date("Y-m-01", strtotime($voucher_date)), $office_id);

    //         // Insert the month MFR Record
    //         $financialReportLibrary->createFinancialReport(date("Y-m-01", strtotime($voucher_date)), $office_id);
    //     }

    //     //Retry ro Create new_journal_of_month and MFR if it was not created on the first instance when creating first voucher of the month 
    //     $journal_of_month_exists_flag = $this->getJournalForCurrentVouchingMonth(date("Y-m-01", strtotime($voucher_date)), $office_id);

    //     $finacial_report_of_month_exists_flag = $this->getFinancialReportForCurrentVouchingMonth(date("Y-m-01", strtotime($voucher_date)), $office_id);

    //     if (!$journal_of_month_exists_flag) {
    //         // Create a journal record
    //         $journalLibrary->createNewJournal(date("Y-m-01", strtotime($voucher_date)), $office_id);
    //     }

    //     if (!$finacial_report_of_month_exists_flag) {
    //         // Create financial report record
    //         $financialReportLibrary->createFinancialReport(date("Y-m-01", strtotime($voucher_date)), $office_id);
    //     }

    //     // Check voucher type

    //     $voucher_type_effect_and_account = $this->voucherTypeEffectAndCode($post['fk_voucher_type_id']);
    //     $voucher_type_effect_code = $voucher_type_effect_and_account->voucher_type_effect_code;
    //     $voucher_type_account_code = $voucher_type_effect_and_account->voucher_type_account_code;

    //     $track = $this->generateItemTrackNumberAndName('voucher');
    //     $header['voucher_track_number'] = $track['voucher_track_number'];
    //     $header['voucher_name'] = $track['voucher_name'];

    //     $header['fk_office_id'] = $office_id;
    //     $header['voucher_date'] = $voucher_date;
    //     $header['voucher_number'] = $voucher_number; 
    //     $header['fk_voucher_type_id'] = $post['fk_voucher_type_id'];

    //     $office_bank_id = $this->getOfficeBankIdToPost($office_id);
    //     $header['fk_office_bank_id'] = $office_bank_id;

    //     $header['fk_office_cash_id'] = !isset($post['fk_office_cash_id']) ? 0 : $post['fk_office_cash_id'];
    //     $voucher_cheque_number = !isset($post['voucher_cheque_number']) ? 0 : $post['voucher_cheque_number'];
    //     $header['voucher_cheque_number'] = $voucher_cheque_number;
        
    //     $chequeInjectionLibrary->updateInjectedChequeStatus($office_bank_id, $voucher_cheque_number);
    //     $chequeBookLibary = new ChequeBookLibrary();
    //     $header['fk_cheque_book_id'] = $this->isVoucherTypeChequeReferenced($header['fk_voucher_type_id']) ? $chequeBookLibary->getChequeBookIdForChequeNumber($header['voucher_cheque_number'], $header['fk_office_bank_id']) : NULL;
    //     $header['voucher_vendor'] = $post['voucher_vendor'];
    //     $header['voucher_vendor_address'] = $post['voucher_vendor_address'];
    //     $header['voucher_description'] = $post['voucher_description'];

    //     $header['voucher_created_by'] = $this->session->user_id;
    //     $header['voucher_created_date'] = date('Y-m-d');
    //     $header['voucher_last_modified_by'] = $this->session->user_id;

    //     $reverse_from_voucher_id = 0;
    //     $reverse_from_voucher_number = '';
        
    //     if(
    //         $voucher_type_effect_code == 'bank_refund' ||
    //         $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() ||
    //         $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() ||
    //         $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode()
    //     ){
    //         $reverse_from_voucher = $this->getRefundFromVoucher($office_id, $post['bank_refund']);
    //         $reverse_from_voucher_number = $reverse_from_voucher['voucher_number'];
    //         $reverse_from_voucher_id = $reverse_from_voucher['voucher_id'];

    //         if($voucher_type_effect_code == 'bank_refund') {
    //             $header['voucher_reversal_from'] = $reverse_from_voucher_id;
    //             $header['voucher_description'] = '<strike>'.$post['voucher_description'].'</strike>';
    //             $header['voucher_is_reversed'] = 1;
    //         }elseif(
    //             $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() ||
    //             $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() ||
    //             $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode()
    //         ){
    //             $title = match($voucher_type_effect_code){
    //                 VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() => VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getName(),
    //                 VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() => VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getName(),
    //                 VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode() => VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getName(),
    //             };
    //             $header['voucher_cleared_from'] = $reverse_from_voucher_id;
    //             $header['voucher_description'] = $post['voucher_description'].' ['.get_phrase('voucher_number').' '.$reverse_from_voucher['voucher_number']. ' ' .$title.']';
    //         }
    //     }else{
    //         $header['voucher_description'] = $post['voucher_description'];
    //     }

    //     $header['fk_approval_id'] = $approvalLibrary->insertApprovalRecord('voucher');
    //     $header['fk_status_id'] = $statusLibrary->initialItemStatus('voucher');

    //     $this->write_db->table('voucher')->insert( $header);

    //     $header_id = $this->write_db->insertId();

    //     if ($header['fk_cheque_book_id'] != NULL) {
    //         $builder = $this->write_db->table('cheque_book');
    //         $builder->where(array('cheque_book_id' => $header['fk_cheque_book_id']));
    //         $builder->update( ['cheque_book_is_used' => 1]);
    //     }

    //     if ($this->request->getPost('cash_recipient_account') !== null) {
    //         $this->createCashRecipientAccountRecord($header_id, $this->request->getPost());
    //     }

    //     $total_voucher_cost = 0;
    //     if (!empty($this->request->getPost('voucher_detail_quantity'))) {
    //         for ($i = 0; $i < sizeof($this->request->getPost('voucher_detail_quantity')); $i++) {
    //             $voucher_detail_quantity = str_replace(",", "", $this->request->getPost('voucher_detail_quantity')[$i]);
    //             $voucher_detail_unit_cost = str_replace(",", "", $this->request->getPost('voucher_detail_unit_cost')[$i]);
    //             $voucher_detail_total_cost = str_replace(",", "", $this->request->getPost('voucher_detail_total_cost')[$i]);

    //             $detail['fk_voucher_id'] = $header_id;
    //             $tracking = $this->generateItemTrackNumberAndName('voucher_detail');
    //             $detail['voucher_detail_track_number'] = $tracking['voucher_detail_track_number'];
    //             $detail['voucher_detail_name'] = $tracking['voucher_detail_name'];

    //             $detail['voucher_detail_quantity'] = $voucher_detail_quantity;
    //             $detail['voucher_detail_description'] = $this->request->getPost('voucher_detail_description')[$i];
    //             $detail['voucher_detail_unit_cost'] = $voucher_detail_unit_cost;
    //             $detail['voucher_detail_total_cost'] = $voucher_detail_total_cost;
    //             // log_message('error', json_encode(['voucher_type_effect_code' => $voucher_type_effect_code, 'detail' => $detail]));
    //             if (
    //                 $voucher_type_effect_code == 'expense' || 
    //                 $voucher_type_effect_code == 'bank_refund' || 
    //                 $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLES->getCode() || 
    //                 $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() || 
    //                 $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENTS->getCode() || 
    //                 $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode() || 
    //                 $voucher_type_effect_code == VoucherTypeEffectEnum::DEPRECIATION->getCode() || 
    //                 $voucher_type_effect_code == VoucherTypeEffectEnum::PAYROLL_LIABILITY->getCode()
    //                 ) {
    //                 $expense_account_id = $this->request->getPost('voucher_detail_account')[$i];
    //                 $detail['fk_expense_account_id'] = $expense_account_id;
    //                 $detail['fk_income_account_id'] = $expenseAccountLibrary->getExpenseIncomeAccountId($expense_account_id);
    //                 $detail['fk_contra_account_id'] = 0;
    //             } elseif (
    //                 $voucher_type_effect_code == 'income' || 
    //                 $voucher_type_effect_code == 'bank_to_bank_contra' ||
    //                 $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES->getCode() || 
    //                 $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode()
    //                 ) {
    //                 $detail['fk_expense_account_id'] = 0;
    //                 $detail['fk_income_account_id'] = $this->request->getPost('voucher_detail_account')[$i];
    //                 $detail['fk_contra_account_id'] = 0;
    //             } elseif ($voucher_type_effect_code == 'bank_contra' || $voucher_type_effect_code == 'cash_contra') {
    //                 $detail['fk_expense_account_id'] = 0;
    //                 $detail['fk_income_account_id'] = 0;
    //                 $detail['fk_contra_account_id'] = $this->request->getPost('voucher_detail_account')[$i];
    //             } elseif ($voucher_type_account_code == 'cash' || $voucher_type_effect_code == 'cash_contra') {
    //                 $detail['fk_expense_account_id'] = 0;
    //                 $detail['fk_income_account_id'] = 0;
    //                 $detail['fk_contra_account_id'] = $this->request->getPost('voucher_detail_account')[$i];
    //             }


    //             $detail['fk_project_allocation_id'] = isset($this->request->getPost('fk_project_allocation_id')[$i]) ? $this->request->getPost('fk_project_allocation_id')[$i] : 0;
    //             $detail['fk_request_detail_id'] = isset($this->request->getPost('fk_request_detail_id')[$i]) ? $this->request->getPost('fk_request_detail_id')[$i] : 0;
    //             $detail['fk_approval_id'] = $approvalLibrary->insertApprovalRecord('voucher_detail');
    //             $detail['fk_status_id'] = $statusLibrary->initialItemStatus('voucher_detail');

    //             // if request_id > 0 give the item the final status
    //             if (isset($this->request->getPost('fk_request_detail_id')[$i]) && $this->request->getPost('fk_request_detail_id')[$i] > 0) {
    //                 $this->updateRequestDetailStatusOnVouching($this->request->getPost('fk_request_detail_id')[$i], $header_id);
    //                 // Check if all request detail items in the request has the last status and update the request to last status too
    //                 $this->updateRequestOnPayingAllDetails($this->request->getPost('fk_request_detail_id')[$i]);
    //             }

    //             $total_voucher_cost += $voucher_detail_total_cost;

    //             $row[] = $detail;
    //         }

    //         $this->write_db->table('voucher_detail')->insertBatch( $row);
    //     }

    //     if($voucher_type_effect_code == 'bank_refund') {
    //         $this->updateReversalFromVoucher($reverse_from_voucher_id, $header_id, $reverse_from_voucher_number, $header['voucher_description'], $total_voucher_cost, 'bank_refund');
    //     }elseif(
    //         $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() ||
    //         $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() ||
    //         $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode()
    //     ){
    //         $this->updateReversalFromVoucher($reverse_from_voucher_id, $header_id, $reverse_from_voucher_number, $header['voucher_description'], $total_voucher_cost,'accrual');
    //     }
      
    //       $voucher_posting_condition = $this->voucherPostingCondition($post);

    //     if ($this->write_db->transStatus() === FALSE  || !$voucher_posting_condition) {
    //         $this->write_db->transRollback();
    //     } else {
    //         $this->write_db->transCommit();
    //         $flag = true;
    //         $message = get_phrase('voucher_creation_success');
    //     }

    //     return $this->response->setJSON(['flag' => $flag,'message' => $message]);
    // }

    function add(){
        $post = $this->request->getPost();
        $responseArr = $this->addVoucher($post);
        return $this->response->setJSON($responseArr);
    }   

    private function addVoucher($post)
    {

        $journalLibrary = new JournalLibrary();
        $financialReportLibrary = new FinancialReportLibrary();
        $chequeInjectionLibrary = new ChequeInjectionLibrary();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
        $expenseAccountLibrary = new ExpenseAccountLibrary();
        

        $flag = false;
        $message = get_phrase('voucher_creation_failed');

        $header = [];
        $detail = [];
        $row = [];
        $office_id = $post['fk_office_id'];
        $voucher_number = $post['voucher_number'];
        $voucher_date = $post['voucher_date'];

        $builder = $this->write_db->table("voucher");
        $builder->where(array('fk_office_id' => $office_id, 
        'voucher_number' => $voucher_number));
        $voucher_obj = $builder->get();

        if ($voucher_obj->getNumRows() > 0) {
            $voucher_number = $this->getVoucherNumber($office_id);
        }

        $this->write_db->transBegin();

        // Check if this is the first voucher in the month, if so create a new journal record for the month
        // This must be run before a voucher is created
        if (!$this->officeHasVouchersForTheTransactingMonth($office_id, $voucher_date)) {

            // Create a journal record
            $journalLibrary->createNewJournal(date("Y-m-01", strtotime($voucher_date)), $office_id);

            // Insert the month MFR Record
            $financialReportLibrary->createFinancialReport(date("Y-m-01", strtotime($voucher_date)), $office_id);
        }

        //Retry ro Create new_journal_of_month and MFR if it was not created on the first instance when creating first voucher of the month 
        $journal_of_month_exists_flag = $this->getJournalForCurrentVouchingMonth(date("Y-m-01", strtotime($voucher_date)), $office_id);

        $finacial_report_of_month_exists_flag = $this->getFinancialReportForCurrentVouchingMonth(date("Y-m-01", strtotime($voucher_date)), $office_id);

        if (!$journal_of_month_exists_flag) {
            // Create a journal record
            $journalLibrary->createNewJournal(date("Y-m-01", strtotime($voucher_date)), $office_id);
        }

        if (!$finacial_report_of_month_exists_flag) {
            // Create financial report record
            $financialReportLibrary->createFinancialReport(date("Y-m-01", strtotime($voucher_date)), $office_id);
        }

        // Check voucher type

        $voucher_type_effect_and_account = $this->voucherTypeEffectAndCode($post['fk_voucher_type_id']);
        $voucher_type_effect_code = $voucher_type_effect_and_account->voucher_type_effect_code;
        $voucher_type_account_code = $voucher_type_effect_and_account->voucher_type_account_code;

        $track = $this->generateItemTrackNumberAndName('voucher');
        $header['voucher_track_number'] = $track['voucher_track_number'];
        $header['voucher_name'] = $track['voucher_name'];

        $header['fk_office_id'] = $office_id;
        $header['voucher_date'] = $voucher_date;
        $header['voucher_number'] = $voucher_number; 
        $header['fk_voucher_type_id'] = $post['fk_voucher_type_id'];

        $office_bank_id = $this->getOfficeBankIdToPost($office_id);
        $header['fk_office_bank_id'] = $office_bank_id;

        $header['fk_office_cash_id'] = !isset($post['fk_office_cash_id']) ? 0 : $post['fk_office_cash_id'];
        $voucher_cheque_number = !isset($post['voucher_cheque_number']) ? 0 : $post['voucher_cheque_number'];
        $header['voucher_cheque_number'] = $voucher_cheque_number;
        
        $chequeInjectionLibrary->updateInjectedChequeStatus($office_bank_id, $voucher_cheque_number);
        $chequeBookLibary = new ChequeBookLibrary();
        $header['fk_cheque_book_id'] = $this->isVoucherTypeChequeReferenced($header['fk_voucher_type_id']) ? $chequeBookLibary->getChequeBookIdForChequeNumber($header['voucher_cheque_number'], $header['fk_office_bank_id']) : NULL;
        $header['voucher_vendor'] = $post['voucher_vendor'];
        $header['voucher_vendor_address'] = $post['voucher_vendor_address'];
        $header['voucher_description'] = $post['voucher_description'];

        $header['voucher_created_by'] = $this->session->user_id;
        $header['voucher_created_date'] = date('Y-m-d');
        $header['voucher_last_modified_by'] = $this->session->user_id;

        $reverse_from_voucher_id = 0;
        $reverse_from_voucher_number = '';
        
        if(
            $voucher_type_effect_code == 'bank_refund' ||
            $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() ||
            $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() ||
            $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode()
        ){
            $reverse_from_voucher = $this->getRefundFromVoucher($office_id, $post['bank_refund']);
            $reverse_from_voucher_number = $reverse_from_voucher['voucher_number'];
            $reverse_from_voucher_id = $reverse_from_voucher['voucher_id'];

            if($voucher_type_effect_code == 'bank_refund') {
                $header['voucher_reversal_from'] = $reverse_from_voucher_id;
                $header['voucher_description'] = '<strike>'.$post['voucher_description'].'</strike>';
                $header['voucher_is_reversed'] = 1;
            }elseif(
                $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() ||
                $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() ||
                $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode()
            ){
                $title = match($voucher_type_effect_code){
                    VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() => VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getName(),
                    VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() => VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getName(),
                    VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode() => VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getName(),
                };
                $header['voucher_cleared_from'] = $reverse_from_voucher_id;
                $header['voucher_description'] = $post['voucher_description'].' ['.get_phrase('voucher_number').' '.$reverse_from_voucher['voucher_number']. ' ' .$title.']';
            }
        }else{
            $header['voucher_description'] = $post['voucher_description'];
        }

        $header['fk_approval_id'] = $approvalLibrary->insertApprovalRecord('voucher');
        $header['fk_status_id'] = $statusLibrary->initialItemStatus('voucher');

        $this->write_db->table('voucher')->insert( $header);

        $header_id = $this->write_db->insertId();

        if ($header['fk_cheque_book_id'] != NULL) {
            $builder = $this->write_db->table('cheque_book');
            $builder->where(array('cheque_book_id' => $header['fk_cheque_book_id']));
            $builder->update( ['cheque_book_is_used' => 1]);
        }

        if (isset($post['cash_recipient_account'])) {
            $this->createCashRecipientAccountRecord($header_id, $post);
        }

        $total_voucher_cost = 0;
        if (!empty($post['voucher_detail_quantity'])) {
            for ($i = 0; $i < sizeof($post['voucher_detail_quantity']); $i++) {
                $voucher_detail_quantity = str_replace(",", "", $post['voucher_detail_quantity'][$i]);
                $voucher_detail_unit_cost = str_replace(",", "", $post['voucher_detail_unit_cost'][$i]);
                $voucher_detail_total_cost = str_replace(",", "", $post['voucher_detail_total_cost'][$i]);

                $detail['fk_voucher_id'] = $header_id;
                $tracking = $this->generateItemTrackNumberAndName('voucher_detail');
                $detail['voucher_detail_track_number'] = $tracking['voucher_detail_track_number'];
                $detail['voucher_detail_name'] = $tracking['voucher_detail_name'];

                $detail['voucher_detail_quantity'] = $voucher_detail_quantity;
                $detail['voucher_detail_description'] = $post['voucher_detail_description'][$i];
                $detail['voucher_detail_unit_cost'] = $voucher_detail_unit_cost;
                $detail['voucher_detail_total_cost'] = $voucher_detail_total_cost;
                // log_message('error', json_encode(['voucher_type_effect_code' => $voucher_type_effect_code, 'detail' => $detail]));
                if (
                    $voucher_type_effect_code == 'expense' || 
                    $voucher_type_effect_code == 'bank_refund' || 
                    $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLES->getCode() || 
                    $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() || 
                    $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENTS->getCode() || 
                    $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode() || 
                    $voucher_type_effect_code == VoucherTypeEffectEnum::DEPRECIATION->getCode() || 
                    $voucher_type_effect_code == VoucherTypeEffectEnum::PAYROLL_LIABILITY->getCode()
                    ) {
                    $expense_account_id = $post['voucher_detail_account'][$i];
                    $detail['fk_expense_account_id'] = $expense_account_id;
                    $detail['fk_income_account_id'] = $expenseAccountLibrary->getExpenseIncomeAccountId($expense_account_id);
                    $detail['fk_contra_account_id'] = 0;
                } elseif (
                    $voucher_type_effect_code == 'income' || 
                    $voucher_type_effect_code == 'bank_to_bank_contra' ||
                    $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES->getCode() || 
                    $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode()
                    ) {
                    $detail['fk_expense_account_id'] = 0;
                    $detail['fk_income_account_id'] = $post['voucher_detail_account'][$i];
                    $detail['fk_contra_account_id'] = 0;
                } elseif ($voucher_type_effect_code == 'bank_contra' || $voucher_type_effect_code == 'cash_contra') {
                    $detail['fk_expense_account_id'] = 0;
                    $detail['fk_income_account_id'] = 0;
                    $detail['fk_contra_account_id'] = $post['voucher_detail_account'][$i];
                } elseif ($voucher_type_account_code == 'cash' || $voucher_type_effect_code == 'cash_contra') {
                    $detail['fk_expense_account_id'] = 0;
                    $detail['fk_income_account_id'] = 0;
                    $detail['fk_contra_account_id'] = $post['voucher_detail_account'][$i];
                }


                $detail['fk_project_allocation_id'] = isset($post['fk_project_allocation_id'][$i]) ? $post['fk_project_allocation_id'][$i] : 0;
                $detail['fk_request_detail_id'] = isset($post['fk_request_detail_id'][$i]) ? $post['fk_request_detail_id'][$i] : 0;
                $detail['fk_approval_id'] = $approvalLibrary->insertApprovalRecord('voucher_detail');
                $detail['fk_status_id'] = $statusLibrary->initialItemStatus('voucher_detail');

                // if request_id > 0 give the item the final status
                if (isset($post['fk_request_detail_id'][$i]) && $post['fk_request_detail_id'][$i] > 0) {
                    $this->updateRequestDetailStatusOnVouching($post['fk_request_detail_id'][$i], $header_id);
                    // Check if all request detail items in the request has the last status and update the request to last status too
                    $this->updateRequestOnPayingAllDetails($post['fk_request_detail_id'][$i]);
                }

                $total_voucher_cost += $voucher_detail_total_cost;

                $row[] = $detail;
            }

            $this->write_db->table('voucher_detail')->insertBatch( $row);
        }

        if($voucher_type_effect_code == 'bank_refund') {
            $this->updateReversalFromVoucher($reverse_from_voucher_id, $header_id, $reverse_from_voucher_number, $header['voucher_description'], $total_voucher_cost, 'bank_refund');
        }elseif(
            $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() ||
            $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() ||
            $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode()
        ){
            $this->updateReversalFromVoucher($reverse_from_voucher_id, $header_id, $reverse_from_voucher_number, $header['voucher_description'], $total_voucher_cost,'accrual');
        }
      
          $voucher_posting_condition = $this->voucherPostingCondition($post);

        if ($this->write_db->transStatus() === FALSE  || !$voucher_posting_condition) {
            $this->write_db->transRollback();
        } else {
            $this->write_db->transCommit();
            $flag = true;
            $message = get_phrase('voucher_creation_success');
        }

        return ['flag' => $flag,'message' => $message];
    }

    private function voucherPostingCondition($post){
        $checkResult = true;
        $has_details = count($post['voucher_detail_quantity']) > 0 ? true : false;
        $all_details_have_accounts = $this->allDetailsHaveAccounts($post['voucher_detail_account']);
    
        if(!$has_details || !$all_details_have_accounts){
          $checkResult = false;
        }
    
        return $checkResult;
      }

      private function allDetailsHaveAccounts($detailsAccounts){
        $checkResult =  true;
    
        foreach($detailsAccounts as $detailsAccount){
          if(!$detailsAccount || $detailsAccount == 0){
            $checkResult = false;
            break;
          }
        }
        return $checkResult;
      }

    function updateReversalFromVoucher($from_id, $to_id, $voucher_number_from, $new_voucher_description, $total_voucher_cost, $settlementType = 'bank_refund'){

        $unrefunded_amount = $this->unrefundedAmountByFromVoucherId($from_id, $settlementType);
        
        // Get existing voucher_refunding_to ids
        $voucher_refunding_to_json = $this->read_db->table('voucher')->where( ['voucher_id' => $from_id])
        ->get()->getRow()->voucher_refunding_to;
        
        $voucher_refunding_to_ids = [];

        if($voucher_refunding_to_json != null){
            $voucher_refunding_to_ids = json_decode($voucher_refunding_to_json);
            array_push($voucher_refunding_to_ids, $to_id);
        }else{
            $voucher_refunding_to_ids = [$to_id];
        }

        $data['voucher_refunding_to'] = json_encode($voucher_refunding_to_ids);
        $voucherWriteBuilder = $this->write_db->table('voucher');

        if(($total_voucher_cost - $unrefunded_amount) == 0){
            if($settlementType == 'bank_refund') {
                $desc['voucher_description'] = "$new_voucher_description [Refunded to $voucher_number_from]";
      
                $data['voucher_reversal_to'] = $to_id;
                $data['voucher_is_reversed'] = 1;
            }else{
                $desc['voucher_description'] = "$new_voucher_description";
                $data['voucher_cleared_to'] = $to_id;
                $data['voucher_transaction_cleared_date'] = date('Y-m-01');
            }

            $voucherWriteBuilder->where('voucher_id', $to_id);
            $voucherWriteBuilder->update($desc);
          
        }
        
        $voucherWriteBuilder->where(['voucher_id' => $from_id]);
        $voucherWriteBuilder->update( $data);
        
      }

    function getRefundFromVoucher($office_id, $reverse_from_voucher_number){
        $voucherReadBuilder = $this->read_db->table('voucher');
        $voucherReadBuilder->select(['voucher_id', 'voucher_number']);
        $voucherReadBuilder->where(['fk_office_id' => $office_id, 'voucher_number' => $reverse_from_voucher_number]);
        $voucher_obj = $voucherReadBuilder->get();
    
        $voucher = [];
    
        if($voucher_obj->getNumRows() > 0){
          $voucher = $voucher_obj->getRowArray();
        }
    
        return $voucher;
      }

    public function officeHasVouchersForTheTransactingMonth($office_id, $transacting_month)
    {

        $month_start_date = date('Y-m-01', strtotime($transacting_month));
        $month_end_date = date('Y-m-t', strtotime($transacting_month));

        $voucher_count_for_the_month = $this->read_db->table(    'voucher')->getWhere(
            array('fk_office_id' => $office_id, 'voucher_date>=' => $month_start_date, 'voucher_date<=' => $month_end_date)
        )->getNumRows();

        $journal_count_for_the_month = $this->read_db->table( 'journal')->getWhere(
            array('fk_office_id' => $office_id, 'journal_month' => $month_start_date)
        )->getNumRows();

        $financial_report_count_for_the_month = $this->read_db->table('financial_report')->getWhere(
            array('fk_office_id' => $office_id, 'financial_report_month' => $month_start_date)
        )->getNumRows();

        $office_has_vouchers_for_the_transacting_month = false;

        if ($voucher_count_for_the_month > 0 && $journal_count_for_the_month > 0 && $financial_report_count_for_the_month > 0) {
            $office_has_vouchers_for_the_transacting_month = true;
        }

        return $office_has_vouchers_for_the_transacting_month;
    }

    public function getJournalForCurrentVouchingMonth($voucher_date, $office_id)
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

    public function getFinancialReportForCurrentVouchingMonth($voucher_date, $office_id)
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

    function getOfficeBankIdToPost($office_id)
    {
        
    $officeBankLibrary = new OfficeBankLibrary();

      $office_bank_id =  $this->request->getPost('fk_office_bank_id') == null ? 0 : $this->request->getPost('fk_office_bank_id');
  
      if ($office_bank_id == 0) {
        // Get id of active office bank
        $office_bank_id = $officeBankLibrary->getActiveOfficeBanks($office_id)[0]['office_bank_id'];
      }
  
      return $office_bank_id;
    }

     /**
   * Check if the voucher type id provided is set to be requiring a cheque number reference
   * @author Nicodemus Kairsa Mwambire nkarisa@ke.ci.org
   * @date 18th March 2024
   * @param integer voucher_type_id  - Country voucher type id
   * @return bool
   * @source master-record-cheque-id
   * @version v24.3.0.1
   */
  public function isVoucherTypeChequeReferenced($voucher_type_id): bool{
    $is_voucher_type_cheque_referenced = false;
    $builder = $this->read_db->table("voucher_type");
    $builder->where(['voucher_type_id' => $voucher_type_id, 'voucher_type_is_cheque_referenced' => 1]);
    $voucher_type_obj = $builder->get();
 
    if($voucher_type_obj->getNumRows() > 0){
      $is_voucher_type_cheque_referenced = true;
    }
 
    return $is_voucher_type_cheque_referenced;
  }

  function createCashRecipientAccountRecord($voucher_id, $post)
  {
    $approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
    $statusLibrary = new \App\Libraries\Core\StatusLibrary();

    $tracking = $this->generateItemTrackNumberAndName('cash_recipient_account');
    $cash_recipient_account_data['cash_recipient_account_name'] = $tracking['cash_recipient_account_name'];
    $cash_recipient_account_data['cash_recipient_account_track_number'] = $tracking['cash_recipient_account_track_number'];
    $cash_recipient_account_data['fk_voucher_id'] = $voucher_id;

    if (isset($post['fk_office_bank_id']) && $post['fk_office_bank_id'] > 0) {
      $cash_recipient_account_data['fk_office_bank_id'] = $post['cash_recipient_account'];
    } elseif ($post['fk_office_cash_id'] > 0) {
      $cash_recipient_account_data['fk_office_cash_id'] = $post['cash_recipient_account'];
    }

    $cash_recipient_account_data['cash_recipient_account_created_date'] = date('Y-m-d');
    $cash_recipient_account_data['cash_recipient_account_created_by'] = $this->session->user_id;
    $cash_recipient_account_data['cash_recipient_account_last_modified_by'] = $this->session->user_id;

    $cash_recipient_account_data['fk_approval_id'] = $approvalLibrary->insertApprovalRecord('cash_recipient_account');
    $cash_recipient_account_data['fk_status_id'] = $statusLibrary->initialItemStatus('cash_recipient_account');

    $this->write_db->table('cash_recipient_account')->insert( $cash_recipient_account_data);
  }

  function updateRequestDetailStatusOnVouching($request_detail_id, $voucher_id)
  {
    // Update the request detail record
    $builder = $this->write_db->table("request_detail");
    $builder->where(array('request_detail_id' => $request_detail_id));
    $builder->update(array('fk_voucher_id' => $voucher_id));
  }

  function updateRequestOnPayingAllDetails($request_detail_id)
  {
    $request_id = $this->read_db->table('request_detail')->getWhere( array('request_detail_id' => $request_detail_id))->getRow()->fk_request_id;
    $unpaid_request_details = $this->read_db->table('request_detail')->getWhere( array('fk_request_id' => $request_id, 'fk_voucher_id' => 0))->getNumRows();

    if ($unpaid_request_details == 0) {
        $builder = $this->write_db->table("request");
        $builder->where(array('request_id' => $request_id));
        $builder->update( array('request_is_fully_vouched' => 1));
    }
  }

  function additionalListColumns(): array {
    $additional = [
        'action' => 'voucher_id'
    ];

    return $additional;
  }

  /**
     * month_cancelled_vouchers
     * @param string $first_voucher_date
     * @return mixed [Object or Array]
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function monthCancelledVouchers($first_voucher_date)
    {

        $start_month_date = date('Y-m-01', strtotime($first_voucher_date));
        $end_month_date = date('Y-m-t', strtotime($first_voucher_date));

        $voucherReadBuilder = $this->read_db->table('voucher');

        $voucherReadBuilder->select(array('voucher_id'));
        $voucherReadBuilder->where(array('voucher_is_reversed' => 1, 'voucher_reversal_to > ' => 0));
        $voucherReadBuilder->where(array('voucher_date >=' => $start_month_date, 'voucher_date <= ' => $end_month_date));

        if (!$this->session->system_admin) {
            $voucherReadBuilder->whereIn('voucher.fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        }

        $month_cancelled_vouchers = $voucherReadBuilder->get();

        $vouchers_ids = [];

        if ($month_cancelled_vouchers->getNumRows() > 0) {
            $vouchers_ids = array_column($month_cancelled_vouchers->getResultArray(), 'voucher_id');
        }

        return $vouchers_ids;
    }

    public function getAttachments($approve_item_id, $record_id){


        $awsAttachmentLibrary = new \App\Libraries\System\AwsAttachmentLibrary();
        $attachment_where_condition_array['fk_approve_item_id'] = $approve_item_id;
        $attachment_where_condition_array['attachment_primary_id'] = $record_id;
      
        $attachments = $awsAttachmentLibrary->retrieveFileUploadsInfo($attachment_where_condition_array);

        
        $attachmentLibrary = new \App\Libraries\Core\AttachmentLibrary();


        for($i = 0; $i < sizeof($attachments); $i++){
          $objectKey = $attachments[$i]['attachment_url'].'/'.$attachments[$i]['attachment_name'];
          $attachments[$i]['attachment_url'] = $this->config->upload_files_to_s3 ? $awsAttachmentLibrary->s3PreassignedUrl($objectKey) : $attachmentLibrary->getLocalFilesystemAttachmentUrl($objectKey);
        }
    
       
        return $attachments;
      }

    function formatColumnsValuesDependancyData(array $vouchers): array
    {
        $month_cancelled_vouchers = [];
        $voucher_attachments_required = false;
        $accountSystemSettingLibrary = new \App\Libraries\Core\AccountSystemSettingLibrary();

        if(count($vouchers)){
            $month_cancelled_vouchers = $this->monthCancelledVouchers($vouchers[0]['voucher_date']);
            //$account_system_settings = $accountSystemSettingLibrary->getAccountSystemSettings($vouchers[0]['fk_account_system_id']);

            $account_system_settings = $accountSystemSettingLibrary->getAccountSystemSettings($this->session->get('user_account_system_id'));
            
            
            if (
                array_key_exists('voucher_attachments_required', $account_system_settings)
                && $account_system_settings['voucher_attachments_required'] == 1
            ) {
                $voucher_attachments_required = true;
            }

            //log_message('error', json_encode( $voucher_attachments_required));
        }

        $approve_item_id = $this->read_db->table('approve_item')->where(['approve_item_name' => 'voucher'])
        ->get()->getRow()->approve_item_id;

        $userLibrary = new \App\Libraries\Core\UserLibrary();

        $user_has_voucher_update_permission = $userLibrary->checkRoleHasPermissions(ucfirst('voucher'), 'update');
        
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $initial_record_status_id = $statusLibrary->initialItemStatus('voucher');

        return compact('initial_record_status_id','month_cancelled_vouchers', 'voucher_attachments_required','approve_item_id','user_has_voucher_update_permission');
    }

    function formatColumnsValues(string $columnsName, mixed $columnsValues, array $rowData, array $dependancyData = []): mixed {
    
        if($columnsName == 'action'){
            $this->attachmentColumnValue($columnsValues, $rowData, $dependancyData);
        }
    
        return $columnsValues;
    }

    function addTrackNumberStyle(array $rowData, array $dependancyData = []): string{
        $style = "";
        
        if(trim($rowData['voucher_type_name']) == 'Voided Cheque'){
            $style = "style='color:orange;'";
        }

        return $style;
    }

    private function attachmentColumnValue(&$columnsValues, $rowData, $dependancyData)
    {
        $is_voided_chq = false;
        $accountSystemSettingLibrary = new \App\Libraries\Core\AccountSystemSettingLibrary();
        $initial_record_status_id = $dependancyData['initial_record_status_id'];
        $month_cancelled_vouchers = $dependancyData['month_cancelled_vouchers'];
        $voucher_attachments_required = $dependancyData['voucher_attachments_required'];
        $approve_item_id = $dependancyData['approve_item_id'];
        $user_has_voucher_update_permission = $dependancyData['user_has_voucher_update_permission'];
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $office_account_system_id = $officeLibrary->getOfficeAccountSystem($rowData['office_id'])['account_system_id'];
        $account_system_settings = $accountSystemSettingLibrary->getAccountSystemSettings($office_account_system_id);
        $voucher_attachments_required = false;

        if (
            array_key_exists('voucher_attachments_required', $account_system_settings) &&
            $account_system_settings['voucher_attachments_required'] == 1
        ) {
            $voucher_attachments_required = true;
        }

        $voucher_attachments = $this->getAttachments($approve_item_id, $rowData['voucher_id']);
        $count_of_attachments = count($voucher_attachments);
        $btn_color = $count_of_attachments == 0 ? 'btn-danger' : 'btn-success';
        $btn_label = $count_of_attachments == 0 ? get_phrase('attach_documents', 'Attach Support Documents') : get_phrase('show_documents', 'Show Support Documents');
        $disable_approval_button = $count_of_attachments == 0 && $voucher_attachments_required ? true : false;

        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $office_account_system_id = $officeLibrary->getOfficeAccountSystem($rowData['office_id'])['account_system_id'];
        $status_data = $this->actionButtonData($this->controller, $office_account_system_id);
        $status_info = $status_data['item_status'];
        extract($status_data);
        $voucher_status = $rowData['fk_status_id'];
        $status_approval_direction = function () use ($status_info, $voucher_status, $initial_record_status_id) {
            if (isset($status_info[$voucher_status])) {
                return isset($status_info[$voucher_status]['status_approval_direction']) ? $status_info[$voucher_status]['status_approval_direction'] : $status_info[$initial_record_status_id]['status_approval_direction'];
            } else {
                return 0;
            }
        };

        $can_delete_attachment = ($rowData['fk_status_id'] == $item_initial_item_status_id || $status_approval_direction == '-1') && $user_has_voucher_update_permission ? true : false;

        if (is_array($month_cancelled_vouchers) && !in_array($rowData['voucher_id'], $month_cancelled_vouchers)) {
            if ($voucher_attachments_required) {
                $columnsValues .= '<div id="dt-control-' . $rowData['voucher_id'] . '"  data-can_delete_attachment="' . $can_delete_attachment . '" data-voucher_id="' . $rowData['voucher_id'] . '" class = "btn ' . $btn_color . ' dt-control" >' . $btn_label . '</div> ';
            }
            $columnsValues .= approval_action_button($this->controller, $item_status, $rowData['voucher_id'], $rowData['status_id'], $item_initial_item_status_id, $item_max_approval_status_ids, $disable_approval_button, true, '', $is_voided_chq);
        }
    }

    public function monthFundsTransferVouchers($office_ids, $reporting_month)
  {

      $voucher_type_ids = $this->voucherTypeLibrary->officeHiddenBankVoucherTypes($office_ids[0]);

      $vouchers = [];

      // log_message('error', json_encode($voucher_type_ids));

      if (count($voucher_type_ids) > 0) {
        $builder = $this->read_db->table('voucher');
          $builder->select(
              [
                  'voucher_date',
                  'voucher_number',
                  'voucher_id',
                  'funds_transfer_source_account_id',
                  'funds_transfer_target_account_id',
                  'funds_transfer_amount',
                  'funds_transfer_id',
                  'funds_transfer_created_date',
                  'funds_transfer_type',
                  'voucher_created_date',
              ]
          );
          $builder->where(array('voucher_date>=' => date('Y-m-01', strtotime($reporting_month)), 'voucher_date<=' => date('Y-m-t', strtotime($reporting_month))));
          $builder->whereIn('fk_voucher_type_id', $voucher_type_ids);
          $builder->join('funds_transfer', 'funds_transfer.fk_voucher_id=voucher.voucher_id');
          $builder->whereIn('voucher.fk_office_id', $office_ids);
          $vouchers_obj = $builder->get();

          if ($vouchers_obj->getNumRows() > 0) {
              $unformatted_accounts_vouchers = $vouchers_obj->getResultArray();

              $vouchers = $this->formatAccountsNumbers($unformatted_accounts_vouchers);
          }
      }
      // log_message('error', json_encode($reporting_month));
      return $vouchers;
  }

  public function formatAccountsNumbers($vouchers)
    {

        $income_accounts = [];
        $expense_accounts = [];
        // $accounts = [];

        $cnt = 0;
        foreach ($vouchers as $voucher) {
            if ($voucher['funds_transfer_type'] == 1) {
                $income_accounts[$voucher['funds_transfer_source_account_id']] = $voucher['funds_transfer_source_account_id'];
                $income_accounts[$voucher['funds_transfer_target_account_id']] = $voucher['funds_transfer_target_account_id'];
            } else {
                $expense_accounts[$voucher['funds_transfer_source_account_id']] = $voucher['funds_transfer_source_account_id'];
                $expense_accounts[$voucher['funds_transfer_target_account_id']] = $voucher['funds_transfer_target_account_id'];
            }
            $cnt++;
        }

        // log_message('error',json_encode($income_accounts));
        // log_message('error',json_encode($expense_accounts));

        $builder = $this->read_db->table('expense_account');
        if (!empty($income_accounts)) {
            //$builder = $this->read_db->table('expense_account');
            $builder->select(array('income_account_id', 'income_account_name'));
            $builder->whereIn('income_account_id', $income_accounts);
            $income_accounts = $builder->get('income_account')->getResultArray();

            $income_account_ids = array_column($income_accounts, 'income_account_id');
            $income_account_names = array_column($income_accounts, 'income_account_name');

            $income_accounts = array_combine($income_account_ids, $income_account_names);
        }

        if (!empty($expense_accounts)) {
            $builder->select(array('expense_account_id', 'expense_account_name'));
            $builder->whereIn('expense_account_id', $expense_accounts);
            $expense_accounts = $builder->get()->getResultArray();

            $expense_account_ids = array_column($expense_accounts, 'expense_account_id');
            $expense_account_names = array_column($expense_accounts, 'expense_account_name');

            $expense_accounts = array_combine($expense_account_ids, $expense_account_names);
        }

        for ($i = 0; $i < sizeof($vouchers); $i++) {
            if ($vouchers[$i]['funds_transfer_type'] == 1) {
                $vouchers[$i]['funds_transfer_source_account_id'] = $income_accounts[$vouchers[$i]['funds_transfer_source_account_id']];
                $vouchers[$i]['funds_transfer_target_account_id'] = $income_accounts[$vouchers[$i]['funds_transfer_target_account_id']];
            } else {
                $vouchers[$i]['funds_transfer_source_account_id'] = $expense_accounts[$vouchers[$i]['funds_transfer_source_account_id']];
                $vouchers[$i]['funds_transfer_target_account_id'] = $expense_accounts[$vouchers[$i]['funds_transfer_target_account_id']];
            }
        }

        return $vouchers;
    }

    public function checkIfMonthVouchersAreApproved($office_id, $month)
    {
 
        $start_month_date = date('Y-m-01', strtotime($month));
        $end_month_date = date('Y-m-t', strtotime($month));
 
        $approved_vouchers = count($this->journalLibrary->journalRecords($office_id, $month));
 
        //return $approved_vouchers;
        $builder = $this->read_db->table('voucher');
        $count_of_month_raised_vouchers = $builder->getWhere(
            array(
                'fk_office_id' => $office_id,
                'voucher_date>=' => $start_month_date,
                'voucher_date<=' => $end_month_date,
            )
        )->getNumRows();
 
        return ($approved_vouchers == $count_of_month_raised_vouchers) && $count_of_month_raised_vouchers > 0 ? true : false;
    }

    /**
     * Modified:documentation
     * get_office_voucher_date
     * @param int $office_id
     * @return array
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function getOfficeVoucherDate($office_id)
    {
        
        $next_vouching_date = $this->getVoucherDate($office_id);
        $last_vouching_month_date = date('Y-m-t', strtotime($next_vouching_date));

        $voucher_date_field_dates = ['next_vouching_date' => $next_vouching_date, 'last_vouching_month_date' => $last_vouching_month_date];

        return $voucher_date_field_dates;
    }

    public function createVoucher($data)
    {

        $voucher_id = 0;

        extract($data);

        $this->write_db->transStart();

        $this->write_db->table('voucher')->insert( $header);

        $header_id = $this->write_db->insertId();

        for ($i = 0; $i < sizeof($detail); $i++) {
            $detail[$i]['fk_voucher_id'] = $header_id;
        }

        $this->write_db->table('voucher_detail')->insertBatch($detail);

        $this->write_db->transComplete();

        if ($this->write_db->transStatus() != false) {
            $voucher_id = $header_id;
        }

        return $voucher_id;
    }

        /**
     * Modified:documentation
     * create_report_and_journal
     * @param int $office_id, $last_vouching_month_date
     * @return void
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function createReportAndJournal($office_id, $last_vouching_month_date):void
    {
        $office_has_this_month_voucher=$this->officeHasVouchersForTheTransactingMonth($office_id, $last_vouching_month_date);

        if (!$office_has_this_month_voucher) {

            // Create a month journal record
            $this->createNewJournal($office_id, date("Y-m-01", strtotime($last_vouching_month_date)));

            // Create the month MFR Record
            $this->createFinancialReport($office_id, date("Y-m-01", strtotime($last_vouching_month_date)));
        }
    }

      /**
     * Modified:documentation
     * create_new_journal
     * @param int $office_id, $journal_date
     * @return void
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function createNewJournal($office_id, $journal_date):void
    {
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $new_journal = [];

       // Check if a journal for the same month and FCP exists

       $cash_journal_exists=$this->monthJournalExists($office_id, $journal_date);

        if ($cash_journal_exists) {
            $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('journal');
            $new_journal['journal_track_number'] = $itemTrackNumberAndName['journal_track_number'];
            $new_journal['journal_name'] = "Journal for the month of " . $journal_date;
            $new_journal['journal_month'] = $journal_date;
            $new_journal['fk_office_id'] = $office_id;
            $new_journal['journal_created_date'] = date('Y-m-d');
            $new_journal['journal_created_by'] = $this->session->user_id;
            $new_journal['journal_last_modified_by'] = $this->session->user_id;
            // $new_journal['fk_approval_id'] = $this->grants_model->insert_approval_record('journal');
            $new_journal['fk_status_id'] = $statusLibrary->initialItemStatus('journal');

            $this->write_db->table('journal')->insert($new_journal);
        }
    }

    /**
     * Modified:documentation
     * create_financial_report
     * @param int $office_id, $financial_report_date
     * @return void
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function createFinancialReport($office_id, $financial_report_date)
    {
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        // Check if a journal for the same month and FCP exists
       
        $month_mfr_exists=$this->monthMfrExists($office_id, $financial_report_date);

        if ( $month_mfr_exists) {
            $new_mfr['financial_report_month'] = $financial_report_date;
            $new_mfr['fk_office_id'] = $office_id;
            $new_mfr['fk_status_id'] = $statusLibrary->initialItemStatus('financial_report');

            $new_mfr_to_insert = $this->mergeWithHistoryFields('financial_report', $new_mfr);

            $this->write_db->table('financial_report')->insert( $new_mfr_to_insert);
        }
    }


      /**
     * month_journal_exists
     * @param int $office_id, string $journal_date
     * @return bool
     * @access private
     * @author: Livingstone Onduso
     * @Date: 19/09/2024
     */
    private function monthJournalExists($office_id,$journal_date):bool{

        //for this case use $this->write_db connection and not $this->read_db
        $builder = $this->write_db->table('journal');
        $builder->where(array('fk_office_id' => $office_id, 'journal_month' => $journal_date));
        $cash_journal_count=$builder->get()->getNumRows();

        return $cash_journal_count===0?true:false;
    }
   /**
     * month_mfr_exists
     * @param int $office_id, string $financial_report_date
     * @return bool
     * @access private
     * @author: Livingstone Onduso
     * @Date: 19/09/2024
     */
    private function monthMfrExists($office_id,$financial_report_date):bool{
       
        //for this case use $this->write_db connection and not $this->read_db
        $builder = $this->write_db->table('financial_report');
        $builder->where(array('fk_office_id' => $office_id, 'financial_report_month' => $financial_report_date));

        $count_financial_report = $builder->get()->getNumRows();

        return $count_financial_report===0?true:false;
    }

        /**
     * Duplicated in the journal model - To be removed from here in the later versions
     * Update: This method differs in implementation as used in the journal controller. It not found in the journal model as indicated.
     * The use in the funds transfer model differ to some extent with the use in the journal countroller.
     * The teo methods need to be reviewed and refactored or rename one if the need is different.
     */

     function reverseVoucher($voucher_id, $reuse_cheque = 1)
     {
 
         $message = get_phrase("reversal_completed");
 
         // Get the voucher and voucher details
         $voucher = $this->read_db->table('voucher')->where(
             array('voucher_id' => $voucher_id)
         )->get()->getRowArray();
 
         $this->write_db->transStart();
 
         $new_voucher_id = $this->insertVoucherReversalRecord($voucher, $reuse_cheque);
 
         $this->updateCashRecipientAccount($new_voucher_id, $voucher);
 
         $this->write_db->transComplete();
 
         if ($this->write_db->transStatus() == false) {
             $message = get_phrase("reversal_failed");
         }
         // log_message('error', $message);
         return $message;
     }

     public function insertVoucherReversalRecord($voucher, $reuse_cheque, $journal_month = '')
    {

        //Unset the primary key field
        $voucher_id = array_shift($voucher);

        $voucher_details = $this->read_db->table('voucher_detail')->where(
            array('fk_voucher_id' => $voucher_id)
        )->get()->getResultArray();

        //log_message('error', json_encode(['Test' => $journal_month]));
        // Get next voucher number
        $next_voucher_number = $this->getVoucherNumber($voucher['fk_office_id'], $journal_month);
        $next_voucher_date = $this->getVoucherDate($voucher['fk_office_id'], $journal_month);

        // Replace the voucher number in selected voucher with the next voucher number
        $cleared_date = $voucher['voucher_transaction_cleared_date'];
        $cleared_month = $voucher['voucher_transaction_cleared_month'];
        $voucher_description = '<strike>' . $voucher['voucher_description'] . '</strike> [Reversal of voucher number ' . $voucher['voucher_number'] . ']';
        $voucher_transaction_cleared_date = $cleared_date == '0000-00-00' || $cleared_date == null ? null : $voucher['voucher_transaction_cleared_date'];
        $voucher_transaction_cleared_month = $cleared_month == '0000-00-00' || $cleared_month == null ? null : $voucher['voucher_transaction_cleared_month'];

        // $chequeNumberIsValidNumber = is_int((int)$voucher['voucher_cheque_number']) && $voucher['voucher_cheque_number'] > 0;
    
        // $voucher = array_replace($voucher, ['voucher_vendor' => '<strike>' . $voucher['voucher_vendor'] . '<strike>', 'voucher_is_reversed' => 1, 'voucher_reversal_from' => $voucher_id, 'voucher_cleared' => 1, 'voucher_date' => $next_voucher_date, 'voucher_cleared_month' => date('Y-m-t', strtotime($next_voucher_date)), 'voucher_number' => $next_voucher_number, 'voucher_description' => $voucher_description, 'voucher_transaction_cleared_date' => $voucher_transaction_cleared_date, 'voucher_transaction_cleared_month' => $voucher_transaction_cleared_month, 'voucher_cheque_number' => is_int($voucher['voucher_cheque_number']) && $voucher['voucher_cheque_number'] > 0 ? -$voucher['voucher_cheque_number'] : $voucher['voucher_cheque_number']]);
        $voucher = array_replace($voucher, ['voucher_vendor' => '<strike>' . $voucher['voucher_vendor'] . '<strike>', 'voucher_is_reversed' => 1, 'voucher_reversal_from' => $voucher_id, 'voucher_cleared' => 1, 'voucher_date' => $next_voucher_date, 'voucher_cleared_month' => date('Y-m-t', strtotime($next_voucher_date)), 'voucher_number' => $next_voucher_number, 'voucher_description' => $voucher_description, 'voucher_transaction_cleared_date' => $voucher_transaction_cleared_date, 'voucher_transaction_cleared_month' => $voucher_transaction_cleared_month, 'voucher_cheque_number' => $voucher['voucher_cheque_number'] != 0 && $voucher['voucher_cheque_number'] != NULL  ? "-".$voucher['voucher_cheque_number'] : $voucher['voucher_cheque_number']]);
        
        //Insert the next voucher record and get the insert id
        $this->write_db->table('voucher')->insert( $voucher);

        $new_voucher_id = $this->write_db->insertId();

        // Update details array and insert

        $updated_voucher_details = [];

        foreach ($voucher_details as $voucher_detail) {
            unset($voucher_detail['voucher_detail_id']);
            $updated_voucher_details[] = array_replace($voucher_detail, ['fk_voucher_id' => $new_voucher_id, 'voucher_detail_unit_cost' => -$voucher_detail['voucher_detail_unit_cost'], 'voucher_detail_total_cost' => -$voucher_detail['voucher_detail_total_cost']]);
        }

        $this->write_db->table('voucher_detail')->insertBatch( $updated_voucher_details);

        // Update the original voucher record by flagging it reversed
        $voucherWriteBuilder = $this->write_db->table('voucher');
        $voucherWriteBuilder->where(array('voucher_id' => $voucher_id));
        $update_data['voucher_is_reversed'] = 1;
        $update_data['voucher_cleared'] = 1;
        $update_data['voucher_cleared_month'] = date('Y-m-t', strtotime($next_voucher_date));
        //This was commented to remove the BUG DE3458 "Where the cancel function of a voucher with chq# was settin the for re-use"
        // $update_data['voucher_cheque_number'] = $voucher['voucher_cheque_number'] > 0 ? -$voucher['voucher_cheque_number'] : $voucher['voucher_cheque_number'];
        $update_data['voucher_reversal_to'] = $new_voucher_id;
        $voucherWriteBuilder->update($update_data);

        return ['new_voucher_id' => $new_voucher_id, 'new_voucher' => $voucher, 'next_voucher_number' => $next_voucher_number];
    }

     public function updateCashRecipientAccount($new_voucher_id, $voucher)
    {

        $voucher_id = array_shift($voucher);
        // Insert a cash_recipient_account record if reversing voucher is bank to bank contra

        $voucherTypeEffectBuilder = $this->read_db->table('voucher_type_effect');
        $voucherTypeEffectBuilder->where(array('voucher_type_id' => $voucher['fk_voucher_type_id']));
        $voucherTypeEffectBuilder->join('voucher_type', 'voucher_type.fk_voucher_type_effect_id=voucher_type_effect.voucher_type_effect_id');
        $voucher_type_effect_code = $voucherTypeEffectBuilder->get()->getRow()->voucher_type_effect_code;

        if ($voucher_type_effect_code == 'bank_to_bank_contra') {

            $cashRecipientAccountReadBuilder = $this->read_db->table('cash_recipient_account');
            $cashRecipientAccountReadBuilder->where(array('fk_voucher_id' => $voucher_id));
            $original_cash_recipient_account = $cashRecipientAccountReadBuilder->get()->getRowArray();

            $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('cash_recipient_account');
            $cash_recipient_account_data['cash_recipient_account_name'] = $itemTrackNumberAndName['cash_recipient_account_name'];
            $cash_recipient_account_data['cash_recipient_account_track_number'] = $itemTrackNumberAndName['cash_recipient_account_track_number'];
            $cash_recipient_account_data['fk_voucher_id'] = $new_voucher_id;

            if ($voucher['fk_office_bank_id'] > 0) {
                $cash_recipient_account_data['fk_office_bank_id'] = $original_cash_recipient_account['fk_office_bank_id'];
            } elseif ($voucher['fk_office_cash_id'] > 0) {
                $cash_recipient_account_data['fk_office_cash_id'] = $original_cash_recipient_account['fk_office_cash_id'];
            }

            $cash_recipient_account_data['cash_recipient_account_created_date'] = date('Y-m-d');
            $cash_recipient_account_data['cash_recipient_account_created_by'] = $this->session->user_id;
            $cash_recipient_account_data['cash_recipient_account_last_modified_by'] = $this->session->user_id;

            $statusLibrary = new \App\Libraries\Core\StatusLibrary();
            $cash_recipient_account_data['fk_approval_id'] = $this->insertApprovalRecord('cash_recipient_account');
            $cash_recipient_account_data['fk_status_id'] = $statusLibrary->initialItemStatus('cash_recipient_account');

            $this->write_db->table('cash_recipient_account')->insert( $cash_recipient_account_data);
        }
    }

    function showListEditActionDependancyData(array $vouchers): array{
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $maxApprovalStatus = $statusLibrary->getMaxApprovalStatusId($this->controller);
        $initialItemStatus = $this->initialItemStatus('voucher');

        $reversedVouchers = array_filter($vouchers, function($voucher){
            if($voucher['voucher_is_reversed'] == 1){
                return $voucher;
            }
        });

        $reversedVouchersIds = array_column($reversedVouchers, 'voucher_id');

        $isVoucherReversed = false; //$vouchers['voucher_is_reversed'] ? true : false;
        return compact('vouchers','maxApprovalStatus','initialItemStatus','reversedVouchersIds');
    }

    function showListEditAction(array $row, array $dependancyData = []): bool{
        $current_status_id = $row['status_id'];
        $max_approval_status = $dependancyData['maxApprovalStatus'];
        $initialItemStatus = $dependancyData['initialItemStatus'];
        $reversedVouchersIds = $dependancyData['reversedVouchersIds'];

        if(
            in_array($current_status_id, $max_approval_status) || 
            in_array($row['voucher_id'], $reversedVouchersIds) || 
            $initialItemStatus != $current_status_id){
            return false;
        }

        return true;
    }

     /**
     * post_approval_action_event
     * Created By: Nicodemus Karisa
     * Date: 31st May 2024
     */

     public function postApprovalActionEvent(array $item): void
     {
         // Force the original voucher to take the next approval status of the reversal voucher except for partial reversal i.e. Bnak Refunds
         $voucher_id = $item['post']['item_id'];
         
         // Builders
         $voucherWriteBuilder = $this->write_db->table('voucher');
         $voucherReadBuilder = $this->read_db->table('voucher');

         // Check if the voucher reversal is a bank refund

         $voucherReadBuilder->where(['voucher_type_effect_code' => 'bank_refund', 'voucher_id' => $voucher_id]);
         $voucherReadBuilder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
         $voucherReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
         $bank_refund_reversal_type_obj = $voucherReadBuilder->get();
 
         $is_reversal_type_bank_refund = false;
 
         if($bank_refund_reversal_type_obj->getNumRows() > 0){
             $is_reversal_type_bank_refund = true;
         }
 
         if(!$is_reversal_type_bank_refund){
             $voucherReadBuilder->where(array('voucher_id' => $voucher_id));
             $voucherReadBuilder->orWhere(array('voucher_reversal_to' => $voucher_id));
             $voucher_reversals_obj = $voucherReadBuilder->get();
     
                 if($voucher_reversals_obj->getNumRows() > 1){
                     // This is a reversal voucher
                     $result = [];
                     $voucher_reversals = $voucher_reversals_obj->getResultArray();
                     foreach($voucher_reversals as $voucher_reversal){
                         if($voucher_reversal['voucher_reversal_to'] > 0){
                             $result['original'] = $voucher_reversal;
                         }
                     }
                     
                     
                     $voucherWriteBuilder->where(array('voucher_id' => $result['original']['voucher_id']));
                     $voucherWriteBuilder->update(['fk_status_id' => $item['post']['next_status']]);
                 }
         }else{
            $statusLibrary = new \App\Libraries\Core\StatusLibrary();
             $max_voucher_approval_status_ids = $statusLibrary->getMaxApprovalStatusId('voucher');
 
             $voucher_date = $bank_refund_reversal_type_obj->getRow()->voucher_date;
 
             if(in_array($item['post']['next_status'], $max_voucher_approval_status_ids)){
                 $update_data['voucher_cleared'] = 1;
                 $update_data['voucher_cleared_month'] = date('Y-m-t', strtotime($voucher_date));
 
                 $voucherWriteBuilder->where(['voucher_id' => $voucher_id]);
                 $voucherWriteBuilder->update($update_data);
             }
         }
     }

     /**
     *is_voucher_missing_voucher_details(): Returns a bool [false means voucher has voucher details and true=missing voucher details]
     * @author Livingstone Onduso: Dated 08-05-2023
     * @access public
     * @param int $voucher_id
     * @return bool - returns bool
     */

    public function isVoucherMissingVoucherDetails(int $voucher_id): bool
    {
        //If no record of passed voucher_id means detail deleted
        $voucherDetailReadBuilder = $this->read_db->table('voucher_detail');
        $voucherDetailReadBuilder->select(['fk_voucher_id']);
        $voucherDetailReadBuilder->where(['fk_voucher_id' => $voucher_id]);
        $vouchers_with_voucher_detail = $voucherDetailReadBuilder->get()->getResultArray();

        if (empty($vouchers_with_voucher_detail)) {
            return true;
        }

        return false;
    }
    /**
     *get_voucher_detail_to_edit(): Returns a rows of voucher details information from voucher_detail table
     * @author Livingstone Onduso: Dated 08-05-2023
     * @access public
     * @param Int $voucher_id - voucher id String voucher_effect_name
     * @return array - returns array
     */
    public function getVoucherDetailToEdit(int $voucher_id, string $voucher_type_effect_code): array
    {   
        $voucherDetailReadBuilder = $this->read_db->table('voucher_detail');
        $voucherDetailReadBuilder->select(['voucher_detail_id', 'voucher_detail_quantity', 'voucher_detail_unit_cost', 'voucher_detail_total_cost', 'voucher_detail_description', 'fk_project_allocation_id', 'project_name', 'project_id']);
        
        //Check if contra or expense. Always transaction will account_id so no need to check if income
        
        if (
                $voucher_type_effect_code == VoucherTypeEffectEnum::EXPENSE->value || 
                $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLES->value ||
                $voucher_type_effect_code == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->value  || 
                $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENTS->value ||
                $voucher_type_effect_code == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->value ||
                $voucher_type_effect_code == VoucherTypeEffectEnum::DEPRECIATION->value ||
                $voucher_type_effect_code == VoucherTypeEffectEnum::PAYROLL_LIABILITY->value 
            ) {
            $voucherDetailReadBuilder->select(['fk_expense_account_id', 'expense_account_name', 'expense_account.fk_income_account_id', 'income_account_name']);
            $voucherDetailReadBuilder->join('expense_account', 'expense_account.expense_account_id=voucher_detail.fk_expense_account_id','left');
            $voucherDetailReadBuilder->join('income_account', 'income_account.income_account_id=voucher_detail.fk_income_account_id','left');
        } elseif ($voucher_type_effect_code == VoucherTypeEffectEnum::CASH_CONTRA->value || $voucher_type_effect_code == VoucherTypeEffectEnum::BANK_CONTRA->value) {
            $voucherDetailReadBuilder->select(['contra_account_name']);
            $voucherDetailReadBuilder->join('contra_account', 'contra_account.contra_account_id=voucher_detail.fk_contra_account_id');
        } elseif (
                $voucher_type_effect_code == VoucherTypeEffectEnum::INCOME->value || 
                $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES->value ||
                $voucher_type_effect_code == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->value 
            ) {
            $voucherDetailReadBuilder->select(array('income_account_name', 'fk_income_account_id'));
            $voucherDetailReadBuilder->join('income_account', 'income_account.income_account_id=voucher_detail.fk_income_account_id','left');
        }

        $voucherDetailReadBuilder->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
        $voucherDetailReadBuilder->join('project', 'project.project_id=project_allocation.fk_project_id');
        $voucherDetailReadBuilder->where(['fk_voucher_id' => $voucher_id]);
        $voucher_detail_to_edit = $voucherDetailReadBuilder->get()->getResultArray();

        $voucher_total_amount = 0;
        foreach ($voucher_detail_to_edit as $voucher_detail) {
            $voucher_total_amount += $voucher_detail['voucher_detail_total_cost'];
        }

        $amount['total_voucher_amount'] = $voucher_total_amount;

        array_push($voucher_detail_to_edit, $amount);
        // log_message('error', json_encode($voucher_detail_to_edit));
        return $voucher_detail_to_edit;
    }

        /**
     * get_active_project_expenses_accounts
     * @date: 13 Nov 2023
     *
     * @return Array
     * @author Onduso
     */
    public function getActiveProjectExpensesAccounts(int $project_id, int $voucher_type_id = 0): array
    {
        //Get the voucher_type
        $voucher_type_effect = $this->getVoucherTypeEffect($voucher_type_id)['voucher_type_effect_code'];

        //Get incomes for a given project then loop to regroup them
        $projectIncomeAccountReadBuilder = $this->read_db->table('project_income_account');
        $projectIncomeAccountReadBuilder->select(['fk_income_account_id']);
        $projectIncomeAccountReadBuilder->where(['fk_project_id' => $project_id]);
        $project_income_account_ids = $projectIncomeAccountReadBuilder->get()->getResultArray();

        $income_ids = [];
        $accounts_ids_and_names = [];

        foreach ($project_income_account_ids as $project_income_account_id) {
            $income_ids[] = $project_income_account_id['fk_income_account_id'];
        }
        

        //if voucher_type=income get the income names and codes
        if ($voucher_type_effect == 'income') {
            $accounts_ids_and_names = $this->getAccountsIdsAndName('income_account', 'income_account_id', $income_ids); //array_combine($income_acc_ids,$income_acc_names);

        } else if ($voucher_type_effect == 'expense') {

            // //Get the expenses for each of the income_accounts
            $accounts_ids_and_names = $this->getAccountsIdsAndName('expense_account', 'fk_income_account_id', $income_ids, true);
        } else if ($voucher_type_effect == 'bank_to_bank_contra') {

            //what of contra
            $accounts_ids_and_names = $this->getAccountsIdsAndName('contra_account', 'contra_account_id', $income_ids);
        }elseif(AccrualVoucherTypeEffects::tryFrom($voucher_type_effect)){
            $accounts_ids_and_names = match($voucher_type_effect){
                AccrualVoucherTypeEffects::RECEIVABLES->value => $this->getIncomeAccountsByIds($income_ids),
            };
        }

        return $accounts_ids_and_names;
    }

    private function getIncomeAccountsByIds($income_ids){
        $incomeAccountReadBuilder = $this->read_db->table('income_account');

        $incomeAccountReadBuilder->select(['income_account_id', 'income_account_name']);
        $incomeAccountReadBuilder->whereIn('income_account_id', $income_ids);
        $income_account_obj = $incomeAccountReadBuilder->get();

        $income_account = [];

        if($income_account_obj->getNumRows() > 0){
            $result = $income_account_obj->getResultArray();

            $ids = array_column($result, 'income_account_id');
            $names = array_column($result, 'income_account_name');

            $income_account = array_combine($ids, $names);
        }

        return $income_account;
    }

    /**
     * get_voucher_type_effect
     * @param int $voucher_type_id
     * @return array
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function getVoucherTypeEffect(int $voucher_type_id): array
    {
        $voucherTypeEffectReadBuilder = $this->read_db->table('voucher_type_effect');
        $voucherTypeEffectReadBuilder->select(array('voucher_type_effect_code', 'voucher_type_id', 'voucher_type_effect_id'));
        $voucherTypeEffectReadBuilder->join('voucher_type', 'voucher_type.fk_voucher_type_effect_id=voucher_type_effect.voucher_type_effect_id');
        return $voucherTypeEffectReadBuilder->where(array('voucher_type_id' => $voucher_type_id))->get()->getRowArray();
    }

    /**
     * get_accounts_ids_and_name
     * @date: 18 Dec 2023
     *
     * @return array
     * @access private
     * @author Onduso
     * @param : string $table, string $income_account_id_col, array $income_ids, $remove_T_expense_name=false
     */
    private function getAccountsIdsAndName(string $table, string $income_account_id_col, array $income_ids, $remove_T_expense_name = false): array
    {

        $genericTableReadBuilder = $this->read_db->table($table);

        $genericTableReadBuilder->select([$table . '_id', $table . '_name']);
        $genericTableReadBuilder->whereIn($income_account_id_col, $income_ids);

        if ($table != 'contra_account') {
            $genericTableReadBuilder->where([$table . '_is_active' => 1]);
        }

        if ($remove_T_expense_name == true) {
            $genericTableReadBuilder->notLike($table . '_name', 'T', 'after');
        }

        $accounts = $genericTableReadBuilder->get()->getResultArray();

        $account_ids = array_column($accounts, $table . '_id');
        $account_names = array_column($accounts, $table . '_name');

        $accounts_ids_and_names = array_combine($account_ids, $account_names);

        return $accounts_ids_and_names;
    }

        /**
     *unapproved_month_vouchers(): Returns the total of unapproved vouchers for current month for an office
     *
     * @author Livingstone Onduso: Dated 08-04-2023
     * @access public
     * @param Int $office_id - Office primary key
     * @param String $reporting_month - Date of the month
     * @param String $effect_code - Effect code e.g. income or expense
     * @param String $account_code - Account code e.g cash or bank
     * @param Int $cash_type_id - Cash type e.g. petty cash
     * @param Int $office_bank_id - Cash type e.g. bank 1
     * @return float - True if reconciliation has been created else false
     */
    public function unapproved_month_vouchers(int $office_id, string $reporting_month, string $effect_code, string $account_code, int $cash_type_id = 0, int $office_bank_id = 0): float
    {
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $max_approval_status_ids = $statusLibrary->getMaxApprovalStatusId('voucher');

        $start_of_reporting_month = date('Y-m-01', strtotime($reporting_month));

        $end_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

        $voucherDetailReadBuilder = $this->read_db->table('voucher_detail');
        $voucherDetailReadBuilder->selectSum('voucher_detail_total_cost');
        $voucherDetailReadBuilder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $voucherDetailReadBuilder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $voucherDetailReadBuilder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $voucherDetailReadBuilder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $voucherDetailReadBuilder->where(['voucher.fk_office_id' => $office_id, 'voucher.voucher_date >=' => $start_of_reporting_month, 'voucher.voucher_date <=' => $end_of_reporting_month, 'voucher.fk_status_id!=' => $max_approval_status_ids[0]]);
        $voucherDetailReadBuilder->where(['voucher_type_effect_code' => $effect_code, 'voucher_type_account_code' => $account_code]);

        if ($cash_type_id != 0) {
            $voucherDetailReadBuilder->where(['fk_office_cash_id' => $cash_type_id]);
        } else if ($office_bank_id != 0) {
            $voucherDetailReadBuilder->where(['fk_office_bank_id' => $office_bank_id]);
        }

        $voucherDetailReadBuilder->groupBy(array('voucher_detail.fk_voucher_id'));
        $results = $voucherDetailReadBuilder->get()->getResultArray();
        $totals_arr = array_column($results, 'voucher_detail_total_cost');

        return array_sum($totals_arr);
    }

        /**
     * Get the get_cheques_for_office
     *
     * Gives an array of the voucher signitories
     *
     * @param int $office - the id office
     * @return array - An array
     * @author LOnduso
     */
    public function getChequesForOffice(Int $office, Int $bank_office_id, Int $cheque_number): int
    {
        //Get the cheque numbers for an office for a given bank office
        $voucherReadBuilder = $this->read_db->table('voucher');
        $voucherReadBuilder->select(array('voucher_cheque_number'));
        $voucherReadBuilder->where(array('fk_office_id' => $office, 'fk_office_bank_id' => $bank_office_id, 'voucher_cheque_number' => $cheque_number));
        $cheque_numbers = $voucherReadBuilder->get()->getNumRows();

        return $cheque_numbers;
    }

    public function unrefundedAmountByFromVoucherId($from_voucher_id, $settlementType = 'bank_refund', $originalVoucherAmount = 0){
        $voucherReadBuilder = $this->read_db->table('voucher');

        $voucherReadBuilder->selectSum("voucher_detail_total_cost");
        $voucherReadBuilder->where(array($settlementType == 'bank_refund' ? 'voucher_reversal_from' : 'voucher_cleared_from' => $from_voucher_id));
        $voucherReadBuilder->join('voucher_detail', 'voucher_detail.fk_voucher_id = voucher.voucher_id');
        $total_refund_amount_obj = $voucherReadBuilder->get();
    
        $total_refund_amount = 0;
    
        if($total_refund_amount_obj->getNumRows() > 0){
          $refunded_to_vouchers = $total_refund_amount_obj->getResultArray();
          $total_refund_amount = abs(array_sum(array_column($refunded_to_vouchers, 'voucher_detail_total_cost')));
        }

        $unrefunded_amount = $originalVoucherAmount - $total_refund_amount;
    
        return $unrefunded_amount;
      }

      /**
     * get_income_and_expense_for_account_system
     * Gets the income acc and expense account for a given account system as a row
     * @param int int $office_id
     * @author Livingstone Onduso.
     * @date 2024-04-30
     * @access private
     * @return  array
     */
    private function getIncomeAndExpenseForAccountSystem(int $account_system_id): array
    {

        $accounts = [];
        $incomeAccountReadBuilder = $this->read_db->table('income_account');

        $incomeAccountReadBuilder->select(['income_account_id', 'expense_account_id']);
        $incomeAccountReadBuilder->join('expense_account', 'expense_account.fk_income_account_id=income_account.income_account_id');
        $incomeAccountReadBuilder->where(['fk_account_system_id' => $account_system_id]);
        $income_and_expense_acc = $incomeAccountReadBuilder->get();

        if ($income_and_expense_acc->getNumRows() > 0) {
            $row = $income_and_expense_acc->getRow();
            $accounts['income_acc'] = $row->income_account_id;
            $accounts['expense_acc'] = $row->expense_account_id;
        }

        return  $accounts;
    }

       /**
     * insert_zero_amount_voucher
     * To insert a voucher with zero amount for voided chq
     * @param int $cheque_number, int $cheque_id, int $office_bank_id
     * @author Livingstone Onduso.
     * @date 2024-04-22
     * @access public
     * @return 
     */
    public function insertZeroAmountVoucher(int $cheque_number, int $cheque_id, int $office_bank_id, $cnt)
    {

        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $voucherTypeLibrary = new \App\Libraries\Grants\VoucherTypeLibrary();
        $approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $voucherWriteBuilder = $this->write_db->table('voucher');
        $voucherDetailWriteBuilder = $this->write_db->table('voucher_detail');

        $office = $officeLibrary->getOfficeByOfficeBankId($office_bank_id);

        $office_id = $office['office_id'];
        $last_voucher_number_and_date = $this->getOfficeLastVoucher($office_id);
        $voided_chq_voucher_type = $voucherTypeLibrary->getHiddenVoucherType('VChq', $office['account_system_id']); // $this->voucher_type_model->get_hidden_voucher_type('bank', 'expense', 2, 1)->voucher_type_id;
        $accounts = $this->getIncomeAndExpenseForAccountSystem($office['account_system_id']);

        $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('voucher');

        $header['voucher_track_number'] = $itemTrackNumberAndName['voucher_track_number'];
        $header['voucher_name'] = $itemTrackNumberAndName['voucher_name'];
        $header['fk_office_id'] = $office['office_id'];
        $header['voucher_date'] = $last_voucher_number_and_date['voucher_date'];
        $header['voucher_number'] =  $last_voucher_number_and_date['voucher_number'] + $cnt;
        $header['fk_voucher_type_id'] = $voided_chq_voucher_type->voucher_type_id;
        $header['fk_office_bank_id'] = $office_bank_id;
        $header['fk_office_cash_id'] = 0;
        $header['voucher_cheque_number'] = $cheque_number;
        $header['fk_cheque_book_id'] = $cheque_id;
        $header['voucher_vendor'] = get_phrase('not_applicable_header_voucher_vendor', "Not Applicable");
        $header['voucher_vendor_address'] = get_phrase('not_applicable_header_voucher_address', "Not Applicable");
        $header['voucher_description'] = get_phrase('not_applicable_header_voucher_description', "Not Applicable");
        $header['voucher_created_by'] = $this->session->user_id;
        $header['voucher_created_date'] = date('Y-m-d');
        $header['voucher_last_modified_by'] = $this->session->user_id;
        $header['fk_approval_id'] = $approvalLibrary->insertApprovalRecord('voucher');
        $header['fk_status_id'] = $statusLibrary->initialItemStatus('voucher');
        $header['voucher_cleared'] = 1;
        $header['voucher_cleared_month'] = date('Y-m-t', strtotime($last_voucher_number_and_date['voucher_date']));

        $voucherWriteBuilder->insert( $header);

        $insert_id = $this->write_db->insertID();

        $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('voucher_detail');
        $detail['fk_voucher_id'] = $insert_id;
        $detail['voucher_detail_track_number'] = $itemTrackNumberAndName ['voucher_detail_track_number'];
        $detail['voucher_detail_name'] = $itemTrackNumberAndName['voucher_detail_name'];
        $detail['voucher_detail_quantity'] = 0;
        $detail['voucher_detail_description'] = get_phrase('detail_desc', "Not Applicable");
        $detail['voucher_detail_unit_cost'] = 0.00;
        $detail['voucher_detail_total_cost'] = 0.00;
        $detail['fk_expense_account_id'] = $accounts['expense_acc'];
        $detail['fk_income_account_id'] = $accounts['income_acc'];
        $detail['fk_contra_account_id'] = 0;
        $detail['fk_project_allocation_id'] = $this->getAtleastOneFcpProjectAllocation($office_id);
        $detail['fk_request_detail_id'] =  0;
        $detail['fk_approval_id'] = 0; 
        $detail['fk_status_id'] = $statusLibrary->initialItemStatus('voucher_detail');

        $voucherDetailWriteBuilder->insert($detail);

        $voucher_date = $last_voucher_number_and_date['voucher_date'];

        //Create month cash journal  and mfr if is first voucher of the month on cancellation of chq.
        $this->createReportAndJournal($office_id, $voucher_date);
        
        return $insert_id;
    }

    /**
     * get_atleast_fcp_project_allocation
     * Gets the first project as a row
     * @param int int $office_id
     * @author Livingstone Onduso.
     * @date 2024-04-22
     * @access private
     * @return  int
     */
    private function getAtleastOneFcpProjectAllocation(int $office_id): int
    {

        $projectAllocationReadBuilder = $this->read_db->table('project_allocation');

        $projectAllocationReadBuilder->select(['project_allocation_id']);
        $projectAllocationReadBuilder->where(['fk_office_id' => $office_id]);
        $project_allocation_id = $projectAllocationReadBuilder->get();

        if ($project_allocation_id->getNumRows() > 0) {
            return $project_allocation_id->getRow()->project_allocation_id;
        } else {
            return 0;
        }
    }

    /**
     * re_number_voucher_numbering 
     * Gets all vouchers of the mother
     * @param int $voucher_id
     * @author Livingstone Onduso.
     * @date 2024-04-26
     * @access public
     * @return array
     */
    private function getVoucherNumbersForRenumbering(int $voucher_id): array
    {

        //Get the office and transaction_date
        /*
    FOR LAG OF WRITE AND READ REPLICATION in READ NODE WE HAVE TO USE 'write_db' and NOT 'read_db' 
    WHEN READING THE LIST FOR VOUCHERS FOR RENUMBERING
   */
        $voucherWriteBuilder = $this->write_db->table('voucher');

        $voucherWriteBuilder->select(['voucher_date', 'fk_office_id']);
        $voucherWriteBuilder->where(['voucher_id' => $voucher_id]);
        $voucher_date_and_office = $voucherWriteBuilder->get()->getRow();

        //Get all vouchers of the months
        $voucherWriteBuilder->select(['voucher_id', 'voucher_number']);
        $voucherWriteBuilder->where(
            [   
                    'fk_office_id' => $voucher_date_and_office->fk_office_id, 
                    'voucher_date >=' => date('Y-m-01', strtotime($voucher_date_and_office->voucher_date)), 
                    'voucher_date <=' => date('Y-m-t', strtotime($voucher_date_and_office->voucher_date))
                ]
            );
        return $voucherWriteBuilder->get()->getResultArray();
    }    

    /**
     * revert_cancelled_cheque_and_related_voucher 
     * reverts the chq number
     * @param int  $voucher_id
     * @author Livingstone Onduso.
     * @date 2024-04-22
     * @access public
     * @return int
     */
    public function revertCancelledChequeAndRelatedVoucher(int $voucher_id): int
    {

        $voucherWriteBuilder = $this->write_db->table('voucher');


        $this->write_db->transStart();

        // Create cheque injection if the cheque is for a closed cheque book
        $this->createChequeInjectionForOfficeBank($voucherWriteBuilder, $voucher_id);

        //Get vouchers to re-number after delete
        $vouchers = $this->getVoucherNumbersForRenumbering($voucher_id);
        $voucher_number = $vouchers[0]['voucher_number'];
        $voucher_number_splitted = str_split($voucher_number, 4);
        $year_month_part = $voucher_number_splitted[0];
        $new_voucher_number = '';

        //Renumber the voucher after delete
        $start_serial = 1;
        foreach ($vouchers as $voucher) {
            //Build the serial number
            if (strlen($start_serial) < 2) {
                $new_voucher_number = $year_month_part . '0' . $start_serial;
            } elseif (strlen($start_serial) >= 2) {
                $new_voucher_number = $year_month_part . $start_serial;
            }

            //If the voucher selected to delete is equal to the one selected for delete just continue with loop
            if ($voucher['voucher_id'] == $voucher_id) {
                continue;
            }

            //Update the records in voucher
            $update_data['voucher_number'] = $new_voucher_number;
            $voucherWriteBuilder->where(['voucher_id' => $voucher['voucher_id']]);
            $voucherWriteBuilder->update( $update_data);

            $start_serial++;
        }


        // Delete the voucher in voucher and voucher details tables with the voided cheque
        // Due to the Cascade the cancel_cheque record is also removed.
        
        $voucherWriteBuilder->where(['voucher_id' => $voucher_id]);
        $voucherWriteBuilder->delete();
        
        $status = 1;
        $this->write_db->transComplete();

        if ($this->write_db->transStatus() == false) {
            $status = 0;
        }

        return $status;
    }

    private function createChequeInjectionForOfficeBank($voucherWriteBuilder, $voucher_id){
        $chequeBookLibrary = new \App\Libraries\Grants\ChequeBookLibrary();
        $chequeInjectionLibrary = new \App\Libraries\Grants\ChequeInjectionLibrary();

        $voucherWriteBuilder->where(['voucher_id' => $voucher_id]);
        $voucherObj = $voucherWriteBuilder->get();
        $voucherToDelete = [];

        if($voucherObj->getNumRows() > 0){
            $voucherToDelete = $voucherObj->getRowArray(); 

            $officeBankId = $voucherToDelete['fk_office_bank_id'];
            $chequeNumber = $voucherToDelete['voucher_cheque_number'];

            $getValidChequesNumbers = $chequeBookLibrary->getAllApprovedActiveChequeBooksLeaves($officeBankId);//array_column($cancelChequeLibrary->getValidCheques($officeBankId),'cheque_number');
            $getValidChequesNumbers = array_map(function($chequeNumber){
                return (int) $chequeNumber;
            }, $getValidChequesNumbers);

            // log_message('error', json_encode(compact('voucherToDelete','getValidChequesNumbers')));

            if(!in_array($chequeNumber, $getValidChequesNumbers)){
                $chequeInjectionLibrary->createChequeInjectionForOfficeBank($officeBankId, $chequeNumber);
            }
        }

    }

    public function getValidAccrualVouchers(string $voucher_type_effect, int $officeId, string $voucher_date): array{
        $voucherNumbers = match($voucher_type_effect){
            VoucherTypeEffectEnum::BANK_REFUND->getCode() => $this->getBankRefundValidRefundVouchers($officeId, $voucher_date),
            // VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode(),VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode(),VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode() => $this->getUnclearedAccruals($voucher_type_effect, $officeId, $voucher_date)
            VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() => $this->getUnclearedReceivables($officeId, $voucher_date),
            VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() => $this->getUnclearedPayables($officeId, $voucher_date),
            VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode() => $this->getUnclearedPrepayments($officeId, $voucher_date),
        };

        return $voucherNumbers;
    }

    // private function getUnclearedAccruals($voucher_type_effect, $officeId, $voucher_date){
    //     log_message('error', json_encode(compact('voucher_type_effect','officeId','voucher_date')));
    //     $voucherReadBuilder = $this->read_db->table('voucher');
    //     $accrualClearanceValidPeriod = service('settings')->get('GrantsConfig.accrualClearanceValidPeriodInMonths');
    //     $voucherValidPeriod = date('Y-m-01', strtotime("-$accrualClearanceValidPeriod months", strtotime($voucher_date)));


    //     $voucherReadBuilder->select('voucher_number');
    //     $voucherReadBuilder->orderBy('voucher_date DESC');
    //     $voucherReadBuilder->where(['voucher.fk_office_id' => $officeId, 'voucher_type_effect_code' => $voucher_type_effect]);
    //     $voucherReadBuilder->where(['voucher.voucher_date >=' => $voucherValidPeriod, 'voucher_cleared' => 0, 'voucher_cleared_to' => 0]);
    //     $voucherReadBuilder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    //     $voucherReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    //     $resultObj = $voucherReadBuilder->get();

    //     $voucherNumbers = [];

    //     if($resultObj->getNumRows() > 0){
    //         $results = $resultObj->getResultArray();
    //         $voucherNumbers = array_column($results, 'voucher_number');
    //     }

    //     return $voucherNumbers;
    // }

    private function getBankRefundValidRefundVouchers(int $officeId, string $voucher_date){
        $voucherReadBuilder = $this->read_db->table('voucher');
        $refundClearanceValidPeriod = service('settings')->get('GrantsConfig.refundClearanceValidPeriodInMonths');
        $voucherValidPeriod = date('Y-m-01', strtotime("-$refundClearanceValidPeriod months", strtotime($voucher_date)));

        $voucherReadBuilder->select('voucher_number');
        $voucherReadBuilder->orderBy('voucher_date DESC');
        $voucherReadBuilder->where(['voucher.fk_office_id' => $officeId, 'voucher_type_account_code' => 'bank', 'voucher_type_effect_code' => VoucherTypeEffectEnum::EXPENSE->getCode()]);
        $voucherReadBuilder->where(['voucher.voucher_date >=' => $voucherValidPeriod, 'voucher_cleared' => 1, 'voucher_reversal_to' => 0, 'voucher_reversal_from' => 0]);
        $voucherReadBuilder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $voucherReadBuilder->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $voucherReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $resultObj = $voucherReadBuilder->get();

        $voucherNumbers = [];

        if($resultObj->getNumRows() > 0){
            $results = $resultObj->getResultArray();
            $voucherNumbers = array_column($results, 'voucher_number');
        }

        return $voucherNumbers;
    }

    private function getUnclearedReceivables(int $officeId, string $voucher_date){
        $voucherReadBuilder = $this->read_db->table('voucher');
        $accrualClearanceValidPeriod = service('settings')->get('GrantsConfig.accrualClearanceValidPeriodInMonths');
        $voucherValidPeriod = date('Y-m-01', strtotime("-$accrualClearanceValidPeriod months", strtotime($voucher_date)));


        $voucherReadBuilder->select('voucher_number');
        $voucherReadBuilder->orderBy('voucher_date DESC');
        $voucherReadBuilder->where(['voucher.fk_office_id' => $officeId, 'voucher_type_effect_code' => VoucherTypeEffectEnum::RECEIVABLES->getCode()]);
        $voucherReadBuilder->where(['voucher.voucher_date >=' => $voucherValidPeriod, 'voucher_cleared' => 0, 'voucher_cleared_to' => 0]);
        $voucherReadBuilder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $voucherReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $resultObj = $voucherReadBuilder->get();

        $voucherNumbers = [];

        if($resultObj->getNumRows() > 0){
            $results = $resultObj->getResultArray();
            $voucherNumbers = array_column($results, 'voucher_number');
        }

        return $voucherNumbers;
    }

    private function getUnclearedPayables(int $officeId, string $voucher_date){
         $voucherReadBuilder = $this->read_db->table('voucher');
        $accrualClearanceValidPeriod = service('settings')->get('GrantsConfig.accrualClearanceValidPeriodInMonths');
        $voucherValidPeriod = date('Y-m-01', strtotime("-$accrualClearanceValidPeriod months", strtotime($voucher_date)));


        $voucherReadBuilder->select('voucher_number');
        $voucherReadBuilder->orderBy('voucher_date DESC');
        $voucherReadBuilder->where(['voucher.fk_office_id' => $officeId, 'voucher_type_effect_code' => VoucherTypeEffectEnum::PAYABLES->getCode()]);
        $voucherReadBuilder->where(['voucher.voucher_date >=' => $voucherValidPeriod, 'voucher_cleared' => 0, 'voucher_cleared_to' => 0]);
        $voucherReadBuilder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $voucherReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $resultObj = $voucherReadBuilder->get();

        $voucherNumbers = [];

        if($resultObj->getNumRows() > 0){
            $results = $resultObj->getResultArray();
            $voucherNumbers = array_column($results, 'voucher_number');
        }

        return $voucherNumbers;
    }

    private function getUnclearedPrepayments(int $officeId, string $voucher_date){
         $voucherReadBuilder = $this->read_db->table('voucher');
        $accrualClearanceValidPeriod = service('settings')->get('GrantsConfig.accrualClearanceValidPeriodInMonths');
        $voucherValidPeriod = date('Y-m-01', strtotime("-$accrualClearanceValidPeriod months", strtotime($voucher_date)));


        $voucherReadBuilder->select('voucher_number');
        $voucherReadBuilder->orderBy('voucher_date DESC');
        $voucherReadBuilder->where(['voucher.fk_office_id' => $officeId, 'voucher_type_effect_code' => VoucherTypeEffectEnum::PREPAYMENTS->getCode()]);
        $voucherReadBuilder->where(['voucher.voucher_date >=' => $voucherValidPeriod, 'voucher_cleared' => 0, 'voucher_cleared_to' => 0]);
        $voucherReadBuilder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $voucherReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $resultObj = $voucherReadBuilder->get();

        $voucherNumbers = [];

        if($resultObj->getNumRows() > 0){
            $results = $resultObj->getResultArray();
            $voucherNumbers = array_column($results, 'voucher_number');
        }
        // log_message('error', json_encode($voucherNumbers));
        return $voucherNumbers;
    }

    function clearAccrualTransaction($incurringVoucherId){
        // addVoucher
        $voucherReadBuilder = $this->read_db->table('voucher');

        // Get incurring voucher
        $accrualLedgers = AccrualLedgerAccounts::cases();

        $voucherReadBuilder->where('voucher_id', $incurringVoucherId);
        $voucherReadBuilder->whereIn('voucher_type_efect_code', $accrualLedgers);
        $incurringVoucherObj = $voucherReadBuilder->get();
    }
}
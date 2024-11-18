<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\VoucherModel;

class VoucherLibrary extends GrantsLibrary
{
    protected $table;
    protected $voucherModel;

    function __construct()
    {
        parent::__construct();

        $this->voucherModel = new VoucherModel();

        $this->table = 'voucher';
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
        $header['voucher_approvers'] = isset($raw_result[0]) && $raw_result[0]['voucher_approvers'] != null ? json_decode($raw_result[0]['voucher_approvers']): [];

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
        $office_accounting_system = $builder->getWhere( array('office_id' => $office_id))->getRow();

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
      ($voucher_data['voucher_status_id'] == $initial_item_status || $direction == -1)  &&
      $voucher_is_reversed == 0  &&
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
 
     return  $is_expense_more_than_income;
   }

   /**
     *Selected_voucher_income_total_cost(): Returns cash recieved in the bank or cash deposit in petty cash box on the selected voucher
     * @author Livingstone Onduso: Dated 08-04-2023
     * @access public
     * @param Int $voucher_id - voucher id
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
         $builder->select(['voucher_id', 'office_id', 'office_code', 'voucher_type_account_name', 'voucher_type_effect_name', 'voucher_type_is_cheque_referenced', 'voucher_number', 'voucher_date', 'fk_voucher_type_id', 'voucher_type_name', 'fk_office_bank_id', 'fk_office_cash_id', 'office_bank_name', 'voucher_cheque_number', 'voucher_vendor', 'voucher_vendor_address', 'voucher_description']);
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

     function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void {
        // if(!$this->session->system_admin){
        //     $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        //     $queryBuilder->where(['voucher.fk_status_id >' => $statusLibrary->getMaxApprovalStatusId($this->controller)]);
        // }
     }

    function changeFieldType(): array{
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

    public function getVoucherDate(int $office_id, string $journal_month = ''): String
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
             'voucher.fk_office_id' => $office_id, 'voucher_date >=' => date('Y-m-01', strtotime($financial_report_month)),
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
    public function checkIfOfficeHasStartedTransacting(Int $office_id): Bool
    {
        // If the office has not voucher yet, then the transacting month equals the office start date
        $count_of_vouchers = $this->read_db->table('voucher')
        ->getWhere( array('fk_office_id' => $office_id))->getNumRows();

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
    public function getOfficeTransactingMonth(Int $office_id): String
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
    public function checkIfOfficeTransactingMonthHasBeenClosed(Int $office_id, String $date_of_month): Bool
    {
        // If the reconciliation of the max date month has been done and submitted,
        // then use the start date of the next month as the transacting date
        // *** Modify the query by checking if it has been submitted - Not yet done ****

        $check_month_reconciliation = $this->read_db->table('financial_report')->getWhere(
            array(
                'financial_report_is_submitted' => 1, 'fk_office_id' => $office_id,
                'financial_report_month' => date('Y-m-01', strtotime($date_of_month)),
            )
        )->getNumRows();

        return $check_month_reconciliation > 0 ? true : false;
    }
}
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
     * @return Array
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
        $header['voucher_approvers'] = json_decode($raw_result[0]['voucher_approvers']);

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

    //  function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void {
    //     if(!$this->session->system_admin){
    //         $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    //         $queryBuilder->where(['voucher.fk_status_id' => $statusLibrary->getMaxApprovalStatusId($this->controller)]);
    //     }
    //  }
}
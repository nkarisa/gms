<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\AccrualLedgerModel;
class AccrualLedgerLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $accrualLedgerModel;

    function __construct()
    {
        parent::__construct();

        $this->accrualLedgerModel = new accrualLedgerModel();

        $this->table = 'accrual_ledger';
    }

    public function actionBeforeInsert(array $postArray): array {
        $ledgerCode = $postArray['header']['accrual_ledger_code'];

        $ledgerEffect = match($ledgerCode){
            'receivables' => 'debit',
            'payables' => 'credit',
            'prepayments' => 'debit',
            'depreciation' => 'credit',
            'payroll_liability' => 'credit',
            default => NULL,
        };

        $creditEffect = match($ledgerCode){
            'receivables' => 'payments',
            'payables' => 'payables',
            'prepayments' => 'settlements',
            'depreciation' => 'depreciation',
            'payroll_liability' => 'payroll_liability',
            default => NULL,
        };

        $debitEffect = match($ledgerCode){
            'receivables' => 'receivables',
            'payables' => 'disbursements',
            'prepayments' => 'prepayments',
            'depreciation' => NULL, // Debit effect to be created later
            'payroll_liability' => NULL, // Debit effect to be created later
            default => NULL,
        };

        $postArray['header']['accrual_ledger_credit_effect'] = $creditEffect;
        $postArray['header']['accrual_ledger_debit_effect'] = $debitEffect;
        $postArray['header']['accrual_ledger_effect'] = $ledgerEffect;

        return $postArray;
    }

    public function changeFieldType(): array {
        $fields = [];

        $accrualLedgers = ['receivables','payables','prepayments','depreciation','payroll_liability'];
        // $accrualLedgerEffects = ['receivables','payments','payables','disbursements','prepayments','settlements','depreciation','payroll_liability'];

        // $fields['accrual_ledger_debit_effect']['field_type'] = 'select';
        // $fields['accrual_ledger_debit_effect']['options'] = array_combine($accrualLedgerEffects, $accrualLedgerEffects);

        // $fields['accrual_ledger_credit_effect']['field_type'] = 'select';
        // $fields['accrual_ledger_credit_effect']['options'] = array_combine($accrualLedgerEffects, $accrualLedgerEffects);

        $fields['accrual_ledger_code']['field_type'] = 'select';
        $fields['accrual_ledger_code']['options'] = array_combine($accrualLedgers, $accrualLedgers); 

        return $fields;
    }

    public function singleFormAddVisibleColumns(): array {
        return ['accrual_ledger_name','accrual_ledger_code'];
    }

    public function editVisibleColumns(): array {
        return ['accrual_ledger_name','accrual_ledger_is_active'];
    }

    public function transactionValidateDuplicatesColumns(): array{
        return ['accrual_ledger_name','accrual_ledger_code'];
    }

    public function listTableVisibleColumns(): array {
        return [
            'accrual_ledger_track_number',
            'accrual_ledger_name',
            'accrual_ledger_code',
            'accrual_ledger_is_active',
            'accrual_ledger_effect',
            'accrual_ledger_debit_effect',
            'accrual_ledger_credit_effect',
            'accrual_ledger_last_modified_date'
        ];
    }

    private function accrualLedgerBalance($officeId, $reportingMonth){
        $journalLibrary = new \App\Libraries\Grants\JournalLibrary();

        $firstDayOfNextReportingMonth = date('Y-m-01', strtotime('first day of next month', strtotime($reportingMonth)));
        $account_balance = $journalLibrary->monthOpeningAccrualBalance($officeId, $firstDayOfNextReportingMonth);
        

        if ($account_balance > -1 && $account_balance < 1) {
            $account_balance = 0;
        }

        return $account_balance;
    }   

    private function accrualLedgerHasTransactionInMonth($officeId, $accrualLedgerCode, $reportingMonth){
        $accrual_ledger_has_transaction_in_month = false;

        $start_month_date = date('Y-m-01', strtotime($reportingMonth));
        $end_month_date = date('Y-m-t', strtotime($reportingMonth));

        $accrualLedgers = $this->accrualLedgerModel->where('accrual_ledger_code', $accrualLedgerCode)->findAll();
        $ledgerEffects = [];

        foreach($accrualLedgers as $accrualLedger){
            if($accrualLedger['accrual_ledger_code'] === $accrualLedgerCode){
                $ledgerEffects[] = $accrualLedger['accrual_ledger_debit_effect'];
                $ledgerEffects[] = $accrualLedger['accrual_ledger_credit_effect'];
                break;
            }
        }

        $builder = $this->read_db->table("voucher");
        $builder->where(array('voucher_date >= ' => $start_month_date, 'voucher_date <= ' => $end_month_date, 'voucher.fk_office_id' => $officeId));
        $builder->whereIn('voucher_type_effect_code', $ledgerEffects);
        $builder->join('cash_recipient_account','cash_recipient_account.fk_voucher_id = voucher.voucher_id','left');
        $builder->join('voucher_type','voucher_type.voucher_type_id = voucher.fk_voucher_type_id');
        $builder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id = voucher_type.fk_voucher_type_effect_id');
        
        $count_of_vouchers = $builder->get()->getNumRows();

        $accrual_ledger_has_transaction_in_month = $count_of_vouchers > 0;

        return $accrual_ledger_has_transaction_in_month;
    }

    private function isAccrualLedgerObselete($officeId, $accrualLedgerCode, $reportingMonth){
        // Office cash acount becomes obselete when all these conditions are met:
        // 1. Should not have funds
        // 2. Should not have vouchers in the given month
        // 3. Should be Inactive ***

        $isAccrualLedgerObselete = false;

        $ledger_balance = $this->accrualLedgerBalance($officeId, $reportingMonth)[$accrualLedgerCode]['amount'];
        $accrual_ledger_has_transaction_in_month = $this->accrualLedgerHasTransactionInMonth($officeId, $accrualLedgerCode, $reportingMonth);

        if ($ledger_balance == 0 && !$accrual_ledger_has_transaction_in_month) {
            $isAccrualLedgerObselete = true;
        }

        return $isAccrualLedgerObselete;
    }

    public function getActiveAccrualLedgersByReportingMonth($officeId, $transactingMonth){
        $accrualLedgers = $this->accrualLedgerModel->findAll();

        $activeAccruals = [];

        foreach($accrualLedgers as $accrualLedger){
            $is_office_cash_obselete = $this->isAccrualLedgerObselete($officeId, $accrualLedger['accrual_ledger_code'], $transactingMonth);
            
            if (!$is_office_cash_obselete){
                $activeAccruals[] = $accrualLedger;
            }
            
        }

        return $activeAccruals;
    }
   
}
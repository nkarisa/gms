<?php 

namespace App\Libraries\Grants\Builders;

class Journal {
    public int $journalDetailColumns = 7;
    function __construct(private array $journalData){
        
    }

    function getNavigationIds(){
        return $this->journalData['vouchers']['navigation'];
    }
    function getJournalOfficeName(): string {
        return $this->journalData['vouchers']['office_name'];  
    }
    function getJournalTransactionMonth(): string {
        return $this->journalData['vouchers']['transacting_month'];
    }
    function getMonthBankOpeningBalance(): array {
        return $this->journalData['vouchers']['month_opening_balance']['bank'];
    }
    function getMonthCashOpeningBalance(): array {
        return $this->journalData['vouchers']['month_opening_balance']['cash'];
    }

    function getAccrualOpeningBalances(){
        $month_used_accrual_ledgers = ['receivables' => 100,'payables' => 200,'prepayments' => 300,'depreciation' => 400,'payroll_liability' => 500];
        return $month_used_accrual_ledgers;
    }
    function getMonthAccounts(): array {
        return $this->journalData['vouchers']['accounts'];
    }
    function getMonthSumAccounts(){
        $sumAccounts = 0;

        foreach($this->getMonthAccounts() as $accounts){
            if(!empty($accounts)){
                $sumAccounts += count($accounts);
            }
        }
        return $sumAccounts;
    }

    function getMonthSumIncomeAccounts(){
        $sumAccounts = 0;

        foreach($this->getMonthAccounts() as $accountType => $accounts){
            if($accountType == 'income' && !empty($accounts)){
                $sumAccounts += count($accounts);
            }
        }
        return $sumAccounts;
    }

    function getMonthSumExpenseAccounts(){
        $sumAccounts = 0;

        foreach($this->getMonthAccounts() as $accountType => $accounts){
            if($accountType == 'expense' && !empty($accounts)){
                $sumAccounts += count($accounts);
            }
        }
        return $sumAccounts;
    }
}
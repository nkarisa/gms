<?php 

namespace App\Libraries\Grants\Builders;

class Journal {

    use JournalBuilder;
    public int $journalDetailColumns = 7;
    function __construct(private array $journalData){
        
    }

    function getNavigationIds(){
        return $this->journalData['vouchers']['navigation'];
    }
    function getJournalOfficeName(): string {
        return $this->journalData['vouchers']['office_name'];  
    }

    // This two methods below [transactingMonth and getJournalTransactionMonth] give the same result
    function transactingMonth(){
        return $this->journalData['transacting_month'];
    }

    function getOfficeId(){
        return $this->journalData['master']['table_body']['office_id'];
    }

    function getJournalTransactionMonth(): string {
        return $this->journalData['vouchers']['transacting_month'];
    }
    function getMonthBankOpeningBalance(): array {
        return $this->journalData['vouchers']['month_opening_balance']['bank'];
    }

    function getOfficeBankAccountsIds(){
        return array_column($this->journalData['vouchers']['office_bank_accounts'],'office_bank_id');
    }

    function getOfficeCashAccountsIds(){
        return array_keys($this->journalData['vouchers']['month_opening_balance']['cash']);
    }

    function getMonthBankOpeningBalanceByOfficeBankId($officeBankId): array {
        return $this->journalData['vouchers']['month_opening_balance']['bank'][$officeBankId]['amount'];
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
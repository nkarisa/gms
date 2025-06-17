<?php 

namespace App\Libraries\Grants\Builders;

use App\Enums\AccrualLedgerAccounts;
use App\Enums\VoucherTypeEffectEnum;
use App\Enums\Settings;
class Journal {

    use JournalBuilder;
    public int $journalDetailColumns = 7;
    function __construct(private array $journalData){
        
    }

    function accrualActivationStatus(){
        return $this->journalData['accrual_activated'];
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

    public function getAccrualOpeningBalances(): array {
        $month_opening_balance = $this->journalData['vouchers']['month_opening_balance'];
        $accrualLedgers = AccrualLedgerAccounts::cases();
        $openingBalances = []; 

        foreach($accrualLedgers as $accrualLedgerObj){
            $accrualLedgerName = $accrualLedgerObj->value;
            $openingBalances[$accrualLedgerName] = $month_opening_balance[$accrualLedgerName]['amount'] ?? null;
        }

        return $openingBalances; 
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

    public function getInitialBankRunningBalance(){
        $running_bank_balance = [];
        $officeBanksIds = $this->getOfficeBankAccountsIds();

        foreach($officeBanksIds as $officeBankId){
            $running_bank_balance[$officeBankId] = $this->getMonthBankOpeningBalance()[$officeBankId]['amount'];
        }

        return $running_bank_balance;
    }

    public function getInitialCashRunningBalance(){
        $running_petty_cash_balance = [];
        $officeCashIds = $this->getOfficeCashAccountsIds();
        foreach($officeCashIds as $officeCashId){
            $running_petty_cash_balance[$officeCashId] = $this->getMonthCashOpeningBalance()[$officeCashId]['amount'];
        }

        return $running_petty_cash_balance;
    }

}
<?php 

namespace App\Libraries\Grants\Builders;

class Journal {
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
}
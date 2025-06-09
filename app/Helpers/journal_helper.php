<?php 

if(!function_exists('journal')){
    function journal(){
        $journalData = json_decode(file_get_contents(APPPATH.'Data/journalData.json'), true);
        return new \App\Libraries\Grants\Builders\Journal($journalData);
    }
}

if(!function_exists('navigation')){
    function navigation(){
        return view('journal/components/navigation', journal()->getNavigationIds());
    }
}

if(!function_exists(function: 'title')){
    function title(){
        return view('journal/components/title', [
            'office_name' => journal()->getJournalOfficeName(), 
            'transacting_month' => journal()->getJournalTransactionMonth()
        ]);
    }
}

if(!function_exists('titleColspan')){
    function titleColspan(){
        $count_of_month_used_accrual_ledgers = 5;
        return journal()->getMonthSumAccounts() + ($count_of_month_used_accrual_ledgers * 3) + journal()->journalDetailColumns + (count(journal()->getMonthBankOpeningBalance()) * 3) + (count(journal()->getMonthCashOpeningBalance()) * 3);
    }
}

if(!function_exists('bankLedgerColumnHeaders')){
    function bankLedgerColumnHeaders(){
        return view('journal/components/bankLedgerColumnHeaders', [
            'month_opening_balance' => journal()->getMonthBankOpeningBalance()
        ]);
    }
}

if(!function_exists('bankLedgerOpeningBalance')){
    function bankLedgerOpeningBalance(){
        return view('journal/components/bankLedgerOpeningBalance', [
            'month_opening_balance' => journal()->getMonthBankOpeningBalance()
        ]);
    }
}

if(!function_exists('cashLedgerColumnHeaders')){
    function cashLedgerColumnHeaders(){
        return view('journal/components/cashLedgerColumnHeaders', [
            'month_opening_balance' => journal()->getMonthCashOpeningBalance()
        ]);
    }
}

if(!function_exists('cashLedgerOpeningBalance')){
    function cashLedgerOpeningBalance(){
        return view('journal/components/cashLedgerOpeningBalance', [
            'month_opening_balance' => journal()->getMonthCashOpeningBalance()
        ]);
    }
}

if(!function_exists('accrualLedgerColumnHeaders')){
    function accrualLedgerColumnHeaders(){
        return view('journal/components/accrualLedgerColumnHeaders', [
            'month_opening_balance' => journal()->getAccrualOpeningBalances()
        ]);
    }
}

if(!function_exists('accrualLedgerOpeningBalance')){
    function accrualLedgerOpeningBalance(){
        return view('journal/components/accrualLedgerOpeningBalance', [
            'month_opening_balance' => journal()->getAccrualOpeningBalances()
        ]);
    }
}

if(!function_exists('accountSpreadEmpty')){
    function accountSpreadEmpty(){
        return view('journal/components/accountSpreadEmpty');
    }
}

if(!function_exists('incomeAccountsHeaderTitle')){
    function incomeAccountsHeaderTitle(){
        return view('journal/components/incomeAccountsHeaderTitle');
    }
}

if(!function_exists('expenseAccountsHeaderTitle')){
    function expenseAccountsHeaderTitle(){
        return view('journal/components/expenseAccountsHeaderTitle');
    }
}


if(!function_exists('bankAccountsTitle')){
    function bankAccountsTitle(){
        return view('journal/components/bankAccountsTitle',
        ['month_opening_balance' => journal()->getMonthCashOpeningBalance()]
    );
    }
}


if(!function_exists('cashAccountsTitle')){
    function cashAccountsTitle(){
        return view('journal/components/cashAccountsTitle',
        ['month_opening_balance' => journal()->getMonthCashOpeningBalance()]
    );
    }
}


if(!function_exists('accrualAccountsTitle')){
    function accrualAccountsTitle(){
        return view('journal/components/accrualAccountsTitle',
        ['month_opening_balance' => journal()->getMonthCashOpeningBalance()]
    );
    }
}
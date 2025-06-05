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
        $journal_detail_columns = 7;
        return journal()->getMonthSumAccounts() + ($count_of_month_used_accrual_ledgers * 3) + $journal_detail_columns + (count(journal()->getMonthBankOpeningBalance()) * 3) + (count(journal()->getMonthCashOpeningBalance()) * 3);
    }
}
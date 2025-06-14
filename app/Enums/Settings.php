<?php 

namespace App\Enums;

use App\Enums\AccrualLedgerAccounts;

enum Settings {
    case ACCRUAL_LEDGERS;

    function getSettings(){
        return match($this){
            self::ACCRUAL_LEDGERS => [
                AccrualLedgerAccounts::RECEIVABLES->value,
                AccrualLedgerAccounts::PAYABLES->value,
                AccrualLedgerAccounts::PREPAYMENTS->value,
                AccrualLedgerAccounts::DEPRECIATION->value,
                AccrualLedgerAccounts::PAYROLL_LIABILITY->value
            ],
        };
    }
}
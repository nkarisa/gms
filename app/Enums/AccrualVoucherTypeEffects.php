<?php 

namespace App\Enums;

use App\Enums\AccrualLedgerAccounts;

enum AccrualVoucherTypeEffects: string {
    case RECEIVABLES = 'receivables';
    case PAYABLES = 'payables';
    case PREPAYMENTS = 'prepayments'; 
    case RECEIVABLES_PAYMENTS = 'payments';
    case PAYABLE_DISBURSEMENTS = 'disbursements';
    case PREPAYMENT_SETTLEMENTS = 'settlements';
    case DEPRECIATION = 'depreciation';
    case PAYROLL_LIABILITY = 'payroll_liability';

    function getEffectAccrualLedger(){
        return match($this){
            self::RECEIVABLES, self::RECEIVABLES_PAYMENTS => AccrualLedgerAccounts::RECEIVABLES,
            self::PAYABLES, self::PAYABLE_DISBURSEMENTS => AccrualLedgerAccounts::PAYABLES,
            self::PREPAYMENTS, self::PREPAYMENT_SETTLEMENTS => AccrualLedgerAccounts::PREPAYMENTS,
            self::DEPRECIATION => AccrualLedgerAccounts::DEPRECIATION,
            self::PAYROLL_LIABILITY => AccrualLedgerAccounts::PAYROLL_LIABILITY,
        };
    }

    static function getAccrualClearanceEffects(){
        return [
            self::RECEIVABLES_PAYMENTS->value,
            self::PAYABLE_DISBURSEMENTS->value,
            self::PREPAYMENT_SETTLEMENTS->value
        ];
    }

}
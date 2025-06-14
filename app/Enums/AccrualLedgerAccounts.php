<?php 

namespace App\Enums;

use App\Enums\VoucherTypeEffectEnum;

enum AccrualLedgerAccounts: string {
    case RECEIVABLES = 'receivables';
    case PAYABLES = 'payables';
    case PREPAYMENTS = 'prepayments';
    case DEPRECIATION = 'depreciation';
    case PAYROLL_LIABILITY = 'payroll_liability';

    public function defaultEffect(): string{
        return match($this) {
            self::RECEIVABLES => 'debit',
            self::PAYABLES => 'credit',
            self::PREPAYMENTS => 'debit',
            self::DEPRECIATION => 'credit',
            self::PAYROLL_LIABILITY => 'credit'
        };
    }

    public function creditType(){
        return match($this) {
            self::RECEIVABLES => VoucherTypeEffectEnum::PREPAYMENTS->value,
            self::PAYABLES => VoucherTypeEffectEnum::PAYABLES->value,
            self::PREPAYMENTS => VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->value,
            self::DEPRECIATION => VoucherTypeEffectEnum::DEPRECIATION->value,
            self::PAYROLL_LIABILITY => VoucherTypeEffectEnum::PAYROLL_LIABILITY->value
        };
    }

    function debitType(){
        return match($this) {
            self::RECEIVABLES => VoucherTypeEffectEnum::RECEIVABLES->value,
            self::PAYABLES => VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->value,
            self::PREPAYMENTS => VoucherTypeEffectEnum::PREPAYMENTS->value,
            self::DEPRECIATION => NULL,
            self::PAYROLL_LIABILITY => NULL
        };
    }

    function effectsPair($transactionEffect){
        return match($this){
            self::PAYABLES => $transactionEffect == VoucherTypeEffectEnum::PAYABLES->value || $transactionEffect == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->value,
            self::RECEIVABLES => $transactionEffect == VoucherTypeEffectEnum::RECEIVABLES->value || $transactionEffect == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->value,
            self::PREPAYMENTS => $transactionEffect == VoucherTypeEffectEnum::PREPAYMENTS->value || $transactionEffect == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->value,
            self::DEPRECIATION => $transactionEffect == VoucherTypeEffectEnum::DEPRECIATION->value,
            self::PAYROLL_LIABILITY => $transactionEffect == VoucherTypeEffectEnum::PAYROLL_LIABILITY->value
        };
    }
}
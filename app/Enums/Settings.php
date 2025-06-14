<?php 

namespace App\Enums;

use App\Enums\AccrualLedgerAccounts;
use App\Enums\VoucherTypeEffectEnum;
use App\Enums\VoucherTypeAccountEnum;

enum Settings {
    case ACCRUAL_LEDGERS;
    case BANK_INCOME;
    case BANK_EXPENSE;
    case CASH_INCOME;
    case CASH_EXPENSE;
    case INCOME_SPREAD;
    case CONTRA_SPREAD;
    case ACRRUAL_EXPENSE_SPREAD;
    case ACRRUAL_INCOME_SPREAD;
    case ACRRUAL_CONTRA_SPREAD;
    

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

    function getTransactionEffectCondition($transaction_account, $transaction_effect){
        return match($this){
            self::BANK_INCOME => 
                    (
                        (
                            (
                                $transaction_account == VoucherTypeAccountEnum::BANK->value && 
                                $transaction_effect == VoucherTypeEffectEnum::INCOME->value
                            ) ||  
                            (
                                $transaction_account == VoucherTypeAccountEnum::CASH->value && 
                                $transaction_effect == VoucherTypeEffectEnum::CASH_CONTRA->value
                            )
                        ) ||
                        $transaction_effect == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->value
                    ),
            self::BANK_EXPENSE => (
                                    (
                                        $transaction_account == VoucherTypeAccountEnum::BANK->value && 
                                        $transaction_effect == VoucherTypeEffectEnum::EXPENSE->value) ||
                                        $transaction_effect == 'prepayments' || 
                                        $transaction_effect == 'disbursements' || 
                                        (
                                            $transaction_account == VoucherTypeAccountEnum::BANK->value && 
                                            (
                                                $transaction_effect == VoucherTypeEffectEnum::BANK_CONTRA->value || 
                                                $transaction_effect == VoucherTypeEffectEnum::BANK_TO_BANK_CONTRA->value
                                            )
                                        )
                    ),
            self::CASH_INCOME => (
                                    (
                                        $transaction_account == VoucherTypeAccountEnum::CASH->value && 
                                        $transaction_effect == VoucherTypeEffectEnum::INCOME->value
                                    ) || 
                                    (
                                        $transaction_account == VoucherTypeAccountEnum::BANK->value && 
                                        $transaction_effect == VoucherTypeEffectEnum::BANK_CONTRA->value
                                    )
                                    ),
            self::CASH_EXPENSE => (
                                    (   
                                        $transaction_account == VoucherTypeAccountEnum::CASH->value && 
                                        $transaction_effect == VoucherTypeEffectEnum::EXPENSE->value
                                    ) || 
                                    (
                                        $transaction_account == VoucherTypeAccountEnum::CASH->value && 
                                        (
                                            $transaction_effect == VoucherTypeEffectEnum::CASH_CONTRA->value || 
                                            $transaction_effect == VoucherTypeEffectEnum::CASH_TO_CASH_CONTRA->value
                                        )
                                    )
                                )

        };
    }


    function journalExpenseAccrualEffectsCondition($transaction_effect): bool{
        return match($this){
            self::ACRRUAL_EXPENSE_SPREAD => (
                $transaction_effect == VoucherTypeEffectEnum::DEPRECIATION->value || 
                $transaction_effect == VoucherTypeEffectEnum::PAYROLL_LIABILITY->value || 
                $transaction_effect == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->value || 
                $transaction_effect == VoucherTypeEffectEnum::PAYABLES->value
            ),
            self::ACRRUAL_INCOME_SPREAD => $transaction_effect == VoucherTypeEffectEnum::RECEIVABLES->value,
            self::ACRRUAL_CONTRA_SPREAD => (
                    $transaction_effect == VoucherTypeEffectEnum::PREPAYMENTS->value ||
                    $transaction_effect == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->value || 
                    $transaction_effect == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->value
            ),
            self::CONTRA_SPREAD => (
                $transaction_effect == VoucherTypeEffectEnum::CASH_CONTRA->value || 
                $transaction_effect == VoucherTypeEffectEnum::BANK_CONTRA->value || 
                $transaction_effect == VoucherTypeEffectEnum::BANK_TO_BANK_CONTRA->value || 
                $transaction_effect == VoucherTypeEffectEnum::CASH_TO_CASH_CONTRA->value
            )
        };
    }

}
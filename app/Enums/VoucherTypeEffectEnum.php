<?php 
namespace App\Enums;
enum VoucherTypeEffectEnum {

    case INCOME;
    case EXPENSE;
    case BANK_CONTRA;
    case CASH_CONTRA;
    case BANK_TO_BANK_CONTRA;
    case CASH_TO_CASH_CONTRA;
    case BANK_REFUND;
    case RECEIVABLES;
    case PAYABLES;
    case PREPAYMENTS; 
    case RECEIVABLES_PAYMENTS;
    case PAYABLE_DISBURSEMENTS;
    case PREPAYMENT_SETTLEMENTS;

    public function getCode(): string{
        return match($this) {
            self::INCOME => 'income',
            self::EXPENSE => 'expense',
            self::BANK_CONTRA => 'bank_contra',
            self::CASH_CONTRA => 'cash_contra',
            self::BANK_TO_BANK_CONTRA => 'bank_to_bank_contra',
            self::CASH_TO_CASH_CONTRA => 'cash_to_cash_contra',
            self::BANK_REFUND => 'bank_refund',
            self::RECEIVABLES => 'receivables',
            self::PAYABLES => 'payables',
            self::PREPAYMENTS => 'prepayments',
            self::RECEIVABLES_PAYMENTS => 'payments',
            self::PAYABLE_DISBURSEMENTS => 'disbursements',
            self::PREPAYMENT_SETTLEMENTS => 'settlements',
        };
    }

    public function getName(){
        return match($this) {
            self::INCOME => get_phrase('income'),
            self::EXPENSE => get_phrase('expense'),
            self::BANK_CONTRA => get_phrase('bank_contra'),
            self::CASH_CONTRA => get_phrase('cash_contra'),
            self::BANK_TO_BANK_CONTRA => get_phrase('bank_to_bank_contra'),
            self::CASH_TO_CASH_CONTRA => get_phrase('cash_to_cash_contra'),
            self::BANK_REFUND => get_phrase('bank_refund'),
            self::RECEIVABLES => get_phrase('receivables'),
            self::PAYABLES => get_phrase('payables'),
            self::PREPAYMENTS => get_phrase('prepayments'),
            self::RECEIVABLES_PAYMENTS => get_phrase('receivable_payments'),
            self::PAYABLE_DISBURSEMENTS => get_phrase('payable_disbursements'),
            self::PREPAYMENT_SETTLEMENTS => get_phrase('prepayment_settlements')
        };           
    }
}
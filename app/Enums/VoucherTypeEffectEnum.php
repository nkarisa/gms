<?php 
namespace App\Enums;
enum VoucherTypeEffectEnum {
    // case INCOME = 'income';
    // case EXPENSE = 'expense';
    // case BANK_CONTRA = 'bank_contra';
    // case CASH_CONTRA = 'cash_contra';
    // case BANK_TO_BANK_CONTRA = 'bank_to_bank_contra';
    // case CASH_TO_CASH_CONTRA = 'cash_to_cash_contra';
    // case BANK_REFUND = 'bank_refund';
    // case RECEIVABLES = 'receivables';
    // case PAYABLES = 'payables';
    // case PREPAYMENTS = 'prepayments';
    // case RECEIVABLES_PAYMENTS = 'payments';
    // case PAYABLE_DISBURSEMENTS = 'disbursements';
    // case PREPAYMENT_SETTLEMENTS = 'settlements';

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
            VoucherTypeEffectEnum::INCOME => 'income',
            VoucherTypeEffectEnum::EXPENSE => 'expense',
            VoucherTypeEffectEnum::BANK_CONTRA => 'bank_contra',
            VoucherTypeEffectEnum::CASH_CONTRA => 'cash_contra',
            VoucherTypeEffectEnum::BANK_TO_BANK_CONTRA => 'bank_to_bank_contra',
            VoucherTypeEffectEnum::CASH_TO_CASH_CONTRA => 'cash_to_cash_contra',
            VoucherTypeEffectEnum::BANK_REFUND => 'bank_refund',
            VoucherTypeEffectEnum::RECEIVABLES => 'receivables',
            VoucherTypeEffectEnum::PAYABLES => 'payables',
            VoucherTypeEffectEnum::PREPAYMENTS => 'prepayments',
            VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS => 'payments',
            VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS => 'disbursements',
            VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS => 'settlements',
        };
    }

    public function getName(){
        return match($this) {
            VoucherTypeEffectEnum::INCOME => get_phrase('income'),
            VoucherTypeEffectEnum::EXPENSE => get_phrase('expense'),
            VoucherTypeEffectEnum::BANK_CONTRA => get_phrase('bank_contra'),
            VoucherTypeEffectEnum::CASH_CONTRA => get_phrase('cash_contra'),
            VoucherTypeEffectEnum::BANK_TO_BANK_CONTRA => get_phrase('bank_to_bank_contra'),
            VoucherTypeEffectEnum::CASH_TO_CASH_CONTRA => get_phrase('cash_to_cash_contra'),
            VoucherTypeEffectEnum::BANK_REFUND => get_phrase('bank_refund'),
            VoucherTypeEffectEnum::RECEIVABLES => get_phrase('receivables'),
            VoucherTypeEffectEnum::PAYABLES => get_phrase('payables'),
            VoucherTypeEffectEnum::PREPAYMENTS => get_phrase('prepayments'),
            VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS => get_phrase('receivable_payments'),
            VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS => get_phrase('payable_disbursements'),
            VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS => get_phrase('prepayment_settlements')
        };           
    }
}
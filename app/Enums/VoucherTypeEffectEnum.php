<?php 
namespace App\Enums;
enum VoucherTypeEffectEnum: string {

    case INCOME = 'income';
    case EXPENSE = 'expense';
    case BANK_CONTRA = 'bank_contra';
    case CASH_CONTRA = 'cash_contra';
    case BANK_TO_BANK_CONTRA = 'bank_to_bank_contra';
    case CASH_TO_CASH_CONTRA = 'cash_to_cash_contra';
    case BANK_REFUND = 'bank_refund';
    case RECEIVABLES = 'receivables';
    case PAYABLES = 'payables';
    case PREPAYMENTS = 'prepayments'; 
    case RECEIVABLES_PAYMENTS = 'payments';
    case PAYABLE_DISBURSEMENTS = 'disbursements';
    case PREPAYMENT_SETTLEMENTS = 'settlements';
    case DEPRECIATION = 'depreciation';
    case PAYROLL_LIABILITY = 'payroll_liability';

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
            self::DEPRECIATION => 'depreciation',
            self::PAYROLL_LIABILITY => 'payroll_liability',
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
            self::PREPAYMENT_SETTLEMENTS => get_phrase('prepayment_settlements'),
            self::DEPRECIATION => get_phrase('depreciation'),
            self::PAYROLL_LIABILITY => get_phrase('payroll_liability'),
        };           
    }
}
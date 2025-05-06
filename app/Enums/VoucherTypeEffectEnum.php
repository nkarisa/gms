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
}
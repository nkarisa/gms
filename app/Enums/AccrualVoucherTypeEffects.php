<?php 

namespace App\Enums;

enum AccrualVoucherTypeEffects: string {
    case RECEIVABLES = 'receivables';
    case PAYABLES = 'payables';
    case PREPAYMENTS = 'prepayments'; 
    case RECEIVABLES_PAYMENTS = 'payments';
    case PAYABLE_DISBURSEMENTS = 'disbursements';
    case PREPAYMENT_SETTLEMENTS = 'settlements';
    case DEPRECIATION = 'depreciation';
    case PAYROLL_LIABILITY = 'payroll_liability';

}
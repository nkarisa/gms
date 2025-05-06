<?php 

namespace App\Enums;
enum VoucherTypeAccountEnum: string {
    case BANK = 'bank';
    case CASH = 'cash';
    case ACCRUAL = 'accrual';
}
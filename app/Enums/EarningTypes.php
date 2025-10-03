<?php 

namespace App\Enums;

enum EarningTypes: string {
    case PAYABLE = 'payable';
    case ACCRUED = 'accrued';
}
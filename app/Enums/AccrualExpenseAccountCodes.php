<?php 

namespace App\Enums;

enum AccrualExpenseAccountCodes: string {
  case DEPRECIATION = 'depreciation';
  case PAYROLL = 'payroll_liabilities';
}
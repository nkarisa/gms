<?php 
namespace App\Enums;
enum AccountSystemSettingEnum: string {
    case ACCRUAL_SETTING_NAME = 'use_accrual_based_accounting';
    case VOUCHER_ATTACHMENT_SETTING_NAME = 'voucher_attachments_required';
    case PCA_OBJECTIVES_SETTINGS_NAME = 'use_pca_objectives';
}
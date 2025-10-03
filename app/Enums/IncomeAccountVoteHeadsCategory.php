<?php 

namespace App\Enums;

enum IncomeAccountVoteHeadsCategory: string {
    case SUPPORT = 'support';
    case GIFTS = 'gifts';
    case NON_COMPASSION = 'non_compassion';
    case ONGOING_INTERVENTION = 'ongoing_intervention';
    case INDIVIDUAL_INTERVENTION = 'individual_intervention';
    case DEPRECIATION = 'deprecitation';
    case PAYROLL_LIABILITY = 'payroll_liability';
    case SUSPENSE = 'suspense';
    case ASSET_ACQUISITION = 'asset_acquisition';

}
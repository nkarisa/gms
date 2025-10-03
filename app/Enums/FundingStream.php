<?php 

namespace App\Enums;

enum FundingStream: string {
    case SUPPORT = 'support';
    case GIFT = 'gift';
    case INDIVIDUAL = 'individual';
    case ONGOING = 'ongoing';
    case LOCAL = 'local';
}
<?php 

namespace App\Enums;

enum AccrualClearanceStatus: string {
    case PENDING = 'pending';
    case CLEARED = 'cleared';
}
<?php
declare(strict_types=1);

namespace App\Enum;

enum PvpStatus: string
{
    case Pending  = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Done     = 'done';
}

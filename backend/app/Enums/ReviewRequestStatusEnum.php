<?php

namespace App\Enums;

enum ReviewRequestStatusEnum: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Clicked = 'clicked';
    case Expired = 'expired';
}

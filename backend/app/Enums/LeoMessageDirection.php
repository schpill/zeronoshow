<?php

namespace App\Enums;

enum LeoMessageDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}

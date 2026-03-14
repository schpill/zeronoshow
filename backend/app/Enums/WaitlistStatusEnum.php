<?php

namespace App\Enums;

enum WaitlistStatusEnum: string
{
    case Pending = 'pending';
    case Notified = 'notified';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Notified => 'Notifié',
            self::Confirmed => 'Confirmé',
            self::Declined => 'Décliné',
            self::Expired => 'Expiré',
        };
    }
}

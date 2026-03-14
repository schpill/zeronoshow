<?php

namespace App\Enums;

enum VoiceCallStatusEnum: string
{
    case Initiated = 'initiated';
    case Ringing = 'ringing';
    case Answered = 'answered';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case NoAnswer = 'no_answer';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Initiated => 'Initié',
            self::Ringing => 'En sonnerie',
            self::Answered => 'Répondu',
            self::Confirmed => 'Confirmé',
            self::Declined => 'Refusé',
            self::NoAnswer => 'Sans réponse',
            self::Failed => 'Échec',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Confirmed, self::Declined, self::NoAnswer, self::Failed], true);
    }
}

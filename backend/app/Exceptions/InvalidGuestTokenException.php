<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidGuestTokenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Token de réservation invalide ou expiré.');
    }
}

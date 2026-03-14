<?php

namespace App\Exceptions;

use RuntimeException;

class TooManyOtpRequestsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Trop de demandes de code. Veuillez réessayer dans 10 minutes.');
    }
}

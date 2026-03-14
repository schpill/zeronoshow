<?php

namespace App\Exceptions;

use RuntimeException;

class OtpExpiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Le code de vérification a expiré. Demandez un nouveau code.');
    }
}

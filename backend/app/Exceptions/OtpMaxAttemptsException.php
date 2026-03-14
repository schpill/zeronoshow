<?php

namespace App\Exceptions;

use RuntimeException;

class OtpMaxAttemptsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Nombre maximum de tentatives atteint. Demandez un nouveau code.');
    }
}

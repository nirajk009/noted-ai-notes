<?php

namespace App\Exceptions;

use RuntimeException;

class GoogleAiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 503)
    {
        parent::__construct($message);
    }
}

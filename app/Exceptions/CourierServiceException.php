<?php

namespace App\Exceptions;

use Exception;

class CourierServiceException extends Exception
{
    public function __construct(string $message, int $statusCode = 500, ?Exception $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}

<?php

namespace App\Exceptions;

use Exception;

class CourierValidationException extends Exception
{
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message, 422);
        $this->errors = $errors;
    }

    public array $errors = [];
}

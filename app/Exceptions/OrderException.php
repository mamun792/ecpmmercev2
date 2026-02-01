<?php

namespace App\Exceptions;

use Exception;

class OrderException extends Exception
{
    public function __construct(
        string $message = 'Order processing error',
        int $code = 400
    ) {
        parent::__construct($message, $code);
    }
}

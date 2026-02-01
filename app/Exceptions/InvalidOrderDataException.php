<?php

namespace App\Exceptions;

use Exception;

class InvalidOrderDataException extends \Exception
{
    private $validationErrors;

    public function __construct(
        string $message,
        array $validationErrors = []
    ) {
        parent::__construct($message);
        $this->validationErrors = $validationErrors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function getValidationErrorsAsString(): string
    {
        return implode(', ', $this->validationErrors);
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'validation_errors' => $this->getValidationErrors(),
        ], 422);
    }
}

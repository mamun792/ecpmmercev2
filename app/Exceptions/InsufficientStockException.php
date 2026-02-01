<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    protected $message = 'Insufficient stock available';
    protected $code = 422;
    private $stockErrors = [];

    /**
     * Constructor for InsufficientStockException
     *
     * @param string|null $message Custom message
     * @param int|null $code Custom error code
     * @param array $stockErrors Detailed stock error information
     */
    public function __construct($message = null, $code = null, array $stockErrors = [])
    {
        if ($message) {
            $this->message = $message;
        }
        if ($code) {
            $this->code = $code;
        }

        $this->stockErrors = $stockErrors;

        parent::__construct($this->message, $this->code);
    }

    /**
     * Get stock error details
     *
     * @return array
     */
    public function getStockErrors(): array
    {
        return $this->stockErrors;
    }

    /**
     * Render the exception into an HTTP response
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'error' => $this->message,
            'stock_errors' => $this->stockErrors
        ], $this->code);
    }
}

class InvalidOrderDataException extends Exception
{
    private $validationErrors = [];

    /**
     * Constructor for InvalidOrderDataException
     *
     * @param string $message Error message
     * @param array $validationErrors Validation error details
     * @param int $code HTTP error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = "Invalid order data",
        array $validationErrors = [],
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->validationErrors = $validationErrors;
    }

    /**
     * Get validation error details
     *
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Render the exception into an HTTP response
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'error' => $this->message,
            'validation_errors' => $this->validationErrors
        ], $this->code);
    }
}

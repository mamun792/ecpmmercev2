<?php

namespace App\Exceptions;

use Exception;

class CouponException extends Exception
{

    protected $details;
    public function __construct($message = "Coupon not found", $code = 404, $details = [])
    {
        $this->details = $details;
        parent::__construct($message, $code);
    }

    public function getDetails()
    {
        return $this->details;
    }


    public function render($request)
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], $this->getCode());
    }
}

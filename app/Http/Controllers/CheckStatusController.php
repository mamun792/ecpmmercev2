<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\SystemCheck;

class CheckStatusController extends Controller
{
    public function checkLicense()
    {
        $check = SystemCheck::isLicenseValid();
        if ($check) {
            return response()->json([
                'data' => $check,
                'status' => 'valid',
                'message' => 'License is valid.'
            ]);
        } else {
            return response()->json([
                'data' => $check,
                'status' => 'invalid',
                'message' => 'License is invalid.'
            ]);
        }
    }
}

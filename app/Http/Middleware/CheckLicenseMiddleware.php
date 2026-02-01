<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class CheckLicenseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $statusFile = storage_path('app/license_status.json');

        if (File::exists($statusFile)) {
            $status = json_decode(File::get($statusFile), true);

            if (isset($status['valid']) && $status['valid'] === false) {
                // Allow only the license-check route
                if ($request->is('license-check')) {
                    return $next($request);
                }

                return response()->view('errors.license_invalid', [], 403);
            }
        }

        return $next($request);
    }
}

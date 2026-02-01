<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        // If no authenticated user or user doesn't have the permission, abort
        if (!Auth::check() || !Auth::user()->hasPermissionTo($permission)) {
            // For web requests, show 403 page; for AJAX/API return JSON
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
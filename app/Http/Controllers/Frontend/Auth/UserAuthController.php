<?php

namespace App\Http\Controllers\Frontend\Auth;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use App\Http\Requests\Auth\LoginRequest;

class UserAuthController extends Controller
{
    /**
     * Display the user login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming user authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Check if user has admin role - redirect them to admin login
        if ($user->hasAnyRole(['admin', 'super-admin', 'Admin', 'Super Admin'])) {
            //Auth::guard('web')->logout();
            // $request->session()->invalidate();
            // $request->session()->regenerateToken();
            
            return redirect()->route('admin.dashboard.index')->with('status', 'Please use the admin login page.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('user.dashboard'));
    }

    /**
     * Destroy an authenticated user session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

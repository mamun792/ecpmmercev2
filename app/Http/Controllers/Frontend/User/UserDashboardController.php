<?php

namespace App\Http\Controllers\Frontend\User;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Order;

class UserDashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Get user's orders excluding incomplete orders with product relationships
        $orders = Order::where('user_id', $user->id)
            ->where('status', '!=', 'incomplete')
            ->with('items', 'items.product', 'items.productVariation', 'items.productVariation.attributes.value.attribute', 'items.productVariation.attributes.value')
            ->latest()
            ->paginate(10);

        // Get order statistics (excluding incomplete)
        $totalOrders = Order::where('user_id', $user->id)
            ->where('status', '!=', 'incomplete')
            ->count();
        $pendingOrders = Order::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        $completedOrders = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->count();
        $canceledOrders = Order::where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        return Inertia::render('Frontend/User/Dashboard', [
            'user' => $user,
            'orders' => $orders,
            'stats' => [
                'totalOrders' => $totalOrders,
                'pendingOrders' => $pendingOrders,
                'completedOrders' => $completedOrders,
                'canceledOrders' => $canceledOrders,
            ],
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Password updated successfully');
    }
}

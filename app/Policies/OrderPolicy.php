<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view orders');
    }

    /**
     * Determine if the user can view the order
     */
    public function view(User $user, Order $order): bool
    {
        // Admin can view all orders
        if ($user->hasPermissionTo('view orders')) {
            return true;
        }

        // Customer can only view their own orders
        return $order->user_id === $user->id;
    }

    /**
     * Determine if the user can create orders
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create orders');
    }

    /**
     * Determine if the user can update the order
     */
    public function update(User $user, Order $order): bool
    {
        // Only admin can update orders
        if (!$user->hasPermissionTo('edit orders')) {
            return false;
        }

        // Additional business logic: Can't update completed/cancelled orders
        if (in_array($order->status, ['completed', 'cancelled', 'delivered'])) {
            return $user->hasRole('super-admin'); // Only super admin can modify finalized orders
        }

        return true;
    }

    /**
     * Determine if the user can delete the order
     */
    public function delete(User $user, Order $order): bool
    {
        // Only admin with delete permission
        if (!$user->hasPermissionTo('delete orders')) {
            return false;
        }

        // Can't delete completed/delivered orders
        if (in_array($order->status, ['completed', 'delivered'])) {
            return $user->hasRole('super-admin');
        }

        return true;
    }

    /**
     * Determine if the user can restore the order
     */
    public function restore(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('delete orders');
    }

    /**
     * Determine if the user can permanently delete the order
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Determine if the user can update order status
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('edit orders');
    }

    /**
     * Determine if the user can manage order items (add/remove/update)
     */
    public function manageItems(User $user, Order $order): bool
    {
        if (!$user->hasPermissionTo('edit orders')) {
            return false;
        }

        // Can't modify items in completed orders
        return !in_array($order->status, ['completed', 'delivered', 'cancelled']);
    }
}

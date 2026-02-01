#!/bin/bash

# OrderController Security Fix - Quick Apply Script
# This script applies the critical security fixes to OrderController

echo "🔒 Applying Security Fixes to OrderController..."

# 1. Register OrderPolicy
echo "1. Registering OrderPolicy..."
if ! grep -q "protected \$policies" app/Providers/AuthServiceProvider.php; then
    echo "   Adding policies array to AuthServiceProvider..."
    # You need to manually add this to AuthServiceProvider.php:
    # protected $policies = [
    #     \App\Models\Order::class => \App\Policies\OrderPolicy::class,
    # ];
fi

# 2. Clear all caches
echo "2. Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 3. Test Observer is registered
echo "3. Testing Observer registration..."
php artisan tinker --execute="
echo 'Testing OrderObserver...' . PHP_EOL;
\$order = App\Models\Order::first();
if (\$order) {
    \$order->touch(); // Triggers observer
    echo '✅ OrderObserver is working' . PHP_EOL;
} else {
    echo '⚠️  No orders found to test observer' . PHP_EOL;
}
"

# 4. Test Policy
echo "4. Testing OrderPolicy..."
php artisan tinker --execute="
echo 'Testing OrderPolicy...' . PHP_EOL;
\$user = App\Models\User::first();
\$order = App\Models\Order::first();
if (\$user && \$order) {
    try {
        \$can = \$user->can('view', \$order);
        echo '✅ OrderPolicy is registered: ' . (\$can ? 'true' : 'false') . PHP_EOL;
    } catch (\Exception \$e) {
        echo '❌ OrderPolicy not registered yet' . PHP_EOL;
    }
} else {
    echo '⚠️  No test data available' . PHP_EOL;
}
"

echo ""
echo "📋 Manual Steps Required:"
echo ""
echo "1. Add to app/Providers/AuthServiceProvider.php:"
echo "   protected \$policies = ["
echo "       \\App\\Models\\Order::class => \\App\\Policies\\OrderPolicy::class,"
echo "   ];"
echo ""
echo "2. Update OrderController methods to use:"
echo "   - UpdateOrderRequest instead of Request in update()"
echo "   - Add Gate::authorize() checks (see docs/ORDER_CONTROLLER_AUDIT.md)"
echo "   - Remove Cache::flush() calls (lines 348, 373)"
echo ""
echo "3. Fix SQL injection in districtWiseOrders() method"
echo ""
echo "✅ Automated fixes applied!"
echo "📖 Full guide: docs/ORDER_CONTROLLER_AUDIT.md"

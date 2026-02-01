<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class CheckRoutePermission
{
    /**
     * Routes that should be excluded from permission checks.
     * These routes are accessible to all authenticated users.
     */
    protected array $excludedRoutes = [
        'admin.profile.edit',
        'admin.profile.update',
        'admin.profile.destroy',
    ];

    /**
     * Map of route patterns to their required permissions.
     * If a route is not in this map, it will use the route name as the permission.
     */
    protected array $routePermissionMap = [
        // Orders - all order routes require admin.orders.index
        'admin.orders.*' => 'admin.orders.index',
        'admin.invoice.*' => 'admin.orders.index',

        // Products - product related routes
        'admin.products.*' => 'admin.products.index',
        'admin.products.create' => 'admin.products.create',
        'admin.products.store' => 'admin.products.create',
        'admin.products.edit' => 'admin.products.index',
        'admin.products.update' => 'admin.products.index',
        'admin.products.destroy' => 'admin.products.index',
        'admin.products.quick-edit' => 'admin.products.index',
        'admin.products.bulk-delete' => 'admin.products.index',
        'admin.upload' => 'admin.products.create',

        // Attributes
        'admin.attributes.*' => 'admin.attributes.index',

        // Categories
        'admin.categories.*' => 'admin.categories.index',

        // Brands
        'admin.brands.*' => 'admin.brands.index',

        // Inventory
        'admin.inventory.*' => 'admin.inventory.getAllProductsStock',
        'admin.reports.*' => 'admin.reports.generateReport',

        // Employees
        'admin.employee.*' => 'admin.employee.index',
        'admin.employee.salary.*' => 'admin.employee.salary.index',
        'admin.team-member.*' => 'admin.team-member.index',

        // Users & Roles
        'admin.users.*' => 'admin.users.index',
        'admin.roles.*' => 'admin.roles.index',

        // Accounting
        'admin.business-dashboard.*' => 'admin.business-dashboard.index',
        'admin.expenses.*' => 'admin.expenses.index',
        'admin.product-purchase-costs.*' => 'admin.product-purchase-costs.index',
        'admin.transaction-history.*' => 'admin.transaction-history.index',

        // Coupons
        'admin.coupons.*' => 'admin.coupons.index',

        // Campaigns
        'admin.campaigns.*' => 'admin.campaigns.index',

        // Corporate Clients
        'admin.corporate-clients.*' => 'admin.corporate-clients.index',

        // Landing Pages
        'admin.landing-pages.*' => 'admin.landing-pages.index',

        // Site Pages
        'admin.site-pages.*' => 'admin.site-pages.index',

        // Blogs
        'admin.blogs.*' => 'admin.blogs.index',

        // Reviews
        'admin.reviews.*' => 'admin.reviews.index',

        // Courier Settings
        'admin.courier.*' => 'admin.courier.settings.index',
        'admin.couriers.*' => 'admin.courier.settings.index',
        'admin.fraud-check.*' => 'admin.courier.settings.index',

        // Settings
        'admin.settings.*' => 'admin.settings.basicInformation',
        'admin.media.*' => 'admin.media.index',
        'admin.sliders.*' => 'admin.sliders.index',
        'admin.promotional-sliders.*' => 'admin.promotional-sliders.index',
        'admin.marketing-tools.*' => 'admin.marketing-tools.index',

        // POS
        'admin.pos.*' => 'admin.pos.index',

        // Dashboard
        'admin.dashboard.*' => 'admin.dashboard.index',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // If not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Check if user is admin (admins have all permissions)
        if ($this->isAdmin($user)) {
            return $next($request);
        }

        $routeName = Route::currentRouteName();

        // If no route name, skip permission check
        if (!$routeName) {
            return $next($request);
        }

        // Check if route is excluded
        if ($this->isExcludedRoute($routeName)) {
            return $next($request);
        }

        // Get the required permission for this route
        $permission = $this->getRequiredPermission($routeName);

        // If no permission required, allow access
        if (!$permission) {
            return $next($request);
        }

        // Check if user has the required permission
        try {
            if (!$user->hasPermissionTo($permission)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
                }

                abort(403, 'You do not have permission to access this resource.');
            }
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            // Permission doesn't exist in database - deny access by default
            // This can happen if new routes are added but seeder hasn't run
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Permission not configured. Please contact administrator.'], 403);
            }

            abort(403, 'Permission not configured. Please contact administrator.');
        }

        return $next($request);
    }

    /**
     * Check if the user is an admin.
     */
    protected function isAdmin($user): bool
    {
        // Check is_admin flag
        if ($user->is_admin ?? false) {
            return true;
        }

        // Check for admin or super-admin roles
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['admin', 'super-admin']);
        }

        return false;
    }

    /**
     * Check if the route is excluded from permission checks.
     */
    protected function isExcludedRoute(string $routeName): bool
    {
        return in_array($routeName, $this->excludedRoutes);
    }

    /**
     * Get the required permission for a route.
     */
    protected function getRequiredPermission(string $routeName): ?string
    {
        // First, check for exact match
        if (isset($this->routePermissionMap[$routeName])) {
            return $this->routePermissionMap[$routeName];
        }

        // Then, check for wildcard patterns
        foreach ($this->routePermissionMap as $pattern => $permission) {
            if ($this->matchesPattern($routeName, $pattern)) {
                return $permission;
            }
        }

        // If no mapping found, use the route name as the permission
        // This allows for fine-grained control when needed
        return $routeName;
    }

    /**
     * Check if a route name matches a pattern.
     */
    protected function matchesPattern(string $routeName, string $pattern): bool
    {
        // Convert wildcard pattern to regex
        $regex = str_replace('.', '\.', $pattern);
        $regex = str_replace('*', '.*', $regex);
        $regex = '/^' . $regex . '$/';

        return preg_match($regex, $routeName) === 1;
    }
}

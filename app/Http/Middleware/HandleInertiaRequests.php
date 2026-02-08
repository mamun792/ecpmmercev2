<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\Categories\CategoryService;
use App\Services\Cart\CartService;
use App\Services\Settings\SettingsService;
use App\Services\Wishlist\WishlistService;
use App\Services\Compare\CompareService;
use App\Http\Resources\Cart\CartResource;
use Illuminate\Support\Str;
use App\Services\Order\OrderInterface as OrderServiceInterface;
use App\Services\Inventory\InventoryService;

class HandleInertiaRequests extends Middleware
{
    protected $categoryService;
    protected $cartService;
    protected $settingsService;
    protected $wishlistService;
    protected $compareService;
    protected $orderService;
    protected $inventoryService;

    public function __construct(
        CategoryService $categoryService,
        CartService $cartService,
        SettingsService $settingsService,
        WishlistService $wishlistService,
        CompareService $compareService,
        OrderServiceInterface $orderService,
        InventoryService $inventoryService
    ) {
        $this->categoryService = $categoryService;
        $this->cartService = $cartService;
        $this->settingsService = $settingsService;
        $this->wishlistService = $wishlistService;
        $this->compareService = $compareService;
        $this->orderService = $orderService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Get or create session ID for cart (use cookie to persist across login/logout)
        $sessionId = $request->cookie('cart_session_id') ?? $request->session()->get('cart_session_id');
        if (!$sessionId) {
            $sessionId = 'anon-' . base64_encode(substr($request->userAgent() ?? 'unknown', 0, 20)) . '-' . Str::random(9);
        }
        // Store in both session and cookie
        $request->session()->put('cart_session_id', $sessionId);
        cookie()->queue('cart_session_id', $sessionId, 60 * 24 * 30); // 30 days

        // Get cart data
        $userId = $user ? $user->id : null;
        $cart = $this->cartService->getCart($userId, $sessionId);

        // Calculate cart count from items relationship
        $cartCount = $cart ? $cart->items->count() : 0;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    // expose permissions and roles as plain arrays for the frontend
                    'permissions' => $user ? $user->getAllPermissions()->pluck('name')->toArray() : [],
                    'roles' => $user ? $user->getRoleNames()->toArray() : [],
                    // useful flag: consider users with role 'admin' or 'super-admin' as full-access
                    'is_admin' => $user ? ($user->hasRole('admin') || $user->hasRole('super-admin')) : false,
                ] : null,
            ],
            // Share categories globally for navigation - hierarchical structure with children
            'categories' => fn () => $this->categoryService->getAllCategoriesForApi(),
            // Share cart data globally
            'cart' => $cart ? new CartResource($cart) : null,
            'cartCount' => $cartCount,
            'cartSessionId' => $sessionId,
            // Share wishlist and compare counts
            'wishlistCount' => $this->wishlistService->getWishlistCount($userId, $sessionId),
            'wishlistIds' => fn() => $this->wishlistService->getWishlist($userId, $sessionId)->pluck('id')->toArray(),
            'compareCount' => $this->compareService->getCompareCount($userId, $sessionId),
            'compareIds' => fn() => $this->compareService->getCompareIds($userId, $sessionId),
            // Share site settings globally
            'settings' => fn () => $this->settingsService->getSiteInfo(),
            'pages' => fn () => $this->settingsService->getAllPages(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            // Admin notifications (only visible to admin users)
            'adminNotifications' => fn () => ($user && ($user->hasRole('admin') || $user->hasRole('super-admin')))
                ? $this->orderService->adminNotifications()
                : ['count' => 0, 'notifications' => collect()],
            // Admin sidebar stats (only visible to admin users)
            'sidebarStats' => fn () => ($user && ($user->hasRole('admin') || $user->hasRole('super-admin') || $user->hasRole('manager')))
                ? array_merge(
                    $this->orderService->getSidebarStats(),
                    ['lowStockCount' => $this->inventoryService->getLowStockCount()]
                )
                : null,
        ];
    }
}

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Cart\CartController;
use App\Http\Controllers\Api\Order\OrderController;
use App\Http\Controllers\Api\Coupon\CouponController;
use App\Http\Controllers\Api\Wishlist\WishlistController;
use App\Http\Controllers\Api\Compare\CompareController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\Review\ReviewController;
use App\Http\Controllers\Api\CommonApi\CommonApiDataController;
use App\Http\Controllers\CheckStatusController;
use App\Http\Controllers\Api\Courier\CourierController;
use App\Http\Controllers\Admin\Courier\FraudcheckController;
use App\Http\Controllers\Api\Campaign\CampaignController;
use App\Http\Controllers\Admin\Inventory\InventoryController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Courier API routes
Route::prefix('couriers')->group(function () {
    // Pathao routes
    Route::prefix('pathao')->group(function () {
        Route::get('/cities', [App\Http\Controllers\Admin\Courier\CourierController::class, 'getAllCities']);
        Route::get('/zones/{cityId}', [App\Http\Controllers\Admin\Courier\CourierController::class, 'getZonesByCity']);
        Route::get('/areas/{zoneId}', [App\Http\Controllers\Admin\Courier\CourierController::class, 'getAreasByZone']);
    });

    // Create shipment route
    Route::post('/create-shipment', [App\Http\Controllers\Admin\Courier\CourierController::class, 'createShipment']);
});

    Route::post('/fraud-check', [FraudcheckController::class, 'fetchSuccessRate'])
        ->name('fraud-check.fetchSuccessRate');

// Inventory Management API Routes (Admin only)
Route::middleware(['auth:api', 'role:admin'])->prefix('inventory')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('api.inventory.index');
    Route::post('/adjust', [InventoryController::class, 'adjustStock'])->name('api.inventory.adjust');
    Route::post('/transfer', [InventoryController::class, 'transferStock'])->name('api.inventory.transfer');
    Route::get('/transactions', [InventoryController::class, 'getTransactions'])->name('api.inventory.transactions');
    Route::get('/alerts', [InventoryController::class, 'getLowStockAlerts'])->name('api.inventory.alerts');
    Route::put('/reorder-level', [InventoryController::class, 'updateReorderLevel'])->name('api.inventory.reorder-level');
    Route::post('/reports', [InventoryController::class, 'generateReport'])->name('api.inventory.reports');
    Route::post('/variation/toggle-status', [InventoryController::class, 'toggleVariationStatus'])->name('api.inventory.variation.toggle-status');
});


Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::group([
        'middleware' => 'auth:api'
    ], function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('update-profile', [AuthController::class, 'updateProfile']);
    });
});

Route::apiResource('coupons', CouponController::class);
Route::get('coupons/verify/{code}', [CouponController::class, 'verify']);
Route::post('coupons/apply', [CouponController::class, 'apply']);
Route::post('/apply-to-cart', [CouponController::class, 'applyToCart']);




Route::prefix('cart')->group(function () {
    Route::post('/add', [CartController::class, 'addToCart']);
    Route::get('/', [CartController::class, 'getCart']);
    Route::put('/update-quantity', [CartController::class, 'updateItemQuantity']);
    Route::delete('/remove', [CartController::class, 'destroy']);
    // cartitmen destroy
    Route::delete('/cartitem/destroy', [CartController::class, 'destroyCartItem']);

    Route::post('/apply-coupon', [CartController::class, 'applyCoupon']);
});

Route::prefix('wishlist')->group(function () {
    Route::post('/add', [WishlistController::class, 'addToWishlist']);
    Route::delete('/remove/{productId}', [WishlistController::class, 'removeFromWishlist']);
    Route::get('/', [WishlistController::class, 'getWishlist']);
});

Route::prefix('compare')->group(function () {
    Route::post('/add', [CompareController::class, 'addToCompare']);
    Route::delete('/remove/{productId}', [CompareController::class, 'removeFromCompare']);
    Route::get('/', [CompareController::class, 'getCompareList']);
    Route::delete('/clear', [CompareController::class, 'clearCompareList']);
});

Route::apiResource('orders', OrderController::class);

// Payment routes
Route::prefix('payment')->group(function () {
    Route::post('/initiate', [App\Http\Controllers\Api\Payment\PaymentController::class, 'initiate']);
    Route::post('/verify', [App\Http\Controllers\Api\Payment\PaymentController::class, 'verify']);
    Route::post('/callback', [App\Http\Controllers\Api\Payment\PaymentController::class, 'callback']);
    Route::post('/update-from-url', [App\Http\Controllers\Api\Payment\PaymentController::class, 'updateFromUrl']);
});


// Review routes
Route::prefix('reviews')->group(function () {
    Route::post('/', [ReviewController::class, 'store']);
    Route::get('/products/{productId}', [ReviewController::class, 'index']);
    Route::get('/can-review/{productId}', [ReviewController::class, 'canReview']);
});

// Admin review routes
Route::prefix('admin/reviews')->group(function () {
    Route::post('/', [ReviewController::class, 'storeAdmin']);
    Route::put('/{reviewId}/approve', [ReviewController::class, 'approve']);
    Route::get('/pending', [ReviewController::class, 'pending']);
});


Route::post('/license-check', [CheckStatusController::class, 'checkLicense']);


//campaigns
Route::get('/campaigns', [CampaignController::class, 'getActiveCampaigns']);





// fontend api

//products
Route::get('/all-products', [ProductController::class, 'getAllProducts']);
Route::get('/latest-products', [ProductController::class, 'latestProducts']);
Route::get('/flash-sales-products', [ProductController::class, 'flashSalesProducts']);
Route::get('/productswith-video', [ProductController::class, 'productswithVideo']);
Route::get('/category-with-products', [ProductController::class, 'categorywithproducts']);
Route::get('/product/{slug}', [ProductController::class, 'singleProduct']);
Route::get('/previous-price-products', [ProductController::class, 'previousPriceProducts']);

//products by remark
Route::get('/products-by-remark/{remark}', [ProductController::class, 'productsByRemark']);

Route::get('/promotional-sliders', [CommonApiDataController::class, 'promotionalSliders']);
Route::get('/corporate-logos', [CommonApiDataController::class, 'corparateLogo']);

Route::get('/landing-page/{slug}', [CommonApiDataController::class, 'landingPage']);

Route::get('/products/filter', [ProductController::class, 'filter']);
Route::get('/products/view-count/{id}', [ProductController::class, 'ProductViewCount']);

Route::get('/order-data/{order_number}', [CommonApiDataController::class, 'orderData']);

Route::get('/ordersbyuser/{user_id}', [CommonApiDataController::class, 'ordersByUser']);

//related products
Route::get('/related-products/{productId}', [ProductController::class, 'getRelatedProducts']);


//categories
Route::get('/all-categories', [CommonApiDataController::class, 'getAllCategories']);

//brands
Route::get('/all-brands', [CommonApiDataController::class, 'getAllBrands']);


Route::get('/all-attributes', [CommonApiDataController::class, 'getAllAttributes']);

//sliders
Route::get('/sliders', [CommonApiDataController::class, 'getAllSliders']);

Route::get('/site-infos', [CommonApiDataController::class, 'siteInfos']);


Route::get('/team-members', [CommonApiDataController::class, 'getTeamMembers']);

//pages
Route::get('/all-pages', [CommonApiDataController::class, 'getAllPages']);
Route::get('/page/{slug}', [CommonApiDataController::class, 'getPageBySlug']);

Route::prefix('courier')->group(function () {
    Route::post('orders', [CourierController::class, 'createOrder']);
    Route::post('orders/bulk', [CourierController::class, 'createBulkOrder']);
    Route::get('status/consignment/{consignmentId}', [CourierController::class, 'getStatusByConsignmentId']);
    Route::get('status/invoice/{invoice}', [CourierController::class, 'getStatusByInvoice']);
    Route::get('status/tracking/{trackingCode}', [CourierController::class, 'getStatusByTrackingCode']);

});

// Blog routes
Route::prefix('blogs')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\BlogController::class, 'index']);
    Route::get('/latest', [App\Http\Controllers\Api\BlogController::class, 'latest']);
    Route::get('/popular', [App\Http\Controllers\Api\BlogController::class, 'popular']);
    Route::get('/search', [App\Http\Controllers\Api\BlogController::class, 'search']);
    Route::get('/tag/{tag}', [App\Http\Controllers\Api\BlogController::class, 'byTag']);
    Route::get('/{slug}', [App\Http\Controllers\Api\BlogController::class, 'show']);
});



// API Version prefix
// Route::prefix('api/v1')->group(function () {
//     // Authentication routes
//     Route::prefix('auth')->group(function () {
//         Route::post('register', [AuthController::class, 'register']);
//         Route::post('login', [AuthController::class, 'login']);

//         Route::middleware('auth:api')->group(function () {
//             Route::get('me', [AuthController::class, 'me']);
//             Route::post('logout', [AuthController::class, 'logout']);
//             Route::post('refresh', [AuthController::class, 'refresh']);
//         });
//     });

//     // User routes
//     Route::middleware('auth:sanctum')->group(function () {
//         Route::get('user', function (Request $request) {
//             return $request->user();
//         });
//     });

//     // Coupon routes
//     Route::prefix('coupons')->group(function () {
//         Route::apiResource('', CouponController::class)->parameters(['' => 'coupon']);
//         Route::get('verify/{code}', [CouponController::class, 'verify']);
//         Route::post('apply', [CouponController::class, 'apply']);
//         Route::post('apply-to-cart', [CouponController::class, 'applyToCart']);
//         Route::post('{coupon_id}/products', [CouponController::class, 'storeProductCoupon']);
//     });

//     // Cart routes
//     Route::prefix('cart')->group(function () {
//         Route::post('add', [CartController::class, 'addToCart']);
//         Route::get('', [CartController::class, 'getCart']);
//         Route::put('update-quantity', [CartController::class, 'updateItemQuantity']);
//         Route::delete('remove', [CartController::class, 'destroy']);
//         Route::post('apply-coupon', [CartController::class, 'applyCoupon']);
//     });

//     // Order routes
//     Route::apiResource('orders', OrderController::class);
// });

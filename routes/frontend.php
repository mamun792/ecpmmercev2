<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\Home\HomeController;
use App\Http\Controllers\Frontend\Auth\AdminAuthController;
use App\Http\Controllers\Frontend\Auth\UserAuthController;
use App\Http\Controllers\Frontend\Auth\UserRegisterController;
use App\Http\Controllers\Frontend\User\UserDashboardController;
use App\Http\Controllers\Frontend\Product\ProductController;
use App\Http\Controllers\Frontend\Cart\CartController;
use App\Http\Controllers\Frontend\Order\OrderController;
use App\Http\Controllers\Frontend\Review\ReviewController; 
use App\Http\Controllers\Frontend\Wishlist\WishlistController;
use App\Http\Controllers\Frontend\Compare\CompareController;
use App\Http\Controllers\Frontend\Pages\PagesController;

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
|
| Here is where you can register frontend routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Home Page - Welcome
Route::get('/', [HomeController::class, 'index'])->name('home');

// Offers (Campaigns) page
use App\Http\Controllers\Frontend\Offer\OfferController;
Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');

// Products listing/filter page
Route::get('/products', [ProductController::class, 'index'])->name('products.index');

// Category page - redirects to products with category filter
Route::get('/category/{slug}', function ($slug) {
    return redirect()->route('products.index', ['category' => $slug]);
})->name('category.show');

// Search page - redirects to products with search filter
Route::get('/search', function (\Illuminate\Http\Request $request) {
    return redirect()->route('products.index', ['search' => $request->get('q', '')]);
})->name('search');

// Search suggestions (AJAX endpoint - returns JSON)
Route::get('/products/search-suggestions', [ProductController::class, 'searchSuggestions'])
    ->name('products.search-suggestions');

// // Single Product (frontend) - returns product JSON
// Route::get('/product/{slug}', [ProductController::class, 'singleProduct'])->name('product.single');

// Product details page (frontend view)
Route::get('/product/{slug}', [ProductController::class, 'detailsPage'])->name('product.details');

// Product Group details page
Route::get('/product-group/{slug}', [ProductController::class, 'productGroup'])->name('product-group.show');

// Cart routes
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('add', [CartController::class, 'addToCart'])->name('add');
    Route::post('update', [CartController::class, 'updateItemQuantity'])->name('update');
    Route::post('remove', [CartController::class, 'removeItem'])->name('remove');
    Route::post('clear', [CartController::class, 'clear'])->name('clear');
    Route::post('apply-coupon', [CartController::class, 'applyCoupon'])->name('apply-coupon');
});

// Order routes (Guest checkout allowed)
Route::prefix('order')->name('order.')->group(function () {
    Route::get('/checkout', [OrderController::class, 'create'])->name('create');
    Route::post('/', [OrderController::class, 'store'])->name('store');
    Route::get('/success/{order}', [OrderController::class, 'success'])->name('success');
    Route::get('/track', [OrderController::class, 'trackOrder'])->name('track');
});

// Review routes (supports both authenticated users and guests)
Route::prefix('reviews')->name('reviews.')->group(function () {
    Route::post('/store', [ReviewController::class, 'store'])
        ->name('store');
    
    Route::get('/can-review/{productId}', [ReviewController::class, 'canReview'])
        ->name('can-review');
});

// Wishlist routes
Route::prefix('wishlist')->name('wishlist.')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('index');
    Route::post('add', [WishlistController::class, 'add'])->name('add');
    Route::post('remove', [WishlistController::class, 'remove'])->name('remove');
});

// Compare routes
Route::prefix('compare')->name('compare.')->group(function () {
    Route::get('/', [CompareController::class, 'index'])->name('index');
    Route::post('add', [CompareController::class, 'add'])->name('add');
    Route::post('remove', [CompareController::class, 'remove'])->name('remove');
    Route::post('clear', [CompareController::class, 'clear'])->name('clear');
});


/*
|--------------------------------------------------------------------------
| Guest Routes (Not Authenticated)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // User Login Routes
    Route::get('/login', [UserAuthController::class, 'create'])->name('login');
    Route::post('/login', [UserAuthController::class, 'store']);
    
    // User Register Routes
    Route::get('/register', [UserRegisterController::class, 'create'])->name('register');
    Route::post('/register', [UserRegisterController::class, 'store']);
    
    // Admin Login Routes
    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'store']);
});


/*
|--------------------------------------------------------------------------
| User Dashboard Routes (Authenticated Users Only)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
    Route::put('/profile', [UserDashboardController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [UserDashboardController::class, 'updatePassword'])->name('password.update');
});

// Logout Route (for both admin and user)
Route::post('/logout', [UserAuthController::class, 'destroy'])->middleware('auth')->name('logout');

// Pre-Order Lead Routes
Route::get('/pre-order-lead', [App\Http\Controllers\Frontend\PreOrderLeadController::class, 'showForm'])->name('pre-order-lead.form');
Route::post('/pre-order-lead', [App\Http\Controllers\Frontend\PreOrderLeadController::class, 'store'])->name('pre-order-lead.store');

// Static site pages (dynamic pages)
Route::get('/page/{page}', [PagesController::class, 'show'])->name('pages.show');

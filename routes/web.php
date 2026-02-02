<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CartController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\Pos\PosController;
use App\Http\Controllers\Admin\Brand\BrandController;
use App\Http\Controllers\Admin\Media\MediaController;
use App\Http\Controllers\Admin\Order\OrderController;
use App\Http\Controllers\Admin\Coupon\CouponController;
use App\Http\Controllers\Admin\Slider\SliderController;
use App\Http\Middleware\RedirectIfAuthenticatedOrGuest;
use App\Http\Controllers\Admin\Courier\CourierController;
use App\Http\Controllers\Admin\Product\ProductController;
use App\Http\Controllers\Admin\Category\CategoryController;
use App\Http\Controllers\Admin\Attribute\AttributeController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Marketing\MarketingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\GeneralSetting\GeneralSettingController;
use App\Http\Controllers\Admin\Inventory\InventoryManagementController;
use App\Http\Controllers\Admin\Inventory\InventoryController;
use App\Http\Controllers\Admin\Courier\FraudcheckController;
use App\Http\Controllers\Admin\SitePage\SitePageController;
use App\Http\Controllers\Admin\LandingPage\LandingPageController;
use App\Http\Controllers\Admin\Employee\EmployeeController;
use App\Http\Controllers\Admin\Employee\EmployeeSalaryController;
use App\Http\Controllers\Admin\TeamMember\TeamMemberController;
use App\Http\Controllers\Admin\Expenses\ExpensesController;
use App\Http\Controllers\Admin\BusinessDashboard\BusinessDashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PromotionalSlider\PromotionalSliderController;
use App\Http\Controllers\Admin\Review\ReviewController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\Campaign\CampaignController;
use App\Http\Controllers\Admin\CorporateClient\CorporateClientController;
use App\Http\Controllers\Admin\Banner\BannerController;
use App\Http\Controllers\Admin\ProductGroup\ProductGroupController;

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
| All frontend routes are defined in routes/frontend.php
*/
require __DIR__ . '/frontend.php';



//route group for authenticated user
Route::group(['middleware' => ['auth', 'check.route.permission'], 'prefix' => 'admin', 'as' => 'admin.'], function () {


    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');


    // profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    // User and Role Management
    Route::resource('users', UserController::class);
    Route::post('/users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles.update');
    // Use the Spatie Permission middleware by full class name to avoid alias resolution issues
    Route::resource('roles', RoleController::class)->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':manage_user_roles');



    //pos
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');

    // oders
    Route::resource('/orders', OrderController::class);
    Route::get('/incomplete-orders', [OrderController::class, 'incompleteOrders'])->name('orders.incomplete');
    Route::get('/order-map', [OrderController::class, 'districtWiseOrders'])->name('orders.map');

    Route::get('/invoice/{order}/download', [\App\Http\Controllers\Admin\Order\OrderInvoiceController::class, 'download'])->name('invoice.download');
    Route::post('/bulk-invoice/download', [\App\Http\Controllers\Admin\Order\OrderInvoiceController::class, 'bulkDownload'])->name('invoice.bulk-download');
    Route::get('/bulk-invoice/print', [\App\Http\Controllers\Admin\Order\OrderInvoiceController::class, 'bulkPrint'])->name('invoice.bulk-print');
    // update new order



    Route::put('/orders/{orderId}/courier-details', [OrderController::class, 'updateCourierDetails']);
    Route::post('/couriers/pathao/shipment', [CourierController::class, 'createShipment'])->name('couriers.pathao.shipment');
    Route::post('/couriers/pathao/shipments/bulk', [CourierController::class, 'createBulkShipment']);
    Route::get('/courier/settings', [CourierController::class, 'index'])->name('courier.settings.index');
    Route::post('/courier/settings', [CourierController::class, 'storeOrUpdate'])->name('courier.settings.store');

    //getAllProductsStock
    Route::get('/getAllProductsStock', [InventoryManagementController::class, 'getAllProductsStock'])->name('inventory.getAllProductsStock');

    // Enterprise Inventory Management
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::post('/adjust', [InventoryController::class, 'adjustStock'])->name('adjust');
        Route::post('/transfer', [InventoryController::class, 'transferStock'])->name('transfer');
        Route::get('/transactions', [InventoryController::class, 'getTransactions'])->name('transactions');
        Route::get('/alerts', [InventoryController::class, 'getLowStockAlerts'])->name('alerts');
        Route::put('/reorder-level', [InventoryController::class, 'updateReorderLevel'])->name('reorder-level');
        Route::post('/reports', [InventoryController::class, 'generateReport'])->name('reports');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        // Inventory Report V2 (Big Tech Style)
        Route::get('/inventory', [\App\Http\Controllers\Admin\Reports\InventoryReportController::class, 'index'])->name('inventory.v2');
        Route::get('/inventory/export-pdf', [\App\Http\Controllers\Admin\Reports\InventoryReportController::class, 'exportPDF'])->name('inventory.export-pdf');
        Route::get('/inventory/export-csv', [\App\Http\Controllers\Admin\Reports\InventoryReportController::class, 'exportCSV'])->name('inventory.export-csv');

        // Revenue Reports
        Route::get('/revenue', [\App\Http\Controllers\Admin\Reports\RevenueReportController::class, 'dashboard'])->name('revenue.dashboard');
        Route::get('/revenue/monthly', [\App\Http\Controllers\Admin\Reports\RevenueReportController::class, 'getMonthlyRevenue'])->name('revenue.monthly');
        Route::get('/revenue/products', [\App\Http\Controllers\Admin\Reports\RevenueReportController::class, 'getTopProducts'])->name('revenue.products');
        Route::get('/revenue/customers', [\App\Http\Controllers\Admin\Reports\RevenueReportController::class, 'getTopCustomers'])->name('revenue.customers');
        Route::get('/revenue/export', [\App\Http\Controllers\Admin\Reports\RevenueReportController::class, 'export'])->name('revenue.export');

        // Legacy inventory (Old version - for backward compatibility)
        Route::get('/legacy', [InventoryManagementController::class, 'generateReport'])->name('generateReport');
    });

    Route::resource('products', ProductController::class);
    Route::post('/products/{id}/quick-edit', [ProductController::class, 'quickEdit'])->name('products.quick-edit');

    // Attributes - Big Tech Style (No Delete, Only Status Toggle)
    Route::resource('attributes', AttributeController::class)->except(['destroy']);
    Route::put('/attributes/{id}/toggle-status', [AttributeController::class, 'toggleStatus'])->name('attributes.toggle-status');

    Route::post('/products/bulk-delete', [ProductController::class, 'bulkDeleteProducts'])->name('products.bulk-delete');
    Route::post('/products/{slug}/restore', [ProductController::class, 'restore'])->name('products.restore');

    // categories
    Route::resource('categories', CategoryController::class);
    Route::put('/categories/{id}/status', [CategoryController::class, 'updateStatus'])->name('categories.updateStatus');
    Route::put('/categories/{id}/order', [CategoryController::class, 'updateOrder'])->name('categories.updateOrder');

    Route::resource('brands', BrandController::class);
    Route::put('/brands/{id}/status', [BrandController::class, 'updateStatus'])->name('brands.updateStatus');

    // Product Groups
    Route::resource('product-groups', ProductGroupController::class);
    Route::put('product-groups/{productGroup}/status', [ProductGroupController::class, 'toggleStatus'])->name('product-groups.status');
    Route::get('product-groups-search-products', [ProductGroupController::class, 'searchProducts'])->name('product-groups.search-products');

    // copon
    Route::resource('coupons', CouponController::class);

    Route::post('coupons/{coupon_id}/products', [CouponController::class, 'storeProductCoupon'])->name('coupons.storeProductCoupon');

    Route::get('/assign-coupon-to-products/{coupon_id}', [CouponController::class, 'couponAddedToProduct'])->name('coupons.addedToProducts');


        // campaigns
    Route::get('campaigns/search-products', [CampaignController::class, 'searchProducts'])->name('campaigns.search-products');
    Route::resource('campaigns', CampaignController::class);

    // corporate clients
    Route::resource('corporate-clients', CorporateClientController::class);


        // banners
    Route::resource('banners', BannerController::class)->only(['index', 'store', 'update']);
    Route::delete('/banners/random-banner', [BannerController::class, 'deleteRandomBanner']);
    Route::delete('/banners/delete-banner', [BannerController::class, 'deleteBanner']);
    Route::delete('/banners/random-banner-image', [BannerController::class, 'deleteRandomBannerImage'])->name('admin.banners.delete-random-image');


    // GeneralSetting
    Route::get('/social-links', [GeneralSettingController::class, 'socialLinks'])->name('settings.socialLinks');
    Route::get('/basic-information', [GeneralSettingController::class, 'basicInformation'])->name('settings.basicInformation');
    Route::post('/settings-create', [GeneralSettingController::class, 'store'])->name('settings.store');

    // System Maintenance
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\GeneralSetting\MaintenanceController::class, 'index'])->name('index');
        Route::post('/toggle', [App\Http\Controllers\Admin\GeneralSetting\MaintenanceController::class, 'toggleMaintenance'])->name('toggle');
        Route::post('/clear-cache', [App\Http\Controllers\Admin\GeneralSetting\MaintenanceController::class, 'clearCache'])->name('clear-cache');
        Route::get('/backup', [App\Http\Controllers\Admin\GeneralSetting\MaintenanceController::class, 'databaseBackup'])->name('backup');
    });


    Route::post('/upload', [ProductController::class, 'productDescriptionImageUpload'])->name('upload');

    // slider
    Route::resource('sliders', SliderController::class);

    Route::resource('promotional-sliders', PromotionalSliderController::class);


        // pages
    Route::resource('site-pages', SitePageController::class);
    Route::put('/site-pages/{id}/status', [SitePageController::class, 'updateStatus'])->name('site-pages.updateStatus');

    // media
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::put('/media', [MediaController::class, 'store'])->name('media.update');


    Route::get('/marketing-tools', [MarketingController::class, 'index'])->name('marketing-tools.index');
    Route::post('/marketing-tools', [MarketingController::class, 'store'])->name('marketing-tools.store');
    Route::delete('/marketing-tools/{marketingTool}', [MarketingController::class, 'destroy'])
    ->name('marketing-tools.destroy');



    //fraud check
    Route::post('/fraud-check', [FraudcheckController::class, 'fetchSuccessRate'])
        ->name('fraud-check.fetchSuccessRate');


    Route::resource('landing-pages', LandingPageController::class);

    // Employee management
    Route::resource('employee', EmployeeController::class);
    Route::post('employee/{employee}/payment', [EmployeeController::class, 'makePayment'])->name('employee.payment');

    // Team Member management
    Route::resource('team-member', TeamMemberController::class);

    // Employee salaries
    Route::get('employee-salaries', [EmployeeSalaryController::class, 'index'])->name('employee.salary.index');
    Route::post('employee-salaries', [EmployeeSalaryController::class, 'store'])->name('employee.salary.store');

    // Expenses
    Route::resource('expenses', ExpensesController::class);

    // Product Purchase Costs
    Route::resource('product-purchase-costs', ExpensesController::class);

    // Business Dashboard
    Route::get('/business-dashboard', [BusinessDashboardController::class, 'index'])->name('business-dashboard.index');

    // Reviews Management
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::put('/reviews/{reviewId}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::delete('/reviews/{reviewId}', [ReviewController::class, 'destroy'])->name('reviews.destroy');





    // Blog Management
    Route::resource('blogs', BlogController::class);

    // Transaction History
    Route::get('/transaction-history', [App\Http\Controllers\Admin\TransactionHistoryController::class, 'index'])->name('transaction-history.index');
    Route::get('/transaction-history/export-csv', [App\Http\Controllers\Admin\TransactionHistoryController::class, 'exportCsv'])->name('transaction-history.export-csv');
    Route::get('/transaction-history/export-pdf', [App\Http\Controllers\Admin\TransactionHistoryController::class, 'exportPdf'])->name('transaction-history.export-pdf');

});


// route group for guest user
Route::put('/orders/{orderId}/status', [OrderController::class, 'OderupdateStatus']);




Route::post('/orders/{orderId}/update', [OrderController::class, 'updateNewOrders']);

Route::post('/orders/{orderId}/basic-info', [OrderController::class, 'updateBasicInfo']);

// Quick update admin notes
Route::put('/admin/orders/{orderId}/admin-notes', [OrderController::class, 'updateAdminNotes'])->name('admin.orders.update-notes');

Route::put('/orders/{order}/items/{item}', [\App\Http\Controllers\Admin\Order\OrderItemController::class, 'updateQuantity'])->name('orders.items.update');

Route::delete('/orders/{order}/items/{item}', [\App\Http\Controllers\Admin\Order\OrderItemController::class, 'destroy'])->name('orders.items.destroy');

// Pre-Order Lead Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/leads', [App\Http\Controllers\Admin\PreOrderLeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{id}', [App\Http\Controllers\Admin\PreOrderLeadController::class, 'show'])->name('leads.show');
    Route::patch('/leads/{id}/status', [App\Http\Controllers\Admin\PreOrderLeadController::class, 'updateStatus'])->name('leads.update-status');
    Route::delete('/leads/{id}', [App\Http\Controllers\Admin\PreOrderLeadController::class, 'destroy'])->name('leads.destroy');

    // Admin notifications listing
    Route::get('/notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
});

require __DIR__ . '/auth.php';

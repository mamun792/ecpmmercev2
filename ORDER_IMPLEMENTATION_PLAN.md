# Frontend Order Implementation Plan

> **Status: ✅ IMPLEMENTED**
> All features documented below have been implemented and are ready for testing.

## 1. Architecture Analysis & Current State

The project follows a **Monolithic Architecture** using **Laravel 11** for the backend and **Inertia.js with Vue 3** for the frontend.

*   **Service Layer**: The `OrderService` (`App\Services\Order\OrderService`) centralizes all order creation logic (`createOrder` method). This ensures consistency between API and Frontend orders.
*   **Existing Controllers**:
    *   `App\Http\Controllers\Api\Order\OrderController`: Handles API orders.
    *   `App\Http\Controllers\Frontend\Cart\CartController`: Handles cart operations (returning `Frontend/Cart/Index`).
*   **Goal**: Create a dedicated Frontend Order flow that reuses the robust `OrderService`.

---

## 2. Backend Implementation

You need to create a new controller specifically for the Frontend (Inertia) flow. This controller will handle the checkout view rendering and the order submission.

### 2.1. Create Controller

**File:** `app/Http/Controllers/Frontend/Order/OrderController.php`

```php
<?php

namespace App\Http\Controllers\Frontend\Order;

use App\Http\Controllers\Controller;
use App\Services\Order\OrderInterface;
use App\Services\Cart\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $orderService;
    protected $cartService;

    public function __construct(OrderInterface $orderService, CartService $cartService)
    {
        $this->orderService = $orderService;
        $this->cartService = $cartService;
    }

    /**
     * Show the Checkout Page
     */
    public function create(Request $request)
    {
        // 1. Retrieve current cart items to display on checkout
        $userId = auth()->id();
        $sessionId = $request->cookie('cart_session_id'); // Or from session
        
        $cart = $this->cartService->getCart($userId, $sessionId);

        // If cart is empty, redirect back to cart or shop
        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        return Inertia::render('Frontend/Order/Checkout', [
            'cart' => $cart,
            'user' => $request->user(), // Pre-fill data if logged in
        ]);
    }

    /**
     * Store the Order
     */
    public function store(Request $request)
    {
        // 1. Validation (Basic request validation)
        $request->validate([
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'shipping_address' => 'required|string',
            'payment_method' => 'required|string',
            'items' => 'required|array|min:1',
        ]);

        try {
            // 2. Reuse OrderService to create the order
            // The service handles complex logic like stock check, courier validation, etc.
            $order = $this->orderService->createOrder($request->all());

            // 3. Clear Cart (Optional - depends if Service does it. 
            // Usually Service might NOT clear session cart automatically if it expects generic data)
            // If OrderService doesn't clear the cart, you might need to call $this->cartService->clear($userId, $sessionId);

            // 4. Redirect to Success Page
            return redirect()->route('order.success', ['order' => $order->order_number]);

        } catch (\Exception $e) {
            Log::error('Order Creation Failed: ' . $e->getMessage());
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Show Success Page
     */
    public function success($orderNumber)
    {
        // Fetch full order data with relationships for the view
        $orderData = \App\Models\Order::where('order_number', $orderNumber)
            ->with(['items', 'items.product', 'items.productVariation', 'items.productVariation.attributes.value.attribute', 'items.productVariation.attributes.value'])
            ->firstOrFail();

        return Inertia::render('Frontend/Order/Success', [
             'order' => $orderData
        ]);
    }
}
```

---

## 3. Routing

Register the new routes in the frontend route file.

**File:** `routes/frontend.php`

Add this inside your existing routes structure (outside of `auth` middleware if guest checkout is allowed):

```php
use App\Http\Controllers\Frontend\Order\OrderController;

// ... existing routes ...

Route::prefix('order')->name('order.')->group(function () {
    // Show Checkout Form
    Route::get('/checkout', [OrderController::class, 'create'])->name('create');
    
    // Place Order
    Route::post('/', [OrderController::class, 'store'])->name('store');
    
    // Order Success Page
    Route::get('/success/{order}', [OrderController::class, 'success'])->name('success');
});
```

---

## 4. Frontend (Inertia/Vue) Implementation

Create the directory: `resources/js/Pages/Frontend/Order/`

### 4.1. Checkout Page (`Checkout.vue`)

This page should construct the payload matching your JSON structure.

**File:** `resources/js/Pages/Frontend/Order/Checkout.vue`

**Key Logic:**
*   Receive `cart` as a prop.
*   **Dynamic Shipping Cost**: Access the global settings shared via `HandleInertiaRequests` (available in `$page.props.settings`) to get `shipping_charge_inside_dhaka` and `shipping_charge_outside_dhaka`.
*   Watch the `area` field to update `shipping_cost` dynamically.
*   Map `cart.items` to the `items` array required by the payload (`product_id`, `quantity`, etc.).
*   **Incomplete Orders**: Ensure the checkout process handles the creation of orders. If a user abandons payment (for online methods), the order usually remains as "incomplete" or "pending".

```javascript
import { usePage } from '@inertiajs/vue3';
import { computed, watch, reactive } from 'vue';

const page = usePage();
const settings = page.props.settings; // Access global settings

// Payload Structure Construction in submit()
const form = useForm({
    user_id: props.user ? props.user.id : null,
    session_id: getCookie('cart_session_id'), // Helper to get cookie
    customer_name: props.user ? props.user.name : '',
    customer_phone: props.user ? props.user.phone : '',
    shipping_address: props.user ? props.user.address : '',
    shipping_cost: 0, 
    area: 'inside_dhaka',
    customer_notes: '',
    payment_method: 'cod',
    payment_status: 'unpaid',
    items: props.cart.items.map(item => ({
        product_id: item.product_id,
        product_variation_id: item.product_variation_id, // Null if none
        quantity: item.quantity
    }))
});

// Dynamic Shipping Logic
watch(() => form.area, (newArea) => {
    if (newArea === 'inside_dhaka') {
        form.shipping_cost = parseFloat(settings.shipping_charge_inside_dhaka || 60);
    } else {
        form.shipping_cost = parseFloat(settings.shipping_charge_outside_dhaka || 120);
    }
}, { immediate: true });

// Incomplete Order Trigger (On Phone Number Entry)
// Backend is already configured to handle 'incomplete' status.
watch(() => form.customer_phone, (newPhone) => {
    // Validate: Must be 11 digits and start with '01' (Bangladesh format)
    const isValidBDPhone = /^01[3-9]\d{8}$/.test(newPhone);
    
    if (isValidBDPhone) { 
        // Construct payload for incomplete order
        const incompletePayload = {
            ...form.data(),
            status: 'incomplete',
            // Default/Fallback values to ensure backend acceptance
            shipping_address: form.shipping_address || 'incomplete address', 
            area: form.area || '',
            shipping_cost: form.shipping_cost || 0
        };

        // Send background request using axios (avoiding Inertia visit to prevent page reload/validation UI interruptions)
        // Ensure axios is imported: import axios from 'axios';
        axios.post(route('order.store'), incompletePayload)
            .then(res => console.log('Incomplete order tracked successfully'))
            .catch(err => console.error('Tracking failed', err));
    }
});
```

### 4.2. Success Page (`Success.vue`)

**File:** `resources/js/Pages/Frontend/Order/Success.vue`

**Design Requirements:** "Professional Big Tech Style" (Clean, centered, reassuring).
**Data:** Receives the full `$order` object.

```vue
<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border border-gray-100">
      
      <!-- Success Icon -->
      <div class="flex flex-col items-center">
        <!-- ... icon ... -->
        
        <h2 class="mt-2 text-3xl font-extrabold text-gray-900 text-center">
          Order Placed Successfully!
        </h2>
        <!-- ... -->
      </div>

      <!-- Order Details -->
      <div class="border-t border-b border-gray-200 py-6">
        <div class="flex justify-between text-sm">
          <span class="text-gray-500">Order Number:</span>
          <span class="font-medium text-gray-900">{{ order.order_number }}</span>
        </div>
         <div class="flex justify-between text-sm mt-2">
          <span class="text-gray-500">Total:</span>
          <span class="font-medium text-gray-900">{{ order.total }}</span>
        </div>
        <!-- Loop through order.items to show product summary if needed -->
      </div>

      <!-- ... Buttons ... -->
      
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    order: Object
});
</script>
```

---

## 6. User Dashboard & Profile Improvements (Documentation Only)

### 6.1. Professional Dashboard Design
*   **Layout**: Sidebar navigation (Orders, Profile, Password, Logout) + Main Content Area.
*   **Style**: Clean typography, card-based layout for order history using Tailwind CSS.

### 6.2. Order History Logic
*   **Goal**: Display user's order history, **excluding incomplete orders**.
*   **Controller**: `App\Http\Controllers\Frontend\User\UserDashboardController`
*   **Query**:
    ```php
    public function index(Request $request)
    {
        $user = $request->user();
        
        $orders = \App\Models\Order::where('user_id', $user->id)
      Backend Readiness**: The backend `OrderService` is already implemented to handle orders with `"status": "incomplete"`.
*   **Frontend Trigger**: In the `Checkout.vue` page, an event listener watches the **Customer Phone** field. 
    *   **Logic**: The system checks if the input matches a **valid 11-digit Bangladesh phone number** (starts with `01`, 11 digits total).
    *   **Action**: Once matched, an API call is triggered immediately to create an incomplete order.
*   **Payload Example**:
    ```json
    {
      "user_id": null,
      "session_id": "anon-TW96aWxsYS81LjAg-t8yag6t",
      "customer_name": "samrat",
      "customer_phone": "01887573015",
      "shipping_address": "incomplete address",
      "shipping_cost": 0,
      "area": "",
      "payment_method": "cod",
      "payment_status": "unpaid",
      "status": "incomplete",
      "items": [{"product_id":9,"product_variation_id":null,"quantity":1}]
    }
    ```
*   **Dashboard Visibility**: These incomplete orders must be excluded from the User Dashboard order list (as defined in section 6.2
            ->paginate(10);

        return Inertia::render('Frontend/User/Dashboard', [
            'orders' => $orders,
            'user' => $user
        ]);
    }
    ```

### 6.3. Profile & Security
1.  **Edit Profile**:
    *   Allow users to update Name, Email, Phone, and Address.
    *   Form should pre-fill with current user data.
2.  **Change Password**:
    *   Dedicated tab/section.
    *   Fields: `Current Password`, `New Password`, `Confirm Password`.
    *   Validation: Ensure current password matches before updating.

### 6.4. Incomplete Order Handling
*   **Checkout**: Ideally, when a user lands on checkout or attempts payment, an order might be created with status `incomplete`.
*   **Dashboard**: As specified above, these should strictly be hidden from the user's main order list to avoid clutter.
*   **Recovery**: (Optional Future Feature) Send emails for incomplete orders (Abandoned Cart recovery).


## 5. Summary of Workflow

1.  User goes to `cart.index` (existing).
2.  User clicks "Proceed to Checkout" -> Redirects to `order.create` (`/order/checkout`).
3.  `Frontend\Order\OrderController@create` fetches cart data from `CartService` and renders `Checkout.vue`.
4.  `Checkout.vue` collects user address/phone and combines it with cart items to match the **Order Payload**.
5.  Form submits POST to `order.store` (`/order`).
6.  `Frontend\Order\OrderController@store` passes data to `OrderService->createOrder()`.
7.  On success, redirect to `order.success`.

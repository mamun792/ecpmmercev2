# E-Commerce API Documentation

## Overview

This documentation provides details for the E-Commerce REST API, which supports cart management, coupon functionality, order processing, and user authentication.

**Base URL**: `http://127.0.0.1:8000/api`

## Authentication

Most endpoints require authentication using JWT tokens.

### Register User

Create a new user account.

```
POST /auth/register
```

**Request Body:**
```json
{
  "name": "User Name",
  "email": "user@example.com",
  "password": "password",
  "password_confirmation": "password",
  "phone_number": "1234567890"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com",
      "phone_number": "1234567890"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

### Login

Authenticate a user and receive an access token.

```
POST /auth/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

## Cart Management

### Add Item to Cart

Add a product to the user's shopping cart.

```
POST /cart/add
```

**Request Body:**
```json
{
  "product_id": 1,
  "user_id": 1,
  "quantity": 1,
  "options": {
    "gift_wrap": true,
    "message": "Happy Birthday!"
  }
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Item added to cart",
  "data": {
    "cart_item": {
      "id": 15,
      "user_id": 1,
      "product_id": 1,
      "quantity": 1,
      "options": {
        "gift_wrap": true,
        "message": "Happy Birthday!"
      },
      "created_at": "2025-04-12T10:15:30Z",
      "updated_at": "2025-04-12T10:15:30Z"
    }
  }
}
```

### Get Cart Contents

Retrieve all items in the user's cart.

```
GET /cart
```

**Request body:**
```
{
    "user_id":1  //session_id
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "items": [
      {
        "id": 11,
        "product_id": 1,
        "product_name": "Product Name",
        "price": 29.99,
        "quantity": 2,
        "options": {
          "gift_wrap": true,
          "message": "Happy Birthday!"
        },
        "subtotal": 59.98
      },
      {
        "id": 12,
        "product_id": 2,
        "product_name": "Another Product",
        "price": 15.99,
        "quantity": 1,
        "options": {},
        "subtotal": 15.99
      }
    ],
    "total": 75.97
  }
}
```

### Update Cart Item Quantity

Update the quantity of an item in the cart.

```
PUT /cart/update-quantity
```

**Request Body:**
```json
{
   // "user_id":1, // session_id
   "session_id":"vbb222",
  "item_id": 14,
  "quantity": 1
}

```

**Response:**
```json
{
  "status": "success",
  "message": "Cart item quantity updated",
  "data": {
    "item": {
      "id": 15,
      "quantity": 1,
      "subtotal": 29.99
    },
    "cart_total": 75.97
  }
}
```

### Remove Item from Cart

Remove an item from the cart.

```
DELETE /cart/remove
```

**Request Body:**
```json
{
  "session_id": "5f495732-e0a5-441b-a52a-445d7c389d58"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Item removed from cart",
  "data": {
    "cart_total": 59.98
  }
}
```

## Coupon Management

### Create Coupon

Create a new coupon code.

```
POST /coupons
```
if festival then this 
**Request Body:**
```json
  "name": "Eid Festival Discount",
  "discount_value": 15.00,
  "discount_type": "percentage",
  "max_uses": 1000,
  "start_date": "2025-04-23T00:00:00",
  "expiry_date": "2025-04-30T23:59:59",
  "is_active": true,
  "festival_name": "Eid",
  "apply_to_all_products": true
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Coupon created successfully",
  "data": {
    "id": 1,
    "code": "WINTER25",
    "name": "Winter 25% Off",
    "discount_value": 25,
    "discount_type": "percentage",
    "max_uses": 200,
    "uses_count": 0,
    "start_date": "2025-12-01T00:00:00Z",
    "expiry_date": "2025-12-31T00:00:00Z",
    "is_active": true,
    "created_at": "2025-04-12T10:30:45Z",
    "updated_at": "2025-04-12T10:30:45Z"
  }
}

or
**Request Body:**
```json
  "name": "Eid Festival Discount",
  "discount_value": 15.00,
  "discount_type": "percentage",
  "max_uses": 1000,
  "start_date": "2025-04-23T00:00:00",
  "expiry_date": "2025-04-30T23:59:59",
  "is_active": true,

}
```

### Get All Coupons

Retrieve all available coupons.

```
GET /coupons
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "coupons": [
      {
        "id": 1,
        "code": "WINTER25",
        "name": "Winter 25% Off",
        "discount_value": 25,
        "discount_type": "percentage",
        "max_uses": 200,
        "uses_count": 0,
        "start_date": "2025-12-01T00:00:00Z",
        "expiry_date": "2025-12-31T00:00:00Z",
        "is_active": true
      }
    ]
  }
}
```

### Apply Coupon to Cart

Apply a coupon code to items in the cart.

```
POST /apply-to-cart
```

**Request Body:**
```json
{
  "code": "WINTER25",
  "cart_ids": [11, 12]
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Coupon applied successfully",
  "data": {
    "original_total": 75.97,
    "discount_amount": 18.99,
    "final_total": 56.98,
    "items": [
      {
        "id": 11,
        "product_name": "Product Name",
        "original_price": 29.99,
        "discounted_price": 22.49,
        "quantity": 2,
        "subtotal": 44.98
      },
      {
        "id": 12,
        "product_name": "Another Product",
        "original_price": 15.99,
        "discounted_price": 11.99,
        "quantity": 1,
        "subtotal": 11.99
      }
    ]
  }
}
```

### Associate Products with Coupon

Link specific products to a coupon.

```
POST /coupons/{coupon_id}/products
```

**Example:**
```
POST /coupons/1/products
```

**Request Body:**
```json
{
  "product_ids": [1, 2]
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Products associated with coupon successfully",
  "data": {
    "coupon_id": 1,
    "products": [
      {
        "id": 1,
        "name": "Product Name"
      },
      {
        "id": 2,
        "name": "Another Product"
      }
    ]
  }
}
```

## Order Processing

### Create Order

Create a new order from cart items.

```
POST /orders
```

**Request Body:**
```json
{
  "user_id": 1,
  "customer_email": "customer@example.com",
  "customer_name": "John Doe",
  "shipping_address": "123 Main Street, City, Country",
  "shipping_cost": 5.00,
  "payment_method": "credit_card",
  "payment_status": "paid",
  "items": [
    {
      "product_id": 2,
      "product_variation_id": 2,
      "quantity": 2,
      "coupon_code": "WINTER25",
      "discount_type": "fixed",
      "discount_amount": 25
    },
    {
      "product_id": 1,
      "quantity": 1
    }
  ]
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Order created successfully",
  "data": {
    "order": {
      "id": 1,
      "order_number": "ORD-2025041200001",
      "user_id": 1,
      "customer_email": "customer@example.com",
      "customer_name": "John Doe",
      "shipping_address": "123 Main Street, City, Country",
      "shipping_cost": 5.00,
      "subtotal": 75.97,
      "discount": 25.00,
      "total": 55.97,
      "payment_method": "credit_card",
      "payment_status": "paid",
      "status": "processing",
      "created_at": "2025-04-12T11:20:15Z",
      "updated_at": "2025-04-12T11:20:15Z",
      "items": [
        {
          "product_id": 2,
          "product_name": "Another Product",
          "quantity": 2,
          "unit_price": 15.99,
          "discount_amount": 25.00,
          "subtotal": 6.98
        },
        {
          "product_id": 1,
          "product_name": "Product Name",
          "quantity": 1,
          "unit_price": 29.99,
          "discount_amount": 0,
          "subtotal": 29.99
        }
      ]
    }
  }
}
```

## Error Handling

All endpoints return appropriate HTTP status codes:

- `200 OK`: Request succeeded
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Authentication failed
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `500 Internal Server Error`: Server-side error

Error responses follow this structure:

```json
{
  "status": "error",
  "message": "Error description",
  "errors": {
    "field_name": [
      "Error message for this field"
    ]
  }
}
```

## Rate Limiting

API requests are limited to 60 requests per minute per IP address. The following headers are included in responses:

- `X-RateLimit-Limit`: Maximum number of requests per minute
- `X-RateLimit-Remaining`: Number of requests remaining in the current time window
- `X-RateLimit-Reset`: Time (Unix timestamp) when the rate limit resets

# Order Notifications (DB-backed, Non-Real-Time) ✅

**Status:** Design note — *not a code implementation* (do not paste this directly into code).

## Purpose
- Store a record in a notifications table whenever an order is created.
- Notifications are DB-backed (for display in admin/user dashboards) and **not** real-time broadcasts.

> **Note:** This document is a specification and implementation plan only — it intentionally contains no executable code.

---

## Overview
- Use the existing `OrderCreated` event (already present in the codebase).
- Add a listener (e.g., `StoreOrderNotification`) that inserts a row into `notifications` table when an order is created.
- Keep notifications small (reference `order_id` and a JSON payload), and mark `is_read` default `false`.

---

## Database Design (migration)
- Table name: `notifications`
- Suggested columns:
  - `id` (bigIncrements)
  - `type` (string) — e.g., `order_created`
  - `order_id` (unsignedBigInteger, nullable) — foreign reference to `orders.id` (index)
  - `data` (json) — small JSON payload with required display fields (order_number, total, user_id, etc.)
  - `is_read` (boolean) — default false
  - `created_at`, `updated_at`

**Indexes:** index on `order_id`, index on `is_read` (or a composite index if needed)

---

## Event & Listener
- **Event:** Use `App\Events\OrderCreated` which already receives an `Order` instance.
- **Listener:** `StoreOrderNotification` (or `CreateOrderNotificationRecord`) responsibilities:
  - Build a compact `data` payload (e.g., order_number, total, customer_name, short message)
  - Insert into `notifications` table
  - Log success/failure
  - Optionally implement `ShouldQueue` (if you want insertion to be asynchronous) — but even synchronous insert is fine for "non-real-time" behaviour.

**Wire-up:** Add listener to `EventServiceProvider` mapping for `OrderCreated::class => [StoreOrderNotification::class]`.

---

## Consumption (UI / API)
- Admin or user dashboards can query `notifications` (paginate, filter by `is_read`, order by `created_at DESC`).
- Provide an API endpoint to mark notifications as read (update `is_read=true`).
- Optionally, provide a small `notifications_count` cache for quick counts.

---

## Testing
- Add a feature test that triggers order creation (via service or API) and asserts a row exists in `notifications` with expected `type` and `order_id`.
- Test marking as read and filtering.

---

## Security & Data Retention
- Store only necessary fields in `data` (avoid storing sensitive PII beyond what's needed).
- Consider retention or pruning policy (e.g., delete notifications older than X days via scheduled task) if volume grows.

---

## Notes / Considerations
- This is deliberately a DB-backed, non-real-time approach (no broadcasting or websocket push).
- If later you want near-real-time, consider adding a broadcast in a separate listener or toggling `ShouldQueue`.

---

## Next steps (if you want me to implement):
1. Create the migration file for `notifications`.
2. Add `StoreOrderNotification` listener and register it.
3. Add tests and an API endpoint to fetch/mark read.

---

**Reminder:** This file is a design / spec note only — it does not contain runnable code.

*Created: docs/features/order-notifications.md*
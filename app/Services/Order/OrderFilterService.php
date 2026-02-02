<?php

namespace App\Services\Order;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Order Filter Service - Big Tech Pattern
 * 
 * Clean, maintainable filter logic separated from controller
 * Each filter method is independent and testable
 */
class OrderFilterService
{
    /**
     * Apply all filters to the order query
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn($q, $status) => $this->filterByStatus($q, $status))
            ->when($filters['payment_status'] ?? null, fn($q, $status) => $this->filterByPaymentStatus($q, $status))
            ->when($filters['customer_search'] ?? null, fn($q, $search) => $this->filterByCustomer($q, $search))
            ->when($filters['order_number'] ?? null, fn($q, $number) => $this->filterByOrderNumber($q, $number))
            ->when($filters['date_from'] ?? null, fn($q, $date) => $this->filterByDateFrom($q, $date))
            ->when($filters['date_to'] ?? null, fn($q, $date) => $this->filterByDateTo($q, $date))
            ->when($filters['min_total'] ?? null, fn($q, $amount) => $this->filterByMinTotal($q, $amount))
            ->when($filters['max_total'] ?? null, fn($q, $amount) => $this->filterByMaxTotal($q, $amount))
            ->when($filters['date_preset'] ?? null, fn($q, $preset) => $this->filterByDatePreset($q, $preset))
            ->when($filters['shipping_area'] ?? null, fn($q, $area) => $this->filterByShippingArea($q, $area))
            ->when($filters['has_courier'] ?? null, fn($q, $value) => $this->filterByCourierStatus($q, $value));
    }

    /**
     * Filter by order status
     */
    protected function filterByStatus(Builder $query, string $status): Builder
    {
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned', 'on_hold', 'confirmed', 'incomplete'];
        
        if (!in_array($status, $validStatuses)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Filter by payment status
     */
    protected function filterByPaymentStatus(Builder $query, string $status): Builder
    {
        $validStatuses = ['unpaid', 'paid', 'refunded', 'failed'];
        
        if (!in_array($status, $validStatuses)) {
            return $query;
        }

        return $query->where('payment_status', $status);
    }

    /**
     * Filter by customer (name, email, phone)
     * Uses OR condition for flexible search
     */
    protected function filterByCustomer(Builder $query, string $search): Builder
    {
        $search = trim($search);
        
        if (empty($search)) {
            return $query;
        }

        return $query->where(function($q) use ($search) {
            $q->where('customer_name', 'like', "%{$search}%")
              ->orWhere('customer_email', 'like', "%{$search}%")
              ->orWhere('customer_phone', 'like', "%{$search}%");
        });
    }

    /**
     * Filter by order number
     * Exact match or partial match
     */
    protected function filterByOrderNumber(Builder $query, string $number): Builder
    {
        $number = trim($number);
        
        if (empty($number)) {
            return $query;
        }

        return $query->where('order_number', 'like', "%{$number}%");
    }

    /**
     * Filter by date from (inclusive)
     */
    protected function filterByDateFrom(Builder $query, string $date): Builder
    {
        try {
            $parsedDate = Carbon::parse($date)->startOfDay();
            return $query->where('created_at', '>=', $parsedDate);
        } catch (\Exception $e) {
            return $query;
        }
    }

    /**
     * Filter by date to (inclusive)
     */
    protected function filterByDateTo(Builder $query, string $date): Builder
    {
        try {
            $parsedDate = Carbon::parse($date)->endOfDay();
            return $query->where('created_at', '<=', $parsedDate);
        } catch (\Exception $e) {
            return $query;
        }
    }

    /**
     * Filter by minimum total amount
     */
    protected function filterByMinTotal(Builder $query, $amount): Builder
    {
        $amount = (float) $amount;
        
        if ($amount <= 0) {
            return $query;
        }

        return $query->where('total', '>=', $amount);
    }

    /**
     * Filter by maximum total amount
     */
    protected function filterByMaxTotal(Builder $query, $amount): Builder
    {
        $amount = (float) $amount;
        
        if ($amount <= 0) {
            return $query;
        }

        return $query->where('total', '<=', $amount);
    }

    /**
     * Filter by date presets (today, yesterday, this_week, etc)
     * Big Tech feature: Quick date filters
     */
    protected function filterByDatePreset(Builder $query, string $preset): Builder
    {
        $now = Carbon::now();

        switch ($preset) {
            case 'today':
                return $query->whereDate('created_at', $now->toDateString());

            case 'yesterday':
                $yesterday = $now->copy()->subDay();
                return $query->whereDate('created_at', $yesterday->toDateString());

            case 'this_week':
                return $query->whereBetween('created_at', [
                    $now->copy()->startOfWeek(),
                    $now->copy()->endOfWeek()
                ]);

            case 'last_week':
                $lastWeek = $now->copy()->subWeek();
                return $query->whereBetween('created_at', [
                    $lastWeek->startOfWeek(),
                    $lastWeek->endOfWeek()
                ]);

            case 'this_month':
                return $query->whereMonth('created_at', $now->month)
                             ->whereYear('created_at', $now->year);

            case 'last_month':
                $lastMonth = $now->copy()->subMonth();
                return $query->whereMonth('created_at', $lastMonth->month)
                             ->whereYear('created_at', $lastMonth->year);

            case 'last_7_days':
                return $query->whereBetween('created_at', [
                    $now->copy()->subDays(7),
                    $now
                ]);

            case 'last_30_days':
                return $query->whereBetween('created_at', [
                    $now->copy()->subDays(30),
                    $now
                ]);

            case 'this_year':
                return $query->whereYear('created_at', $now->year);

            default:
                return $query;
        }
    }

    /**
     * Filter by shipping area
     */
    protected function filterByShippingArea(Builder $query, string $area): Builder
    {
        $validAreas = ['inside_dhaka', 'outside_dhaka'];
        
        if (!in_array($area, $validAreas)) {
            return $query;
        }

        return $query->where('area', $area);
    }

    /**
     * Filter by courier status
     * has_courier: true = has courier tracking, false = no courier
     */
    protected function filterByCourierStatus(Builder $query, $value): Builder
    {
        $hasCourier = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        if ($hasCourier) {
            return $query->whereNotNull('courier_tracking_id');
        } else {
            return $query->whereNull('courier_tracking_id');
        }
    }

    /**
     * Apply sorting to the query
     */
    public function applySorting(Builder $query, string $sortBy = 'created_at', string $sortDirection = 'desc'): Builder
    {
        // Whitelist of allowed sort columns for security
        $allowedColumns = [
            'id',
            'order_number',
            'customer_name',
            'customer_email',
            'total',
            'status',
            'payment_status',
            'created_at',
            'updated_at'
        ];

        // Validate sort column
        if (!in_array($sortBy, $allowedColumns)) {
            $sortBy = 'created_at';
        }

        // Validate sort direction
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        return $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Get active filter count
     * Useful for UI badge display
     */
    public function getActiveFilterCount(array $filters): int
    {
        $count = 0;

        // Count non-empty filters (excluding pagination/sorting)
        $filterKeys = ['status', 'payment_status', 'customer_search', 'order_number', 'date_from', 'date_to', 'min_total', 'max_total', 'date_preset', 'shipping_area', 'has_courier'];

        foreach ($filterKeys as $key) {
            if (!empty($filters[$key])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get filter summary for display
     * Returns human-readable filter descriptions
     */
    public function getFilterSummary(array $filters): array
    {
        $summary = [];

        if (!empty($filters['status'])) {
            $summary[] = 'Status: ' . ucfirst($filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $summary[] = 'Payment: ' . ucfirst($filters['payment_status']);
        }

        if (!empty($filters['customer_search'])) {
            $summary[] = 'Customer: "' . $filters['customer_search'] . '"';
        }

        if (!empty($filters['order_number'])) {
            $summary[] = 'Order: ' . $filters['order_number'];
        }

        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $from = $filters['date_from'] ?? 'start';
            $to = $filters['date_to'] ?? 'now';
            $summary[] = "Date: {$from} to {$to}";
        }

        if (!empty($filters['min_total']) || !empty($filters['max_total'])) {
            $min = $filters['min_total'] ?? '0';
            $max = $filters['max_total'] ?? '∞';
            $summary[] = "Amount: ৳{$min} - ৳{$max}";
        }

        if (!empty($filters['date_preset'])) {
            $summary[] = 'Period: ' . str_replace('_', ' ', ucfirst($filters['date_preset']));
        }

        if (!empty($filters['shipping_area'])) {
            $summary[] = 'Area: ' . str_replace('_', ' ', ucfirst($filters['shipping_area']));
        }

        if (isset($filters['has_courier'])) {
            $summary[] = $filters['has_courier'] ? 'With Courier' : 'No Courier';
        }

        return $summary;
    }

    /**
     * Validate filter input
     * Returns cleaned and validated filters
     */
    public function validateFilters(array $filters): array
    {
        $validated = [];

        // String filters
        $stringFilters = ['status', 'payment_status', 'customer_search', 'order_number', 'date_from', 'date_to', 'date_preset', 'shipping_area'];
        foreach ($stringFilters as $key) {
            if (isset($filters[$key])) {
                $validated[$key] = trim((string) $filters[$key]);
            }
        }

        // Numeric filters
        if (isset($filters['min_total'])) {
            $validated['min_total'] = max(0, (float) $filters['min_total']);
        }
        if (isset($filters['max_total'])) {
            $validated['max_total'] = max(0, (float) $filters['max_total']);
        }

        // Boolean filters
        if (isset($filters['has_courier'])) {
            $validated['has_courier'] = filter_var($filters['has_courier'], FILTER_VALIDATE_BOOLEAN);
        }

        return $validated;
    }
}

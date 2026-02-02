<?php

namespace App\Traits;

/**
 * Provides standardized eager loading patterns for Order-related queries.
 *
 * This trait centralizes all order eager loading definitions to:
 * - Prevent N+1 query issues
 * - Ensure consistency across controllers and services
 * - Make maintenance easier when relationships change
 */
trait OrderEagerLoading
{
    /**
     * Standard eager loading for order list/index views
     * Optimized for performance with minimal data
     */
    protected function getOrderListEagerLoads(): array
    {
        return [
            'items' => function ($query) {
                $query->select([
                    'id',
                    'order_id',
                    'product_id',
                    'product_variation_id',
                    'product_name',
                    'product_sku',
                    'product_image',
                    'variation_name',
                    'variation_sku',
                    'variation_image',
                    'variation_attributes',
                    'quantity',
                    'unit_price',
                    'final_price',
                    'is_pre_order',
                    'created_at'
                ]);
            },
            'items.product' => function ($query) {
                $query->withTrashed()->select([
                    'id',
                    'name',
                    'slug',
                    'feature_image',
                    'type'
                ]);
            },
            'items.productVariation' => function ($query) {
                $query->withTrashed()->select([
                    'id',
                    'product_id',
                    'price',
                    'image_path'
                ]);
            },
            'items.productVariation.attributeValues' => function ($query) {
                $query->withTrashed();
            },
            'items.productVariation.attributeValues.attribute' => function ($query) {
                $query->withTrashed();
            },
        ];
    }

    /**
     * Full eager loading for order detail/edit views
     * Includes all relationships needed for editing
     * V2 Inventory: Uses attributeValues instead of attributes.value.attribute
     */
    protected function getOrderDetailEagerLoads(): array
    {
        return [
            'items.product' => function ($query) {
                $query->withTrashed();
            },
            'items.productVariation' => function ($query) {
                $query->withTrashed();
            },
            'items.productVariation.attributeValues:id,product_variation_id,attribute_id,value' => function ($query) {
                $query->withTrashed();
            },
            'items.productVariation.attributeValues.attribute:id,name' => function ($query) {
                $query->withTrashed();
            },
            'items.productVariation.inventoryStock',
            'statusHistories.changedBy',
            'editLogs.editor',
        ];
    }

    /**
     * Eager loading for invoice generation
     * Includes all product/variation details with soft deleted data
     * V2 Inventory: Uses attributeValues instead of attributes.value.attribute
     */
    protected function getInvoiceEagerLoads(): array
    {
        return [
            'items.product' => function ($query) {
                $query->withTrashed();
            },
            'items.productVariation' => function ($query) {
                $query->withTrashed();
            },
            'items.productVariation.attributeValues:id,product_variation_id,attribute_id,value' => function ($query) {
                $query->withTrashed();
            },
            'items.productVariation.attributeValues.attribute:id,name' => function ($query) {
                $query->withTrashed();
            },
        ];
    }

    /**
     * Minimal eager loading for status updates
     * Just items for recalculation purposes
     */
    protected function getMinimalEagerLoads(): array
    {
        return [
            'items' => function ($query) {
                $query->select([
                    'id',
                    'order_id',
                    'product_id',
                    'product_variation_id',
                    'quantity',
                    'unit_price',
                    'subtotal',
                    'discount_total',
                    'final_price',
                ]);
            },
        ];
    }

    /**
     * Eager loading for stock operations
     * Includes product and variation for stock updates
     * V2 Inventory: Removed old 'stock', 'sold_stock' fields - now in inventory_stocks table
     */
    protected function getStockOperationEagerLoads(): array
    {
        return [
            'items.product' => function ($query) {
                $query->withTrashed()->select([
                    'id',
                    'name',
                    'type',
                    'is_pre_order',
                ]);
            },
            'items.productVariation' => function ($query) {
                $query->withTrashed()->select([
                    'id',
                    'product_id',
                    'price',
                ]);
            },
            'items.product.inventoryStocks',
            'items.productVariation.inventoryStock',
        ];
    }
}

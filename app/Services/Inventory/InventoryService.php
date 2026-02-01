<?php

namespace App\Services\Inventory;

use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\StockReservation;
use App\Models\Product;
use App\Models\ProductVariation;
use App\DTOs\InventoryAdjustmentDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\InsufficientStockException;

class InventoryService
{
    /**
     * Create initial stock for a product/variation
     */
    public function createStock(
        int $productId,
        ?int $variationId = null,
        int $quantity = 0,
        string $locationCode = 'MAIN',
        float $costPrice = 0.00,
        string $reason = 'Initial stock',
        ?string $notes = null,
        int $minThreshold = 0
    ): InventoryStock {
        return DB::transaction(function () use ($productId, $variationId, $quantity, $locationCode, $costPrice, $reason, $notes, $minThreshold) {
            // Create or update inventory stock record
            $inventoryStock = InventoryStock::updateOrCreate(
                [
                    'product_id' => $productId,
                    'product_variation_id' => $variationId,
                    'location_code' => $locationCode,
                ],
                [
                    'location_name' => $this->getLocationName($locationCode),
                    'available_quantity' => $quantity,
                    'minimum_threshold' => $minThreshold,
                    'average_cost_price' => $costPrice,
                    'last_cost_price' => $costPrice,
                    'last_movement_at' => now(),
                    'last_updated_by' => auth()->id(),
                ]
            );

            // Create transaction record
            $this->createTransaction(
                inventoryStockId: $inventoryStock->id,
                productId: $productId,
                variationId: $variationId,
                type: 'initial',
                quantityChange: $quantity,
                quantityBefore: 0,
                quantityAfter: $quantity,
                costPrice: $costPrice,
                reason: $reason,
                notes: $notes,
                locationCode: $locationCode
            );

            Log::info('Initial stock created', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $quantity,
                'location' => $locationCode
            ]);

            return $inventoryStock;
        });
    }

    /**
     * Adjust stock using DTO pattern - primary method for all adjustments
     */
    public function adjustStock(InventoryAdjustmentDTO $dto): InventoryStock
    {
        return DB::transaction(function () use ($dto) {
            // Find inventory stock record
            $query = InventoryStock::where('product_id', $dto->productId)
                ->where('location_code', $dto->location);

            if ($dto->variationId) {
                $query->where('product_variation_id', $dto->variationId);
            }

            $inventoryStock = $query->first();

            if (!$inventoryStock) {
                // Create new inventory record if doesn't exist
                $inventoryStock = InventoryStock::create([
                    'product_id' => $dto->productId,
                    'product_variation_id' => $dto->variationId,
                    'location_code' => $dto->location,
                    'location_name' => ucfirst($dto->location) . ' Warehouse',
                    'available_quantity' => 0,
                    'reserved_quantity' => 0,
                    'total_quantity' => 0,
                    'minimum_threshold' => 0,
                    'reorder_point' => 0,
                    'track_inventory' => true,
                    'status' => 'active',
                    'last_updated_by' => $dto->userId,
                ]);
            }

            $quantityBefore = $inventoryStock->available_quantity;

            // Handle 'add' type which is an alias for 'increase'
            $adjType = $dto->adjustmentType === 'add' ? 'increase' : $dto->adjustmentType;

            // Calculate new quantity based on adjustment type
            $quantityAfter = match ($adjType) {
                'increase' => $quantityBefore + $dto->quantity,
                'decrease' => max(0, $quantityBefore - $dto->quantity),
                'set' => $dto->quantity,
                default => throw new \InvalidArgumentException('Invalid adjustment type')
            };

            // Map adjustment types to database enum values
            $transactionType = match ($adjType) {
                'increase' => 'adjustment',
                'decrease' => 'adjustment',
                'set' => 'adjustment',
                default => 'adjustment'
            };

            // Update inventory stock
            $inventoryStock->update([
                'available_quantity' => $quantityAfter,
                'total_quantity' => $quantityAfter + $inventoryStock->reserved_quantity,
                'last_movement_at' => now(),
                'last_updated_by' => $dto->userId,
                'adjustment_count' => $inventoryStock->adjustment_count + 1,
            ]);

            // Record transaction
            InventoryTransaction::create([
                'inventory_stock_id' => $inventoryStock->id,
                'product_id' => $dto->productId,
                'product_variation_id' => $dto->variationId,
                'transaction_type' => $transactionType,
                'quantity_change' => $adjType === 'decrease' ? -$dto->quantity : $dto->quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference_type' => $dto->referenceType ?? 'manual_adjustment',
                'reference_id' => $dto->referenceId,
                'location_code' => $dto->location,
                'reason' => $dto->reason,
                'notes' => $dto->notes,
                'created_by' => $dto->userId,
                'created_by_type' => 'user',
                'source' => 'admin_panel',
            ]);

            Log::info('Stock adjusted', [
                'product_id' => $dto->productId,
                'location' => $dto->location,
                'type' => $dto->adjustmentType,
                'quantity' => $dto->quantity,
                'before' => $quantityBefore,
                'after' => $quantityAfter,
                'user_id' => $dto->userId,
            ]);

            return $inventoryStock->fresh();
        });
    }

    /**
     * Legacy adjust stock method (for backward compatibility)
     */
    public function adjustStockLegacy(
        int $productId,
        int $quantityChange,
        string $reason,
        ?int $variationId = null,
        string $locationCode = 'MAIN',
        ?float $costPrice = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): InventoryStock {
        return DB::transaction(function () use ($productId, $variationId, $quantityChange, $reason, $locationCode, $costPrice, $notes, $referenceType, $referenceId) {
            // Find inventory stock record
            $inventoryStock = InventoryStock::where('product_id', $productId)
                ->where('product_variation_id', $variationId)
                ->where('location_code', $locationCode)
                ->first();

            if (!$inventoryStock) {
                throw new \Exception("Inventory stock record not found for product {$productId} at location {$locationCode}");
            }

            $quantityBefore = $inventoryStock->available_quantity;
            $quantityAfter = $quantityBefore + $quantityChange;

            // Validate stock cannot go below reserved quantity
            if ($quantityAfter < 0 && abs($quantityAfter) > $inventoryStock->reserved_quantity) {
                throw new InsufficientStockException(
                    "Cannot reduce stock below reserved quantity. Available: {$quantityBefore}, Requested: " . abs($quantityChange)
                );
            }

            // Update inventory stock
            $inventoryStock->update([
                'available_quantity' => max(0, $quantityAfter), // Don't allow negative stock
                'last_movement_at' => now(),
                'last_updated_by' => auth()->id(),
                'adjustment_count' => $inventoryStock->adjustment_count + 1,
            ]);

            // Update cost price if provided
            if ($costPrice && $quantityChange > 0) {
                $this->updateAverageCost($inventoryStock, $quantityChange, $costPrice);
            }

            // Determine transaction type
            $transactionType = $quantityChange > 0 ? 'adjustment' : 'adjustment';
            if ($referenceType === 'sale') {
                $transactionType = 'sale';
            } elseif ($referenceType === 'purchase') {
                $transactionType = 'purchase';
            }

            // Create transaction record
            $this->createTransaction(
                inventoryStockId: $inventoryStock->id,
                productId: $productId,
                variationId: $variationId,
                type: $transactionType,
                quantityChange: $quantityChange,
                quantityBefore: $quantityBefore,
                quantityAfter: $quantityAfter,
                costPrice: $costPrice,
                reason: $reason,
                notes: $notes,
                locationCode: $locationCode,
                referenceType: $referenceType,
                referenceId: $referenceId
            );

            // Update product stock status
            $this->updateProductStockStatus($productId);

            Log::info('Stock adjusted', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity_change' => $quantityChange,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $reason
            ]);

            return $inventoryStock->refresh();
        });
    }

    /**
     * Reserve stock for cart/order
     */
    public function reserveStock(
        int $productId,
        int $quantity,
        string $reservedFor,
        int $reservedForId,
        ?int $variationId = null,
        string $locationCode = 'MAIN',
        ?\DateTime $expiresAt = null
    ): StockReservation {
        return DB::transaction(function () use ($productId, $variationId, $quantity, $reservedFor, $reservedForId, $locationCode, $expiresAt) {
            // Find inventory stock
            $inventoryStock = InventoryStock::where('product_id', $productId)
                ->where('product_variation_id', $variationId)
                ->where('location_code', $locationCode)
                ->first();

            if (!$inventoryStock || $inventoryStock->available_quantity < $quantity) {
                throw new InsufficientStockException("Insufficient stock for reservation. Available: " . ($inventoryStock->available_quantity ?? 0));
            }

            // Create reservation
            $reservation = StockReservation::create([
                'inventory_stock_id' => $inventoryStock->id,
                'product_id' => $productId,
                'product_variation_id' => $variationId,
                'reserved_for_type' => $reservedFor,
                'reserved_for_id' => $reservedForId,
                'quantity' => $quantity,
                'expires_at' => $expiresAt ?? now()->addHours(2), // Default 2 hours
                'created_by' => auth()->id(),
                'source' => $reservedFor,
            ]);

            // Update inventory stock reserved quantity
            $inventoryStock->increment('reserved_quantity', $quantity);
            $inventoryStock->decrement('available_quantity', $quantity);

            // Create transaction record
            $this->createTransaction(
                inventoryStockId: $inventoryStock->id,
                productId: $productId,
                variationId: $variationId,
                type: 'reservation',
                quantityChange: -$quantity,
                quantityBefore: $inventoryStock->available_quantity + $quantity,
                quantityAfter: $inventoryStock->available_quantity,
                reason: "Stock reserved for {$reservedFor} #{$reservedForId}",
                locationCode: $locationCode,
                referenceType: $reservedFor,
                referenceId: $reservedForId
            );

            return $reservation;
        });
    }

    /**
     * Release stock reservation
     */
    public function releaseReservation(int $reservationId): bool
    {
        return DB::transaction(function () use ($reservationId) {
            $reservation = StockReservation::findOrFail($reservationId);

            if ($reservation->status !== 'active') {
                return false; // Already processed
            }

            $inventoryStock = $reservation->inventoryStock;

            // Update inventory stock
            $inventoryStock->decrement('reserved_quantity', $reservation->remaining_quantity);
            $inventoryStock->increment('available_quantity', $reservation->remaining_quantity);

            // Update reservation status
            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);

            // Create transaction record
            $this->createTransaction(
                inventoryStockId: $inventoryStock->id,
                productId: $reservation->product_id,
                variationId: $reservation->product_variation_id,
                type: 'release',
                quantityChange: $reservation->remaining_quantity,
                quantityBefore: $inventoryStock->available_quantity - $reservation->remaining_quantity,
                quantityAfter: $inventoryStock->available_quantity,
                reason: "Reservation #{$reservationId} cancelled",
                locationCode: $inventoryStock->location_code,
                referenceType: $reservation->reserved_for_type,
                referenceId: $reservation->reserved_for_id
            );

            return true;
        });
    }

    /**
     * Get available stock for product/variation
     */
    public function getAvailableStock(int $productId, ?int $variationId = null, string $locationCode = 'MAIN'): int
    {
        $inventoryStock = InventoryStock::where('product_id', $productId)
            ->where('product_variation_id', $variationId)
            ->where('location_code', $locationCode)
            ->first();

        return $inventoryStock ? $inventoryStock->available_quantity : 0;
    }

    /**
     * Get stock movements for product
     */
    public function getStockMovements(int $productId, ?int $variationId = null, int $limit = 50)
    {
        return InventoryTransaction::where('product_id', $productId)
            ->where('product_variation_id', $variationId)
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Create transaction record
     */
    protected function createTransaction(
        int $inventoryStockId,
        int $productId,
        ?int $variationId,
        string $type,
        int $quantityChange,
        int $quantityBefore,
        int $quantityAfter,
        ?float $costPrice = null,
        ?string $reason = null,
        ?string $notes = null,
        string $locationCode = 'MAIN',
        ?string $referenceType = null,
        ?int $referenceId = null
    ): InventoryTransaction {
        return InventoryTransaction::create([
            'inventory_stock_id' => $inventoryStockId,
            'product_id' => $productId,
            'product_variation_id' => $variationId,
            'transaction_type' => $type,
            'quantity_change' => $quantityChange,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'unit_cost' => $costPrice,
            'total_cost' => $costPrice ? $costPrice * abs($quantityChange) : null,
            'location_code' => $locationCode,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => $reason,
            'notes' => $notes,
            'created_by' => auth()->id(),
            'created_by_type' => 'user',
            'source' => 'manual',
        ]);
    }

    /**
     * Update average cost price
     */
    protected function updateAverageCost(InventoryStock $inventoryStock, int $newQuantity, float $newCost): void
    {
        $currentQuantity = $inventoryStock->available_quantity - $newQuantity;
        $currentCost = $inventoryStock->average_cost_price;

        if ($currentQuantity <= 0) {
            $averageCost = $newCost;
        } else {
            $totalValue = ($currentQuantity * $currentCost) + ($newQuantity * $newCost);
            $totalQuantity = $currentQuantity + $newQuantity;
            $averageCost = $totalValue / $totalQuantity;
        }

        $inventoryStock->update([
            'average_cost_price' => round($averageCost, 2),
            'last_cost_price' => $newCost,
        ]);
    }

    /**
     * Update product stock status
     */
    protected function updateProductStockStatus(int $productId): void
    {
        $totalStock = InventoryStock::where('product_id', $productId)->sum('available_quantity');

        $product = Product::find($productId);
        if ($product) {
            $product->update([
                'stock_status' => $totalStock > 0 ? 'in_stock' : 'out_of_stock'
            ]);
        }
    }

    /**
     * Get location name from code
     */
    protected function getLocationName(string $locationCode): string
    {
        $locations = [
            'MAIN' => 'Main Warehouse',
            'STORE' => 'Store Front',
            'BACKUP' => 'Backup Storage',
        ];

        return $locations[$locationCode] ?? $locationCode;
    }
}

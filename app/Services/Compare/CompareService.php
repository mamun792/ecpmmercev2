<?php

namespace App\Services\Compare;

use App\Models\CompareList;
use App\Models\Product;

class CompareService
{
    /**
     * Get or create compare list for user/session
     */
    private function getOrCreateCompareList($userId, $sessionId)
    {
        $compareList = CompareList::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->first();

        if (!$compareList) {
            $compareList = CompareList::create([
                'user_id' => $userId,
                'session_id' => $userId ? null : $sessionId,
                'product_ids' => [],
            ]);
        }

        return $compareList;
    }

    /**
     * Add product to compare list
     */
    public function addToCompare(int $productId, $userId = null, $sessionId = null)
    {
        $product = Product::find($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $compareList = $this->getOrCreateCompareList($userId, $sessionId);
        $ids = $compareList->product_ids ?? [];

        if (count($ids) >= 4) {
            return ['success' => false, 'message' => 'Compare list can have maximum 4 products'];
        }

        if (in_array($productId, $ids)) {
            return ['success' => false, 'message' => 'Product already in compare list'];
        }

        $compareList->addProduct($productId);

        return ['success' => true, 'message' => 'Product added to compare list'];
    }

    /**
     * Remove product from compare list
     */
    public function removeFromCompare(int $productId, $userId = null, $sessionId = null)
    {
        $compareList = CompareList::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->first();

        if (!$compareList) {
            return ['success' => false, 'message' => 'Compare list not found'];
        }

        $compareList->removeProduct($productId);

        return ['success' => true, 'message' => 'Product removed from compare list'];
    }

    /**
     * Get compare list items
     */
    public function getCompareList($userId = null, $sessionId = null)
    {
        $compareList = CompareList::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->first();

        if (!$compareList) {
            return collect();
        }

        return $compareList->products();
    }

    /**
     * Get compare count
     */
    public function getCompareCount($userId = null, $sessionId = null)
    {
        $compareList = CompareList::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->first();

        return $compareList ? count($compareList->product_ids ?? []) : 0;
    }

    /**
     * Get compare product ids as array
     */
    public function getCompareIds($userId = null, $sessionId = null)
    {
        $compareList = CompareList::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->first();

        $ids = $compareList ? ($compareList->product_ids ?? []) : [];
        return array_values($ids);
    }

    /**
     * Clear compare list
     */
    public function clearCompareList($userId = null, $sessionId = null)
    {
        $compareList = CompareList::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->first();

        if ($compareList) {
            $compareList->product_ids = [];
            $compareList->save();
        }

        return ['success' => true, 'message' => 'Compare list cleared'];
    }

    /**
     * Check if product is in compare list
     */
    public function isInCompare(int $productId, $userId = null, $sessionId = null)
    {
        $compareList = CompareList::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->first();

        return $compareList ? in_array($productId, $compareList->product_ids ?? []) : false;
    }
}

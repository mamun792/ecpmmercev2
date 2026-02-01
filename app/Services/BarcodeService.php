<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariation;

/**
 * Barcode Generation Service - Amazon/Shopify Style
 * 
 * Generates EAN-13 compatible barcodes automatically
 * Format: 880-XXXXX-XXXXX-C
 * - 880: Bangladesh country code
 * - XXXXX: Product ID (5 digits, zero-padded)
 * - XXXXX: Random unique number (5 digits)
 * - C: Check digit (EAN-13 algorithm)
 */
class BarcodeService
{
    /**
     * Generate EAN-13 barcode for a product
     * 
     * @param int $productId
     * @return string 13-digit barcode
     */
    public function generateEAN13(int $productId): string
    {
        $attempts = 0;
        $maxAttempts = 10;

        do {
            // 880 = Bangladesh country code (3 digits)
            $countryCode = '880';
            
            // Product ID (5 digits, zero-padded)
            $productPart = str_pad($productId, 5, '0', STR_PAD_LEFT);
            
            // Random unique part (5 digits)
            $randomPart = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            
            // First 12 digits
            $barcode12 = $countryCode . $productPart . $randomPart;
            
            // Calculate check digit
            $checkDigit = $this->calculateEAN13CheckDigit($barcode12);
            
            $barcode = $barcode12 . $checkDigit;
            
            // Check if barcode is unique
            if ($this->isBarcodeUnique($barcode)) {
                return $barcode;
            }
            
            $attempts++;
        } while ($attempts < $maxAttempts);

        // Fallback: Use timestamp-based barcode
        return $this->generateTimestampBarcode($productId);
    }

    /**
     * Generate barcode for product variation
     * 
     * @param int $productId
     * @param int $variationId
     * @return string
     */
    public function generateVariationBarcode(int $productId, int $variationId): string
    {
        $attempts = 0;
        $maxAttempts = 10;

        do {
            // 880 = Bangladesh
            $countryCode = '880';
            
            // Product ID (3 digits)
            $productPart = str_pad($productId, 3, '0', STR_PAD_LEFT);
            
            // Variation ID (3 digits)
            $variationPart = str_pad($variationId, 3, '0', STR_PAD_LEFT);
            
            // Random (3 digits)
            $randomPart = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
            
            // First 12 digits
            $barcode12 = $countryCode . $productPart . $variationPart . $randomPart;
            
            // Calculate check digit
            $checkDigit = $this->calculateEAN13CheckDigit($barcode12);
            
            $barcode = $barcode12 . $checkDigit;
            
            // Check uniqueness
            if ($this->isBarcodeUnique($barcode)) {
                return $barcode;
            }
            
            $attempts++;
        } while ($attempts < $maxAttempts);

        // Fallback
        return $this->generateTimestampBarcode($productId, $variationId);
    }

    /**
     * Calculate EAN-13 check digit
     * 
     * @param string $barcode12 First 12 digits
     * @return int Check digit (0-9)
     */
    private function calculateEAN13CheckDigit(string $barcode12): int
    {
        $sum = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $barcode12[$i];
            // Odd positions (1st, 3rd, 5th...) multiply by 1
            // Even positions (2nd, 4th, 6th...) multiply by 3
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        
        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Validate EAN-13 barcode
     * 
     * @param string $barcode
     * @return bool
     */
    public function validateEAN13(string $barcode): bool
    {
        // Must be 13 digits
        if (strlen($barcode) !== 13 || !ctype_digit($barcode)) {
            return false;
        }

        $barcode12 = substr($barcode, 0, 12);
        $providedCheckDigit = (int) $barcode[12];
        $calculatedCheckDigit = $this->calculateEAN13CheckDigit($barcode12);

        return $providedCheckDigit === $calculatedCheckDigit;
    }

    /**
     * Check if barcode is unique in database
     * 
     * @param string $barcode
     * @return bool
     */
    private function isBarcodeUnique(string $barcode): bool
    {
        // Check in products table
        $productExists = Product::where('barcode', $barcode)->exists();
        
        // Check in product_variations table
        $variationExists = ProductVariation::where('barcode', $barcode)->exists();
        
        return !$productExists && !$variationExists;
    }

    /**
     * Generate timestamp-based barcode (fallback)
     * 
     * @param int $productId
     * @param int|null $variationId
     * @return string
     */
    private function generateTimestampBarcode(int $productId, ?int $variationId = null): string
    {
        $timestamp = substr((string) time(), -6); // Last 6 digits of timestamp
        $productPart = str_pad($productId, 3, '0', STR_PAD_LEFT);
        $variationPart = $variationId ? str_pad($variationId, 2, '0', STR_PAD_LEFT) : '00';
        
        $barcode12 = '880' . $productPart . $variationPart . $timestamp;
        $checkDigit = $this->calculateEAN13CheckDigit($barcode12);
        
        return $barcode12 . $checkDigit;
    }

    /**
     * Format barcode for display (with dashes)
     * 
     * @param string $barcode
     * @return string
     */
    public function formatBarcode(string $barcode): string
    {
        if (strlen($barcode) !== 13) {
            return $barcode;
        }

        // Format: 880-12345-67890-1
        return substr($barcode, 0, 3) . '-' . 
               substr($barcode, 3, 5) . '-' . 
               substr($barcode, 8, 4) . '-' . 
               substr($barcode, 12, 1);
    }
}

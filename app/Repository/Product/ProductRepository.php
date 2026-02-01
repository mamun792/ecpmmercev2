<?php

namespace App\Repository\Product;

use App\Models\Product;
use App\Models\ProductVariation;


class ProductRepository
{
    /**
     * Find a product by ID
     */
    public function findById($id)
    {
        return Product::find($id);
    }

    public function findVariationById($id)
    {
        return ProductVariation::find($id);
    }
}

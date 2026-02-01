<?php

namespace App\Services\Brand;

use App\Models\Brand;
use Illuminate\Support\Str;
use App\Helpers\ImageHelper;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\File;

class BrandService
{

  public function getAllBrands()
  {
      return Brand::all();
  }


  public function createBrand(array $data)
  {
      try {
          // Handle image upload
          $imagePath = null;
          if (isset($data['brand_image']) && $data['brand_image']->isValid()) {
              $imagePath = ImageHelper::uploadImage($data['brand_image'], 'storage/brands');
          }

          // Create brand
          return Brand::create([
              'brand_name' => $data['brand_name'],
              'brand_slug' => Str::slug($data['brand_name']),
              'brand_image' => $imagePath,
          ]);
      } catch (\Exception $e) {
          throw new \Exception('Failed to create brand: ' . $e->getMessage());
      }
  }


  public function findBrand($id)
  {
    return Brand::findOrFail($id);
  }


  public function updateBrand(Brand $brand, array $data)
  {
      try {
          $imagePath = $brand->brand_image;
  
          if (isset($data['brand_image']) && $data['brand_image']->isValid()) {
              // Delete old image using the helper
              ImageHelper::deleteImage($imagePath);
  
              $imagePath = ImageHelper::uploadImage($data['brand_image'], 'storage/brands');
          }
  
          // Update brand
          $brand->update([
              'brand_name' => $data['brand_name'],
              'brand_slug' => Str::slug($data['brand_name']),
              'brand_image' => $imagePath,
          ]);
  
          return $brand;
      } catch (\Exception $e) {
          throw new \Exception('Failed to update brand: ' . $e->getMessage());
      }
  }
  

  public function updateStatus($id, $status)
    {
        $brand = Brand::findOrFail($id);
        $brand->status = $status;
        $brand->save();
        
        return $brand;
    }


    public function deleteBrand(Brand $brand)
    {
        try {
            // Delete brand image using helper
            ImageHelper::deleteImage($brand->brand_image);
    
            // Delete the brand
            $brand->delete();
    
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete brand: ' . $e->getMessage());
        }
    }
    

}
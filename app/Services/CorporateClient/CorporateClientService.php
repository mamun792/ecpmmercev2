<?php

namespace App\Services\CorporateClient;

use App\Models\CorporateClient;
use App\Helpers\ImageHelper;

class CorporateClientService
{

  public function getAllCorporateClients()
  {
      return CorporateClient::all();
  }


  public function createCorporateClient(array $data)
  {
      try {
          // Handle image upload
          $imagePath = null;
          if (isset($data['image']) && $data['image']->isValid()) {
              $imagePath = ImageHelper::uploadImage($data['image'], 'storage/corporate-clients');
          }

          // Create corporate client
          return CorporateClient::create([
              'image' => $imagePath,
              'link' => $data['link'] ?? null,
          ]);
      } catch (\Exception $e) {
          throw new \Exception('Failed to create corporate client: ' . $e->getMessage());
      }
  }


  public function findCorporateClient($id)
  {
    return CorporateClient::findOrFail($id);
  }


  public function updateCorporateClient(CorporateClient $corporateClient, array $data)
  {
      try {
          $imagePath = $corporateClient->image;

          if (isset($data['image']) && $data['image']->isValid()) {
              // Delete old image using the helper
              ImageHelper::deleteImage($imagePath);

              $imagePath = ImageHelper::uploadImage($data['image'], 'storage/corporate-clients');
          }

          // Update corporate client
          $corporateClient->update([
              'image' => $imagePath,
              'link' => $data['link'] ?? null,
          ]);

          return $corporateClient;
      } catch (\Exception $e) {
          throw new \Exception('Failed to update corporate client: ' . $e->getMessage());
      }
  }


    public function deleteCorporateClient(CorporateClient $corporateClient)
    {
        try {
            // Delete corporate client image using helper
            ImageHelper::deleteImage($corporateClient->image);

            // Delete the corporate client
            $corporateClient->delete();

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete corporate client: ' . $e->getMessage());
        }
    }


}
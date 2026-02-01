<?php

namespace App\Services;

use App\Contracts\PreOrderLeadInterface;
use App\DTOs\PreOrderLeadDTO;
use App\Helpers\ImageHelper;
use App\Models\PreOrderLead;
use App\Repository\PreOrderLead\PreOrderLeadRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class PreOrderLeadService implements PreOrderLeadInterface
{
    protected PreOrderLeadRepository $repository;

    public function __construct(PreOrderLeadRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createLead(array $data): PreOrderLead
    {
        // Handle image upload if present
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $imagePath = 'uploads/leads';
            $data['image_path'] = ImageHelper::uploadImage($data['image'], $imagePath);
            unset($data['image']); // Remove the file from data array
        }

        // Create DTO and convert to array
        $dto = PreOrderLeadDTO::fromArray($data);
        $leadData = $dto->toArray();

        return $this->repository->create($leadData);
    }

    public function getAllLeads(): Collection
    {
        return $this->repository->getAll();
    }

    public function getLeadById(int $id): PreOrderLead
    {
        return $this->repository->findById($id);
    }

    public function updateLeadStatus(int $id, string $status): bool
    {
        return $this->repository->updateStatus($id, $status);
    }

    public function deleteLead(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
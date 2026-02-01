<?php

namespace App\Repository\PreOrderLead;

use App\Models\PreOrderLead;
use Illuminate\Database\Eloquent\Collection;

class PreOrderLeadRepository
{
    public function create(array $data): PreOrderLead
    {
        return PreOrderLead::create($data);
    }

    public function getAll(): Collection
    {
        return PreOrderLead::orderBy('created_at', 'desc')->get();
    }

    public function findById(int $id): PreOrderLead
    {
        return PreOrderLead::findOrFail($id);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $lead = $this->findById($id);
        return $lead->update(['status' => $status]);
    }

    public function delete(int $id): bool
    {
        $lead = $this->findById($id);
        return $lead->delete();
    }
}
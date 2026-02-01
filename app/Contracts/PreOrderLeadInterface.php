<?php

namespace App\Contracts;

use App\Models\PreOrderLead;
use Illuminate\Database\Eloquent\Collection;

interface PreOrderLeadInterface
{
    public function createLead(array $data): PreOrderLead;

    public function getAllLeads(): Collection;

    public function getLeadById(int $id): PreOrderLead;

    public function updateLeadStatus(int $id, string $status): bool;

    public function deleteLead(int $id): bool;
}
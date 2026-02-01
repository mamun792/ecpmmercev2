<?php

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

trait HasPaginationMeta
{
    protected function buildPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'links' => $paginator->linkCollection()->toArray(),
            'path' => $paginator->path()
        ];
    }
}

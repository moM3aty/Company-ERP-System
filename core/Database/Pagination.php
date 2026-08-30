<?php
// Path: core/Database/Pagination.php

namespace Core\Database;

/**
 * Generates SQL LIMIT/OFFSET and builds pagination metadata.
 */
class Pagination
{
    private int $total;
    private int $perPage;
    private int $currentPage;

    public function __construct(int $total, int $perPage = 15, int $currentPage = 1)
    {
        $this->total = $total;
        $this->perPage = $perPage > 0 ? $perPage : 15;
        $this->currentPage = $currentPage > 0 ? $currentPage : 1;
    }

    public function getSqlLimitOffset(): string
    {
        $offset = ($this->currentPage - 1) * $this->perPage;
        return " LIMIT {$this->perPage} OFFSET {$offset}";
    }

    public function getMetaData(): array
    {
        $lastPage = ceil($this->total / $this->perPage);
        return [
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $lastPage,
            'has_more' => $this->currentPage < $lastPage
        ];
    }
}
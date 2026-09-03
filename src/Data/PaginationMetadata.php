<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

final class PaginationMetadata
{
    public function __construct(
        private readonly int $currentPage,
        private readonly int $lastPage,
        private readonly int $perPage,
        private readonly int $total,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, int $itemCount = 0): self
    {
        $total = max(0, (int) ($data['total'] ?? $data['count'] ?? $itemCount));
        $perPage = max(1, (int) ($data['per_page'] ?? $data['page_size'] ?? max($itemCount, 1)));
        $lastPage = max(1, (int) ($data['last_page'] ?? (int) ceil($total / $perPage)));

        return new self(
            currentPage: max(1, (int) ($data['current_page'] ?? $data['page'] ?? 1)),
            lastPage: $lastPage,
            perPage: $perPage,
            total: $total,
        );
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->lastPage;
    }
}

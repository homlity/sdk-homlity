<?php

declare(strict_types=1);

namespace Homlity\Sdk\Filter;

use Homlity\Sdk\Exception\ValidationException;

final class TicketFilters
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $role = null,
        public readonly ?bool $finalized = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $priorityId = null,
        public readonly ?int $propertyId = null,
        public readonly ?string $deadlineFrom = null,
        public readonly ?string $deadlineTo = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {
        $this->validate();
    }

    /** @return array<string, scalar> */
    public function toQuery(): array
    {
        $query = [
            'q' => $this->cleanString($this->search),
            'role' => $this->role,
            'finalizado' => $this->finalized === null ? null : ($this->finalized ? '1' : '0'),
            'tipo' => $this->categoryId,
            'prioridad' => $this->priorityId,
            'inmueble' => $this->propertyId,
            'fecha_limite_desde' => $this->deadlineFrom,
            'fecha_limite_hasta' => $this->deadlineTo,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];

        return array_filter($query, static fn ($value) => $value !== null && $value !== '');
    }

    private function validate(): void
    {
        if ($this->role !== null && !in_array($this->role, ['owner', 'observer'], true)) {
            throw new ValidationException('Ticket role must be `owner` or `observer`.');
        }
        if ($this->page < 1 || $this->perPage < 1 || $this->perPage > 100) {
            throw new ValidationException('Ticket pagination is outside the supported range.');
        }

        foreach ([$this->categoryId, $this->priorityId, $this->propertyId] as $id) {
            if ($id !== null && $id <= 0) {
                throw new ValidationException('Ticket filter IDs must be positive integers.');
            }
        }

        foreach ([$this->deadlineFrom, $this->deadlineTo] as $date) {
            if ($date !== null && !$this->isDate($date)) {
                throw new ValidationException('Ticket deadline filters must use YYYY-MM-DD.');
            }
        }

        if ($this->deadlineFrom !== null && $this->deadlineTo !== null && $this->deadlineFrom > $this->deadlineTo) {
            throw new ValidationException('Ticket deadline start cannot be after its end.');
        }
    }

    private function isDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function cleanString(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }
}

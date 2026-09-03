<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

use Homlity\Sdk\Exception\ValidationException;

final class TicketCategory
{
    /** @param array<string, mixed> $raw */
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly ?string $description,
        private readonly ?int $parentId,
        private readonly array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $name = $data['nombre'] ?? '';
        $description = $data['descripcion'] ?? null;
        $parentId = $data['id_padre'] ?? null;

        return new self(
            self::positiveInteger($data['id'] ?? null, 'Ticket category'),
            is_scalar($name) ? (string) $name : '',
            is_scalar($description) ? (string) $description : null,
            $parentId === null ? null : self::positiveInteger($parentId, 'Ticket category parent'),
            $data,
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function parentId(): ?int
    {
        return $this->parentId;
    }

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->raw;
    }

    private static function positiveInteger(mixed $value, string $subject): int
    {
        if (!is_int($value) && !is_string($value)) {
            throw new ValidationException(sprintf('%s data is missing a valid positive `id` field.', $subject));
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new ValidationException(sprintf('%s data is missing a valid positive `id` field.', $subject));
        }

        return $id;
    }
}

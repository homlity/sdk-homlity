<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

use Homlity\Sdk\Exception\ValidationException;

final class Channel
{
    /** @param array<string, mixed> $raw */
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = self::positiveInteger($data['id'] ?? null);
        $name = $data['nombre'] ?? '';

        return new self(
            $id,
            is_scalar($name) ? (string) $name : '',
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

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->raw;
    }

    private static function positiveInteger(mixed $value): int
    {
        if (!is_int($value) && !is_string($value)) {
            throw new ValidationException('Channel data is missing a valid positive `id` field.');
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new ValidationException('Channel data is missing a valid positive `id` field.');
        }

        return $id;
    }
}

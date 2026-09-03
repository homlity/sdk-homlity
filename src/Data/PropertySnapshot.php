<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

final class PropertySnapshot
{
    /** @param array<string, mixed> $raw */
    private function __construct(
        private readonly int $id,
        private readonly ?string $code,
        private readonly array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new \InvalidArgumentException('Property data is missing a valid `id` field.');
        }

        $code = $data['code'] ?? $data['codigo_inmueble'] ?? null;

        return new self((int) $id, is_scalar($code) ? (string) $code : null, $data);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->raw;
    }
}

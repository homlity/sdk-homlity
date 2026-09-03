<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

final class LeadSnapshot
{
    /** @param array<string, mixed> $raw */
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly ?int $clientId,
        private readonly array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new \InvalidArgumentException('Lead data is missing a valid `id` field.');
        }

        $clientId = $data['converted_cliente_id'] ?? null;

        return new self(
            (int) $id,
            (string) ($data['nombre'] ?? ''),
            is_numeric($clientId) ? (int) $clientId : null,
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

    public function clientId(): ?int
    {
        return $this->clientId;
    }

    /** @return array<string, mixed>|null */
    public function relatedProperty(): ?array
    {
        $property = $this->raw['inmueble'] ?? ($this->raw['requerimiento']['inmueble_relacionado'] ?? null);

        return is_array($property) ? $property : null;
    }

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->raw;
    }
}

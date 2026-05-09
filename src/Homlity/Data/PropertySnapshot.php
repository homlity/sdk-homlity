<?php

declare(strict_types=1);

namespace Homlity\Sdk\Homlity\Data;

final class PropertySnapshot
{
    /**
     * @param array<string, mixed> $data
     */
    private function __construct(private readonly array $data)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function id(): ?string
    {
        return isset($this->data['id']) ? (string) $this->data['id'] : null;
    }

    public function status(): ?string
    {
        return isset($this->data['status']) ? (string) $this->data['status'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}

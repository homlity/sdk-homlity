<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

final class TicketSnapshot
{
    /** @param array<string, mixed> $raw */
    private function __construct(
        private readonly int $id,
        private readonly ?string $subject,
        private readonly array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? $data['uui'] ?? null;
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new \InvalidArgumentException('Ticket data is missing a valid `id` field.');
        }

        $subject = $data['titulo'] ?? $data['subject'] ?? null;

        return new self((int) $id, is_string($subject) ? $subject : null, $data);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function subject(): ?string
    {
        return $this->subject;
    }

    public function isFinalized(): bool
    {
        return !empty($this->raw['fecha_finalizacion']);
    }

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->raw;
    }
}

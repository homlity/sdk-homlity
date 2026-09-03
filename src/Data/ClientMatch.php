<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

final class ClientMatch
{
    /**
     * @param array<string, mixed>|null $documentType
     * @param array<string, mixed>|null $status
     * @param list<array<string, mixed>> $roles
     */
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $maskedDocument,
        private readonly ?array $documentType,
        private readonly ?array $status,
        private readonly array $roles,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new \InvalidArgumentException('Client data is missing a valid `id` field.');
        }

        $document = (string) ($data['identification'] ?? '');
        $length = function_exists('mb_strlen') ? mb_strlen($document) : strlen($document);
        $visible = $length > 4
            ? (function_exists('mb_substr') ? mb_substr($document, -4) : substr($document, -4))
            : '';
        $visibleLength = function_exists('mb_strlen') ? mb_strlen($visible) : strlen($visible);
        $masked = str_repeat('*', max(4, $length - $visibleLength)) . $visible;
        $documentType = isset($data['type_identification']) && is_array($data['type_identification'])
            ? $data['type_identification']
            : null;
        $status = isset($data['status']) && is_array($data['status']) ? $data['status'] : null;
        $roles = isset($data['rols']) && is_array($data['rols']) ? array_values($data['rols']) : [];

        return new self(
            (int) $id,
            (string) ($data['full_name'] ?? $data['short_name'] ?? ''),
            $masked,
            $documentType,
            $status,
            $roles,
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

    public function maskedDocument(): string
    {
        return $this->maskedDocument;
    }

    /** @return array<string, mixed>|null */
    public function documentType(): ?array
    {
        return $this->documentType;
    }

    /** @return array<string, mixed>|null */
    public function status(): ?array
    {
        return $this->status;
    }

    /** @return list<array<string, mixed>> */
    public function roles(): array
    {
        return $this->roles;
    }
}

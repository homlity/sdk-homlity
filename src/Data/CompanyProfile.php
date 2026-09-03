<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

final class CompanyProfile
{
    /**
     * @param array<string, mixed> $businessHours
     * @param array<string, mixed> $raw
     */
    private function __construct(
        private readonly int $id,
        private readonly ?string $name,
        private readonly ?string $phone,
        private readonly ?string $email,
        private readonly ?string $address,
        private readonly ?string $city,
        private readonly array $businessHours,
        private readonly ?string $publicUrl,
        private readonly array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new \InvalidArgumentException('Company profile is missing a valid `id` field.');
        }

        $businessHours = $data['business_hours'] ?? [];
        if (!is_array($businessHours)) {
            $businessHours = [];
        }

        // Keep raw() useful without allowing unrelated or sensitive response
        // fields to become part of this public DTO by accident.
        $raw = array_intersect_key($data, array_flip([
            'id',
            'name',
            'phone',
            'email',
            'address',
            'city',
            'business_hours',
            'public_url',
        ]));

        return new self(
            id: (int) $id,
            name: self::stringOrNull($data['name'] ?? null),
            phone: self::stringOrNull($data['phone'] ?? null),
            email: self::stringOrNull($data['email'] ?? null),
            address: self::stringOrNull($data['address'] ?? null),
            city: self::stringOrNull($data['city'] ?? null),
            businessHours: $businessHours,
            publicUrl: self::stringOrNull($data['public_url'] ?? null),
            raw: $raw,
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    /** @return array<string, mixed> */
    public function businessHours(): array
    {
        return $this->businessHours;
    }

    public function publicUrl(): ?string
    {
        return $this->publicUrl;
    }

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->raw;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}

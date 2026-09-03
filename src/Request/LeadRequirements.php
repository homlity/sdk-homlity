<?php

declare(strict_types=1);

namespace Homlity\Sdk\Request;

use Homlity\Sdk\Exception\ValidationException;

final class LeadRequirements
{
    /** @param list<int> $propertyTypeIds */
    public function __construct(
        public readonly ?float $budgetMin = null,
        public readonly ?float $budgetMax = null,
        public readonly ?string $rooms = null,
        public readonly ?string $bathrooms = null,
        public readonly ?string $parkingSpaces = null,
        public readonly ?float $areaMin = null,
        public readonly ?float $areaMax = null,
        public readonly ?string $stratum = null,
        public readonly ?int $ageMin = null,
        public readonly ?int $ageMax = null,
        public readonly ?string $businessType = null,
        public readonly ?string $propertyType = null,
        public readonly array $propertyTypeIds = [],
        public readonly ?int $cityId = null,
        public readonly ?int $neighborhoodId = null,
    ) {
        $this->assertRange($this->budgetMin, $this->budgetMax, 'budget');
        $this->assertRange($this->areaMin, $this->areaMax, 'area');

        if (($this->ageMin !== null && ($this->ageMin < 0 || $this->ageMin > 500))
            || ($this->ageMax !== null && ($this->ageMax < 0 || $this->ageMax > 500))) {
            throw new ValidationException('Lead property age must be between 0 and 500.');
        }
        if ($this->ageMin !== null && $this->ageMax !== null && $this->ageMin > $this->ageMax) {
            throw new ValidationException('Lead minimum property age cannot exceed maximum.');
        }
        if ($this->businessType !== null && !in_array($this->businessType, ['venta', 'arriendo'], true)) {
            throw new ValidationException('Lead business type must be `venta` or `arriendo`.');
        }
        foreach ([$this->rooms, $this->bathrooms, $this->parkingSpaces] as $value) {
            if ($value !== null && strlen(trim($value)) > 50) {
                throw new ValidationException('Lead room, bathroom and parking filters cannot exceed 50 characters.');
            }
        }
        if ($this->stratum !== null && strlen(trim($this->stratum)) > 10) {
            throw new ValidationException('Lead stratum cannot exceed 10 characters.');
        }
        if ($this->propertyType !== null && strlen(trim($this->propertyType)) > 100) {
            throw new ValidationException('Lead property type cannot exceed 100 characters.');
        }
        if (count($this->propertyTypeIds) > 30) {
            throw new ValidationException('A lead can contain at most 30 property type IDs.');
        }
        foreach ($this->propertyTypeIds as $id) {
            if (!is_int($id) || $id <= 0) {
                throw new ValidationException('Lead property type IDs must be positive integers.');
            }
        }
        foreach ([$this->cityId, $this->neighborhoodId] as $id) {
            if ($id !== null && $id <= 0) {
                throw new ValidationException('Lead location IDs must be positive integers.');
            }
        }
        if ($this->neighborhoodId !== null && $this->cityId === null) {
            throw new ValidationException('Lead city ID is required when a neighborhood is provided.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'valor_desde' => $this->budgetMin,
            'valor_hasta' => $this->budgetMax,
            'habitaciones' => $this->clean($this->rooms),
            'banos' => $this->clean($this->bathrooms),
            'parqueaderos' => $this->clean($this->parkingSpaces),
            'area_desde' => $this->areaMin,
            'area_hasta' => $this->areaMax,
            'estrato' => $this->clean($this->stratum),
            'anos_minimo' => $this->ageMin,
            'anos_maximo' => $this->ageMax,
            'tipo_negocio' => $this->businessType,
            'tipo_inmueble' => $this->clean($this->propertyType),
            'tipos_inmueble' => $this->propertyTypeIds === [] ? null : array_values($this->propertyTypeIds),
            'ciudad' => $this->cityId,
            'barrio' => $this->neighborhoodId,
        ], static fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    private function assertRange(?float $minimum, ?float $maximum, string $name): void
    {
        if (($minimum !== null && $minimum < 0) || ($maximum !== null && $maximum < 0)) {
            throw new ValidationException(sprintf('Lead %s cannot be negative.', $name));
        }
        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            throw new ValidationException(sprintf('Lead minimum %s cannot exceed maximum.', $name));
        }
    }

    private function clean(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }
}

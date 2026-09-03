<?php

declare(strict_types=1);

namespace Homlity\Sdk\Filter;

use Homlity\Sdk\Exception\ValidationException;

final class PropertyFilters
{
    /**
     * @param list<int> $statuses
     * @param list<int> $propertyTypeIds
     * @param array<string, scalar> $tags
     */
    public function __construct(
        public readonly ?string $search = null,
        public readonly array $statuses = [],
        public readonly array $propertyTypeIds = [],
        public readonly ?int $businessTypeId = null,
        public readonly ?int $cityId = null,
        public readonly ?int $neighborhoodId = null,
        public readonly ?int $adviserId = null,
        public readonly ?int $stratum = null,
        public readonly ?int $rooms = null,
        public readonly ?int $bathrooms = null,
        public readonly ?int $parkingSpaces = null,
        public readonly ?float $rentPriceMin = null,
        public readonly ?float $rentPriceMax = null,
        public readonly ?float $salePriceMin = null,
        public readonly ?float $salePriceMax = null,
        public readonly ?float $builtAreaMin = null,
        public readonly ?float $builtAreaMax = null,
        public readonly array $tags = [],
        public readonly ?string $origin = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {
        $this->validate();
    }

    /** @return array<string, scalar|array<scalar>> */
    public function toQuery(): array
    {
        $query = [
            'q' => $this->cleanString($this->search),
            'status' => $this->statuses === [] ? null : array_values($this->statuses),
            'tipo_inmueble' => $this->propertyTypeIds === [] ? null : array_values($this->propertyTypeIds),
            'tipo_gestion' => $this->businessTypeId,
            'ciudad' => $this->cityId,
            'barrio' => $this->neighborhoodId,
            'asesor' => $this->adviserId,
            'estrato' => $this->stratum,
            'n_cuartos' => $this->rooms,
            'n_banos' => $this->bathrooms,
            'n_parqueaderos' => $this->parkingSpaces,
            'arriendo_desde' => $this->rentPriceMin,
            'arriendo_hasta' => $this->rentPriceMax,
            'venta_desde' => $this->salePriceMin,
            'venta_hasta' => $this->salePriceMax,
            'area_desde' => $this->builtAreaMin,
            'area_hasta' => $this->builtAreaMax,
            'tags' => $this->tags === [] ? null : $this->tags,
            'origin' => $this->cleanString($this->origin),
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];

        return array_filter($query, static fn ($value) => $value !== null && $value !== '');
    }

    private function validate(): void
    {
        if ($this->page < 1) {
            throw new ValidationException('Property page must be greater than zero.');
        }
        if ($this->perPage < 1 || $this->perPage > 100) {
            throw new ValidationException('Property per-page value must be between 1 and 100.');
        }

        foreach ([$this->statuses, $this->propertyTypeIds] as $ids) {
            foreach ($ids as $id) {
                if (!is_int($id) || $id <= 0) {
                    throw new ValidationException('Property filter IDs must be positive integers.');
                }
            }
        }

        foreach ([$this->businessTypeId, $this->cityId, $this->neighborhoodId, $this->adviserId] as $id) {
            if ($id !== null && $id <= 0) {
                throw new ValidationException('Property relation filter IDs must be positive integers.');
            }
        }

        foreach ([$this->stratum, $this->rooms, $this->bathrooms, $this->parkingSpaces] as $number) {
            if ($number !== null && $number < 0) {
                throw new ValidationException('Property numeric filters cannot be negative.');
            }
        }

        $this->assertRange($this->rentPriceMin, $this->rentPriceMax, 'rent price');
        $this->assertRange($this->salePriceMin, $this->salePriceMax, 'sale price');
        $this->assertRange($this->builtAreaMin, $this->builtAreaMax, 'built area');

        foreach ($this->tags as $key => $value) {
            if (!is_string($key) || preg_match('/^[A-Za-z0-9_.-]+$/', $key) !== 1 || !is_scalar($value)) {
                throw new ValidationException('Property tags must use safe string keys and scalar values.');
            }
        }
    }

    private function assertRange(?float $minimum, ?float $maximum, string $name): void
    {
        if (($minimum !== null && $minimum < 0) || ($maximum !== null && $maximum < 0)) {
            throw new ValidationException(sprintf('Property %s values cannot be negative.', $name));
        }
        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            throw new ValidationException(sprintf('Property %s minimum cannot exceed maximum.', $name));
        }
    }

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}

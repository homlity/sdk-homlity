<?php

declare(strict_types=1);

namespace Homlity\Sdk\Api;

use Homlity\Sdk\Data\PaginatedResult;
use Homlity\Sdk\Data\PropertySnapshot;
use Homlity\Sdk\Exception\ValidationException;
use Homlity\Sdk\Filter\PropertyFilters;
use Homlity\Sdk\Support\ResponseData;

final class PropertiesApi extends BaseApi
{
    /** @return PaginatedResult<PropertySnapshot> */
    public function list(?PropertyFilters $filters = null): PaginatedResult
    {
        $filters ??= new PropertyFilters();
        $response = $this->send('GET', '/v1/propertys', ['query' => $filters->toQuery()]);

        return PaginatedResult::fromApiResponse($response, PropertySnapshot::fromArray(...));
    }

    /** @return PaginatedResult<PropertySnapshot> */
    public function search(PropertyFilters $filters): PaginatedResult
    {
        return $this->list($filters);
    }

    /**
     * Fetches an integration-safe property representation. The backend accepts
     * either its numeric ID or the agency's property code.
     */
    public function get(int|string $property): PropertySnapshot
    {
        $value = trim((string) $property);
        if ($value === '') {
            throw new ValidationException('Property ID or code is required.');
        }

        $response = $this->send('GET', '/v1/integrations/properties/' . $this->encodePath($value));

        return PropertySnapshot::fromArray(ResponseData::object($response));
    }

    public function getByCode(string $code): PropertySnapshot
    {
        return $this->get($code);
    }
}

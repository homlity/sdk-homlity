<?php

declare(strict_types=1);

namespace Homlity\Sdk\Homlity\Api;

use Homlity\Sdk\Homlity\Exception\ApiException;
use Homlity\Sdk\Homlity\Http\HttpClientInterface;

abstract class BaseApi
{
    public function __construct(protected readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * @param array{
     *   query?: array<string, scalar|array<scalar>|null>,
     *   headers?: array<string, string>,
     *   json?: mixed,
     *   body?: string
     * } $options
     */
    protected function send(string $method, string $path, array $options = []): mixed
    {
        $response = $this->httpClient->request($method, $path, $options);

        if (!$response->isSuccessful()) {
            throw ApiException::fromResponse(strtoupper($method), $path, $response);
        }

        return $response->json() ?? $response->body();
    }

    protected function encodePath(string $value): string
    {
        return rawurlencode($value);
    }

    /**
     * @param array<int|string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeBatchPayload(array $payload): array
    {
        if ($payload === []) {
            return [];
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        return [$payload];
    }
}

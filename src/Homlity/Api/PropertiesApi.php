<?php

declare(strict_types=1);

namespace Homlity\Sdk\Homlity\Api;

use Homlity\Sdk\Homlity\Config;
use Homlity\Sdk\Homlity\Exception\ApiException;
use Homlity\Sdk\Homlity\Http\ApiResponse;
use Homlity\Sdk\Homlity\Schema\PropertyPayloadNormalizer;
use Homlity\Sdk\Homlity\Schema\PropertyPayloadValidator;

final class PropertiesApi extends BaseApi
{
    public function __construct(
        \Homlity\Sdk\Homlity\Http\HttpClientInterface $httpClient,
        private readonly Config $config,
        private readonly PropertyPayloadValidator $validator,
        private readonly PropertyPayloadNormalizer $normalizer,
    ) {
        parent::__construct($httpClient);
    }

    /**
     * @param array<int|string, mixed> $properties
     */
    public function push(array $properties): mixed
    {
        $batch = $this->normalizeBatchPayload($properties);
        $this->validator->validateUpsertPayload($batch);

        $normalized = array_map(fn (array $property): array => $this->normalizer->normalize($property), $batch);
        $payload = json_encode($normalized, JSON_THROW_ON_ERROR);

        return $this->sendSignedPropertyRequest('POST', '/wp-json/homlity-sync/v1/properties', $payload);
    }

    public function deactivate(string $externalId, array $payload = []): mixed
    {
        $rawPayload = $payload === []
            ? ''
            : json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->sendSignedPropertyRequest(
            'POST',
            '/wp-json/homlity-sync/v1/properties/' . $this->encodePath($externalId) . '/deactivate',
            $rawPayload
        );
    }

    private function sendSignedPropertyRequest(string $method, string $path, string $rawJsonBody): mixed
    {
        $response = $this->requestWithSignature($method, $path, $rawJsonBody, $this->config->signPropertyRequests());

        if (
            !$this->config->signPropertyRequests()
            && $this->config->retrySignedOnSignatureRequired()
            && $this->isSignatureRequiredResponse($response)
        ) {
            $response = $this->requestWithSignature($method, $path, $rawJsonBody, true);
        }

        if (!$response->isSuccessful()) {
            throw ApiException::fromResponse($method, $path, $response);
        }

        return $response->json() ?? $response->body();
    }

    private function requestWithSignature(string $method, string $path, string $rawJsonBody, bool $signRequest): ApiResponse
    {
        $headers = [];

        if ($rawJsonBody !== '') {
            $headers['Content-Type'] = 'application/json';
        }

        if ($signRequest) {
            $headers['X-Homlity-Token'] = $this->config->apiKey();
            $headers['X-Homlity-Signature'] = 'sha256=' . hash_hmac('sha256', $rawJsonBody, $this->config->apiKey());
        }

        return $this->httpClient->request($method, $path, [
            'headers' => $headers,
            'body' => $rawJsonBody,
        ]);
    }

    private function isSignatureRequiredResponse(ApiResponse $response): bool
    {
        return $response->statusCode() === 401
            && stripos($response->body(), 'firma requerida') !== false;
    }
}

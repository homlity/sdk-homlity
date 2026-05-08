<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk\Homlity\Api;

use Fincaraiz\Sdk\Homlity\Schema\PropertyPayloadNormalizer;
use Fincaraiz\Sdk\Homlity\Schema\PropertyPayloadValidator;

final class PropertiesApi extends BaseApi
{
    public function __construct(
        \Fincaraiz\Sdk\Homlity\Http\HttpClientInterface $httpClient,
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

        return $this->send('POST', '/wp-json/homlity-sync/v1/properties', ['json' => $normalized]);
    }

    public function deactivate(string $externalId, array $payload = []): mixed
    {
        return $this->send(
            'POST',
            '/wp-json/homlity-sync/v1/properties/' . $this->encodePath($externalId) . '/deactivate',
            ['json' => $payload]
        );
    }
}

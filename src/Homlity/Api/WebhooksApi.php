<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk\Homlity\Api;

use Fincaraiz\Sdk\Homlity\Schema\PropertyPayloadValidator;

final class WebhooksApi extends BaseApi
{
    public function __construct(
        \Fincaraiz\Sdk\Homlity\Http\HttpClientInterface $httpClient,
        private readonly PropertyPayloadValidator $validator,
    ) {
        parent::__construct($httpClient);
    }

    public function notify(string $event, string $propertyId): mixed
    {
        $payload = [['event' => $event, 'property_id' => $propertyId]];
        $this->validator->validateWebhookPayload($payload);

        return $this->send('POST', '/wp-json/homlity-sync/v1/webhook', ['json' => $payload[0]]);
    }
}

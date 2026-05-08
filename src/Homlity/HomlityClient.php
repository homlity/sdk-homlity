<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk\Homlity;

use Fincaraiz\Sdk\Homlity\Api\PropertiesApi;
use Fincaraiz\Sdk\Homlity\Api\WebhooksApi;
use Fincaraiz\Sdk\Homlity\Http\CurlHttpClient;
use Fincaraiz\Sdk\Homlity\Http\HttpClientInterface;
use Fincaraiz\Sdk\Homlity\Schema\PropertyPayloadNormalizer;
use Fincaraiz\Sdk\Homlity\Schema\PropertyPayloadValidator;

final class HomlityClient
{
    private readonly HttpClientInterface $httpClient;

    private ?PropertiesApi $properties = null;
    private ?WebhooksApi $webhooks = null;

    public function __construct(
        private readonly Config $config,
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new CurlHttpClient($config);
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function properties(): PropertiesApi
    {
        if ($this->properties === null) {
            $this->properties = new PropertiesApi(
                $this->httpClient,
                new PropertyPayloadValidator(),
                new PropertyPayloadNormalizer(),
            );
        }

        return $this->properties;
    }

    public function webhooks(): WebhooksApi
    {
        if ($this->webhooks === null) {
            $this->webhooks = new WebhooksApi($this->httpClient, new PropertyPayloadValidator());
        }

        return $this->webhooks;
    }
}

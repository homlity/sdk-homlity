<?php

declare(strict_types=1);

namespace Homlity\Sdk\Homlity;

use Homlity\Sdk\Homlity\Api\AnalyticsApi;
use Homlity\Sdk\Homlity\Api\PropertiesApi;
use Homlity\Sdk\Homlity\Api\WebhooksApi;
use Homlity\Sdk\Homlity\Http\CurlHttpClient;
use Homlity\Sdk\Homlity\Http\HttpClientInterface;
use Homlity\Sdk\Homlity\Schema\PropertyPayloadNormalizer;
use Homlity\Sdk\Homlity\Schema\PropertyPayloadValidator;

final class HomlityClient
{
    private readonly HttpClientInterface $httpClient;

    private ?AnalyticsApi $analytics = null;
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
                $this->config,
                new PropertyPayloadValidator(),
                new PropertyPayloadNormalizer(),
            );
        }

        return $this->properties;
    }

    public function analytics(): AnalyticsApi
    {
        if ($this->analytics === null) {
            $this->analytics = new AnalyticsApi($this->httpClient);
        }

        return $this->analytics;
    }

    public function webhooks(): WebhooksApi
    {
        if ($this->webhooks === null) {
            $this->webhooks = new WebhooksApi($this->httpClient, new PropertyPayloadValidator());
        }

        return $this->webhooks;
    }
}

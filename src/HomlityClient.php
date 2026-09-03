<?php

declare(strict_types=1);

namespace Homlity\Sdk;

use Homlity\Sdk\Api\CategoriesApi;
use Homlity\Sdk\Api\ClientsApi;
use Homlity\Sdk\Api\ListingsApi;
use Homlity\Sdk\Api\LocationsApi;
use Homlity\Sdk\Api\LeadsApi;
use Homlity\Sdk\Api\PropertiesApi;
use Homlity\Sdk\Api\TasksApi;
use Homlity\Sdk\Api\TicketsApi;
use Homlity\Sdk\Api\WebhooksApi;
use Homlity\Sdk\Http\CurlHttpClient;
use Homlity\Sdk\Http\HttpClientInterface;
use Homlity\Sdk\Schema\ListingPayloadValidator;
use Homlity\Sdk\Schema\SchemaCatalog;

final class HomlityClient
{
    private readonly HttpClientInterface $httpClient;
    private readonly SchemaCatalog $schemaCatalog;

    private ?ListingsApi $listings = null;
    private ?ClientsApi $clients = null;
    private ?CategoriesApi $categories = null;
    private ?LocationsApi $locations = null;
    private ?PropertiesApi $properties = null;
    private ?TicketsApi $tickets = null;
    private ?LeadsApi $leads = null;
    private ?TasksApi $tasks = null;
    private ?WebhooksApi $webhooks = null;

    public function __construct(
        private readonly Config $config,
        ?HttpClientInterface $httpClient = null,
        ?SchemaCatalog $schemaCatalog = null
    ) {
        $this->httpClient = $httpClient ?? new CurlHttpClient($config);
        $this->schemaCatalog = $schemaCatalog ?? new SchemaCatalog();
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function schemaCatalog(): SchemaCatalog
    {
        return $this->schemaCatalog;
    }

    public function listings(): ListingsApi
    {
        if ($this->listings === null) {
            $this->listings = new ListingsApi(
                $this->httpClient,
                new ListingPayloadValidator($this->schemaCatalog)
            );
        }

        return $this->listings;
    }

    public function clients(): ClientsApi
    {
        if ($this->clients === null) {
            $this->clients = new ClientsApi($this->httpClient);
        }

        return $this->clients;
    }

    public function categories(): CategoriesApi
    {
        if ($this->categories === null) {
            $this->categories = new CategoriesApi($this->httpClient);
        }

        return $this->categories;
    }

    public function locations(): LocationsApi
    {
        if ($this->locations === null) {
            $this->locations = new LocationsApi($this->httpClient);
        }

        return $this->locations;
    }

    public function properties(): PropertiesApi
    {
        if ($this->properties === null) {
            $this->properties = new PropertiesApi($this->httpClient);
        }

        return $this->properties;
    }

    public function tickets(): TicketsApi
    {
        if ($this->tickets === null) {
            $this->tickets = new TicketsApi($this->httpClient);
        }

        return $this->tickets;
    }

    public function leads(): LeadsApi
    {
        if ($this->leads === null) {
            $this->leads = new LeadsApi($this->httpClient);
        }

        return $this->leads;
    }

    public function tasks(): TasksApi
    {
        if ($this->tasks === null) {
            $this->tasks = new TasksApi($this->httpClient);
        }

        return $this->tasks;
    }

    public function webhooks(): WebhooksApi
    {
        if ($this->webhooks === null) {
            $this->webhooks = new WebhooksApi($this->httpClient);
        }

        return $this->webhooks;
    }
}

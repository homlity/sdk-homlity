<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk;

use Fincaraiz\Sdk\Api\CategoriesApi;
use Fincaraiz\Sdk\Api\ClientsApi;
use Fincaraiz\Sdk\Api\ListingsApi;
use Fincaraiz\Sdk\Api\LocationsApi;
use Fincaraiz\Sdk\Api\TasksApi;
use Fincaraiz\Sdk\Api\WebhooksApi;
use Fincaraiz\Sdk\Http\CurlHttpClient;
use Fincaraiz\Sdk\Http\HttpClientInterface;
use Fincaraiz\Sdk\Schema\ListingPayloadValidator;
use Fincaraiz\Sdk\Schema\SchemaCatalog;

final class FincaRaizClient
{
    private readonly HttpClientInterface $httpClient;
    private readonly SchemaCatalog $schemaCatalog;

    private ?ListingsApi $listings = null;
    private ?ClientsApi $clients = null;
    private ?CategoriesApi $categories = null;
    private ?LocationsApi $locations = null;
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

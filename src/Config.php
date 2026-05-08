<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk;

final class Config
{
    public const BASE_URL_PRODUCTION = 'https://kong.fincaraiz.com.co/management/api/1.0';
    public const BASE_URL_QA = 'https://kong-qa.frcol.io/management/api/1.0';
    public const BASE_URL_MOCK = 'https://virtserver.swaggerhub.com/Fincaraiz.com.co/Integradores/1.0.0';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = self::BASE_URL_PRODUCTION,
        private readonly int $timeoutSeconds = 30
    ) {
        if ($this->apiKey === '') {
            throw new \InvalidArgumentException('API key is required.');
        }

        if ($this->timeoutSeconds <= 0) {
            throw new \InvalidArgumentException('Timeout must be greater than zero.');
        }
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function baseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }
}

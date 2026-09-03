<?php

declare(strict_types=1);

namespace Homlity\Sdk;

final class Config
{
    public const BASE_URL_PRODUCTION = 'https://kong.homlity.com.co/management/api/1.0';
    public const BASE_URL_QA = 'https://kong-qa.frcol.io/management/api/1.0';
    public const BASE_URL_MOCK = 'https://virtserver.swaggerhub.com/Homlity.com.co/Integradores/1.0.0';
    public const BASE_URL_TENANT_PRODUCTION = 'https://web.homlity.com/api';

    public const AUTH_API_KEY = 'api_key';
    public const AUTH_BEARER = 'bearer';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = self::BASE_URL_PRODUCTION,
        private readonly int $timeoutSeconds = 30,
        private readonly string $authMode = self::AUTH_API_KEY
    ) {
        if ($this->apiKey === '') {
            throw new \InvalidArgumentException('API key is required.');
        }

        if ($this->timeoutSeconds <= 0) {
            throw new \InvalidArgumentException('Timeout must be greater than zero.');
        }

        if (!\in_array($this->authMode, [self::AUTH_API_KEY, self::AUTH_BEARER], true)) {
            throw new \InvalidArgumentException('Authentication mode must be `api_key` or `bearer`.');
        }
    }

    /**
     * Configures the tenant-scoped Homlity API. The access token determines
     * the authenticated user and real-estate agency on the backend.
     */
    public static function forTenantApi(
        string $accessToken,
        string $baseUrl = self::BASE_URL_TENANT_PRODUCTION,
        int $timeoutSeconds = 30
    ): self {
        return new self(
            apiKey: $accessToken,
            baseUrl: $baseUrl,
            timeoutSeconds: $timeoutSeconds,
            authMode: self::AUTH_BEARER,
        );
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

    public function authMode(): string
    {
        return $this->authMode;
    }

    /** @return array<string, string> */
    public function authenticationHeaders(): array
    {
        if ($this->authMode === self::AUTH_BEARER) {
            return ['Authorization' => 'Bearer ' . $this->apiKey];
        }

        return [
            'apikey' => $this->apiKey,
            'X-API-KEY' => $this->apiKey,
        ];
    }

    /** @return array<string, int|string> */
    public function __debugInfo(): array
    {
        return [
            'apiKey' => '[REDACTED]',
            'baseUrl' => $this->baseUrl,
            'timeoutSeconds' => $this->timeoutSeconds,
            'authMode' => $this->authMode,
        ];
    }
}

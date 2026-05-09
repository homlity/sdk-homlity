<?php

declare(strict_types=1);

namespace Homlity\Sdk\Homlity;

final class Config
{
    public const BASE_URL_PRODUCTION = 'https://example.com';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds = 30,
        private readonly bool $signPropertyRequests = true,
        private readonly bool $retrySignedOnSignatureRequired = true
    ) {
        if ($this->apiKey === '') {
            throw new \InvalidArgumentException('API key is required.');
        }

        if ($this->baseUrl === '') {
            throw new \InvalidArgumentException('Base URL is required.');
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

    public function signPropertyRequests(): bool
    {
        return $this->signPropertyRequests;
    }

    public function retrySignedOnSignatureRequired(): bool
    {
        return $this->retrySignedOnSignatureRequired;
    }
}

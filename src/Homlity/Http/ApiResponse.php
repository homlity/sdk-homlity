<?php

declare(strict_types=1);

namespace Homlity\Sdk\Homlity\Http;

final class ApiResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly string $body
    ) {
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function json(): mixed
    {
        if (trim($this->body) === '') {
            return null;
        }

        return json_decode($this->body, true);
    }
}

<?php

declare(strict_types=1);

namespace Homlity\Sdk\Homlity\Webhook;

use Homlity\Sdk\Homlity\Exception\WebhookException;

final class WebhookNotification
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $payload
     */
    private function __construct(
        private readonly string $rawBody,
        private readonly array $headers,
        private readonly array $payload
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public static function fromRequest(string $rawBody, array $headers = []): self
    {
        $trimmedBody = trim($rawBody);
        if ($trimmedBody === '') {
            throw new WebhookException('Webhook body is empty.');
        }

        try {
            $payload = json_decode($trimmedBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new WebhookException('Webhook body must be valid JSON.', 0, $exception);
        }

        if (!is_array($payload)) {
            throw new WebhookException('Webhook payload must decode to an object.');
        }

        return new self($rawBody, self::normalizeHeaders($headers), $payload);
    }

    public function assertAuthorizedSignature(string $secret, string $headerName = 'x-homlity-signature'): void
    {
        $signatureHeader = $this->header($headerName);
        if (!is_string($signatureHeader) || $signatureHeader === '') {
            throw new WebhookException(sprintf('Missing webhook signature header `%s`.', $headerName));
        }

        if (!str_starts_with($signatureHeader, 'sha256=')) {
            throw new WebhookException('Invalid webhook signature format. Expected `sha256=<hex_hmac>`.');
        }

        $expected = 'sha256=' . hash_hmac('sha256', $this->rawBody, $secret);
        if (!hash_equals($expected, strtolower($signatureHeader))) {
            throw new WebhookException('Webhook signature validation failed.');
        }
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower(trim($name))] = trim($value);
        }

        return $normalized;
    }
}

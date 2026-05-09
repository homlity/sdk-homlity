<?php

declare(strict_types=1);

namespace Homlity\Sdk\Homlity\Exception;

use Homlity\Sdk\Homlity\Http\ApiResponse;

final class ApiException extends \RuntimeException
{
    public static function fromResponse(string $method, string $path, ApiResponse $response): self
    {
        $message = sprintf(
            'Homlity API request failed: %s %s returned HTTP %d. Body: %s',
            strtoupper($method),
            $path,
            $response->statusCode(),
            $response->body()
        );

        if ($response->statusCode() === 401 && stripos($response->body(), 'firma requerida') !== false) {
            $message .= ' Hint: this endpoint requires request signing; send X-Homlity-Token and X-Homlity-Signature.';
        }

        return new self($message);
    }
}

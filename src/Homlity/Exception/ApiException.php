<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk\Homlity\Exception;

use Fincaraiz\Sdk\Homlity\Http\ApiResponse;

final class ApiException extends \RuntimeException
{
    public static function fromResponse(string $method, string $path, ApiResponse $response): self
    {
        return new self(sprintf(
            'Homlity API request failed: %s %s returned HTTP %d. Body: %s',
            strtoupper($method),
            $path,
            $response->statusCode(),
            $response->body()
        ));
    }
}

<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk\Homlity\Http;

use Fincaraiz\Sdk\Homlity\Config;
use Fincaraiz\Sdk\Homlity\Exception\TransportException;

final class CurlHttpClient implements HttpClientInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function request(string $method, string $path, array $options = []): ApiResponse
    {
        $url = $this->buildUrl($path, $options['query'] ?? []);
        $ch = curl_init($url);
        if ($ch === false) {
            throw new TransportException('Unable to initialize cURL.');
        }

        $responseHeaders = [];
        $headers = $this->buildHeaders($options['headers'] ?? [], isset($options['json']));

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_CONNECTTIMEOUT => $this->config->timeoutSeconds(),
            CURLOPT_TIMEOUT => $this->config->timeoutSeconds(),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$responseHeaders): int {
                $trimmed = trim($headerLine);
                if ($trimmed === '' || !str_contains($trimmed, ':')) {
                    return strlen($headerLine);
                }

                [$name, $value] = explode(':', $trimmed, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);
                return strlen($headerLine);
            },
        ]);

        if (array_key_exists('json', $options)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['json'], JSON_THROW_ON_ERROR));
        } elseif (array_key_exists('body', $options)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string) $options['body']);
        }

        $responseBody = curl_exec($ch);
        if ($responseBody === false) {
            $error = curl_error($ch);
            $code = curl_errno($ch);
            curl_close($ch);
            throw new TransportException(sprintf('HTTP transport failed (%d): %s', $code, $error));
        }

        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return new ApiResponse($statusCode, $responseHeaders, (string) $responseBody);
    }

    /**
     * @param array<string, string> $customHeaders
     * @return array<int, string>
     */
    private function buildHeaders(array $customHeaders, bool $hasJsonBody): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->config->apiKey(),
            'X-API-KEY' => $this->config->apiKey(),
        ];

        if ($hasJsonBody) {
            $headers['Content-Type'] = 'application/json';
        }

        foreach ($customHeaders as $name => $value) {
            $headers[$name] = $value;
        }

        $formattedHeaders = [];
        foreach ($headers as $name => $value) {
            $formattedHeaders[] = sprintf('%s: %s', $name, $value);
        }

        return $formattedHeaders;
    }

    /**
     * @param array<string, scalar|array<scalar>|null> $query
     */
    private function buildUrl(string $path, array $query): string
    {
        $normalizedPath = str_starts_with($path, '/') ? $path : '/' . $path;
        $url = str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : $this->config->baseUrl() . $normalizedPath;

        if ($query !== []) {
            $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            if ($queryString !== '') {
                $url .= (str_contains($url, '?') ? '&' : '?') . $queryString;
            }
        }

        return $url;
    }
}

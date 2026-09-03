<?php

declare(strict_types=1);

namespace Homlity\Sdk\Support;

final class ResponseData
{
    /** @return array<string, mixed> */
    public static function object(mixed $response): array
    {
        if (!is_array($response)) {
            return [];
        }

        if (isset($response['data']) && is_array($response['data']) && !array_is_list($response['data'])) {
            return $response['data'];
        }

        return $response;
    }

    /** @return list<mixed> */
    public static function list(mixed $response): array
    {
        if (!is_array($response)) {
            return [];
        }

        $data = array_key_exists('data', $response) ? $response['data'] : $response;

        return is_array($data) && array_is_list($data) ? $data : [];
    }
}

<?php

declare(strict_types=1);

namespace Homlity\Sdk\Support;

final class DocumentNormalizer
{
    public static function normalize(string $document): string
    {
        $normalized = preg_replace('/\s+/u', '', trim($document)) ?? '';

        return function_exists('mb_strtoupper') ? mb_strtoupper($normalized) : strtoupper($normalized);
    }
}

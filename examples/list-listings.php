<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Homlity\Sdk\Config;
use Homlity\Sdk\HomlityClient;

$config = new Config(
    apiKey: getenv('FINCARAIZ_API_KEY') ?: '',
    baseUrl: Config::BASE_URL_PRODUCTION,
    timeoutSeconds: 30
);

$sdk = new HomlityClient($config);

$clientCookie = getenv('FINCARAIZ_CLIENT_COOKIE') ?: '';

$list = $sdk->listings()->list(
    clientCookie: $clientCookie,
    query: [
        'page' => 1,
        'page_size' => 10,
        'ordering' => '-created',
    ]
);

print_r($list);

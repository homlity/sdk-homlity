<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Homlity\Sdk\Config;
use Homlity\Sdk\HomlityClient;

$apiKey = getenv('FINCARAIZ_API_KEY') ?: '';
$webhookId = getenv('FINCARAIZ_WEBHOOK_ID') ?: '';
$targetUrl = getenv('FINCARAIZ_WEBHOOK_TARGET') ?: '';

$sdk = new HomlityClient(new Config($apiKey));

$response = $sdk->webhooks()->subscribeTarget($webhookId, $targetUrl);

print_r($response);

<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Fincaraiz\Sdk\Config;
use Fincaraiz\Sdk\FincaRaizClient;

$apiKey = getenv('FINCARAIZ_API_KEY') ?: '';
$webhookId = getenv('FINCARAIZ_WEBHOOK_ID') ?: '';
$targetUrl = getenv('FINCARAIZ_WEBHOOK_TARGET') ?: '';

$sdk = new FincaRaizClient(new Config($apiKey));

$response = $sdk->webhooks()->subscribeTarget($webhookId, $targetUrl);

print_r($response);

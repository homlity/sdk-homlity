<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Homlity\Sdk\Homlity\Config;
use Homlity\Sdk\Homlity\HomlityClient;

$client = new HomlityClient(new Config('api-key', 'https://tu-wp.com'));

$report = $client->analytics()->report([
    'from' => '2026-04-01',
    'to' => '2026-04-30',
    'advisor_id' => 25,
    'limit' => 20,
]);

print_r($report);

<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Homlity\Sdk\Homlity\Config;
use Homlity\Sdk\Homlity\HomlityClient;

$client = new HomlityClient(new Config('api-key', 'https://tu-wp.com'));
$response = $client->properties()->deactivate('12345');

var_dump($response);

<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Homlity\Sdk\Homlity\Webhook\WebhookNotification;

$rawBody = file_get_contents('php://input') ?: '';
$headers = function_exists('getallheaders') && is_array(getallheaders()) ? getallheaders() : [];

$notification = WebhookNotification::fromRequest($rawBody, $headers);
$notification->assertAuthorizedSignature($_ENV['HOMLITY_WEBHOOK_SECRET'] ?? '');

http_response_code(200);
echo json_encode(['ok' => true, 'event' => $notification->payload()['event'] ?? null]);

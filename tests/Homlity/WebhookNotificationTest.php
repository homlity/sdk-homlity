<?php

declare(strict_types=1);

namespace Homlity\Sdk\Tests\Homlity;

use Homlity\Sdk\Homlity\Exception\WebhookException;
use Homlity\Sdk\Homlity\Webhook\WebhookNotification;
use PHPUnit\Framework\TestCase;

final class WebhookNotificationTest extends TestCase
{
    public function testItValidatesHmacSignature(): void
    {
        $body = json_encode(['event' => 'property.created', 'property_id' => '12345'], JSON_THROW_ON_ERROR);
        $secret = 'secret-123';
        $signature = 'sha256=' . hash_hmac('sha256', $body, $secret);

        $notification = WebhookNotification::fromRequest($body, ['X-Homlity-Signature' => $signature]);
        $notification->assertAuthorizedSignature($secret);

        self::assertSame('property.created', $notification->payload()['event']);
    }

    public function testItFailsForInvalidSignature(): void
    {
        $body = json_encode(['event' => 'property.created', 'property_id' => '12345'], JSON_THROW_ON_ERROR);

        $notification = WebhookNotification::fromRequest($body, ['X-Homlity-Signature' => 'sha256=deadbeef']);

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Webhook signature validation failed.');
        $notification->assertAuthorizedSignature('secret-123');
    }
}

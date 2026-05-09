<?php

declare(strict_types=1);

namespace Homlity\Sdk\Tests;

use Homlity\Sdk\Webhook\WebhookSubscription;
use PHPUnit\Framework\TestCase;

final class WebhookSubscriptionTest extends TestCase
{
    public function testItBuildsTheExpectedSubscriptionPayload(): void
    {
        self::assertSame(
            ['target' => 'https://example.com/webhooks/homlity'],
            WebhookSubscription::target('https://example.com/webhooks/homlity')
        );
    }

    public function testItRejectsInvalidWebhookUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WebhookSubscription::target('/relative-path');
    }
}

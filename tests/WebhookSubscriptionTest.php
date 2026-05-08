<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk\Tests;

use Fincaraiz\Sdk\Webhook\WebhookSubscription;
use PHPUnit\Framework\TestCase;

final class WebhookSubscriptionTest extends TestCase
{
    public function testItBuildsTheExpectedSubscriptionPayload(): void
    {
        self::assertSame(
            ['target' => 'https://example.com/webhooks/fincaraiz'],
            WebhookSubscription::target('https://example.com/webhooks/fincaraiz')
        );
    }

    public function testItRejectsInvalidWebhookUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WebhookSubscription::target('/relative-path');
    }
}

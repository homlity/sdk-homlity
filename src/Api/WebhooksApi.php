<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk\Api;

use Fincaraiz\Sdk\Webhook\SubscriptionResult;
use Fincaraiz\Sdk\Webhook\WebhookSubscription;

final class WebhooksApi extends BaseApi
{
    /**
     * POST /webhook/{id}/subscribe
     *
     * @param array<string, mixed> $payload
     */
    public function subscribe(string $integratorId, array $payload): mixed
    {
        return $this->send('POST', '/webhook/' . $this->encodePath($integratorId) . '/subscribe', [
            'json' => $payload,
        ]);
    }

    /**
     * Convenience wrapper for the common subscription body expected by FincaRaiz.
     */
    public function subscribeTarget(string $integratorId, string $targetUrl): mixed
    {
        return $this->subscribe($integratorId, WebhookSubscription::target($targetUrl));
    }

    /**
     * Subscribe only when the target URL has changed from the last known value.
     *
     * Because FincaRaiz has no GET endpoint to query the current subscription,
     * the caller must supply $knownUrl (the URL stored after the previous
     * successful call) so the SDK can decide whether an API call is needed.
     *
     * Usage:
     *
     *   $result = $sdk->webhooks()->subscribeTargetIfChanged(
     *       integratorId: $id,
     *       targetUrl: 'https://myapp.com/webhooks/fincaraiz',
     *       knownUrl: Cache::get('fincaraiz_webhook_url'),
     *   );
     *
     *   if ($result->subscribed) {
     *       Cache::set('fincaraiz_webhook_url', $result->url);
     *   }
     *
     * @param string|null $knownUrl Last URL successfully subscribed, or null if unknown.
     */
    public function subscribeTargetIfChanged(
        string $integratorId,
        string $targetUrl,
        ?string $knownUrl = null,
    ): SubscriptionResult {
        $normalizedTarget = WebhookSubscription::target($targetUrl)['target'];

        if ($knownUrl !== null && $knownUrl === $normalizedTarget) {
            return SubscriptionResult::unchanged($normalizedTarget);
        }

        $response = $this->subscribe($integratorId, ['target' => $normalizedTarget]);

        return SubscriptionResult::subscribed($normalizedTarget, $response);
    }

    /**
     * POST /webhook/{id}/unsubscribe
     */
    public function unsubscribe(string $integratorId): mixed
    {
        return $this->send('POST', '/webhook/' . $this->encodePath($integratorId) . '/unsubscribe');
    }

    /**
     * POST /api/fincaraiz/
     *
     * Used for webhook event delivery/verification headers defined by OpenAPI.
     *
     * @param array<string, mixed> $payload
     */
    public function postEvent(string $hubId, string $verifyToken, array $payload): mixed
    {
        return $this->send('POST', '/api/fincaraiz/', [
            'headers' => [
                'HUB.ID' => $hubId,
                'VERIFY-TOKEN' => $verifyToken,
            ],
            'json' => $payload,
        ]);
    }
}

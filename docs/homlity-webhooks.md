# Homlity Webhooks

Header requerido:
- `X-Homlity-Signature: sha256=<hex_hmac>`

Firma:
- `hash_hmac('sha256', $rawBody, $webhookSecret)`

Validación en SDK:
```php
$notification = \Homlity\Sdk\Homlity\Webhook\WebhookNotification::fromRequest($rawBody, getallheaders());
$notification->assertAuthorizedSignature($_ENV['HOMLITY_WEBHOOK_SECRET']);
```

## Curl de referencia
```bash
BODY='{"event":"property.created","property_id":"12345"}'
SECRET='super-secret'
SIGNATURE=$(php -r "echo 'sha256=' . hash_hmac('sha256', '$BODY', '$SECRET');")

curl -X POST 'https://tu-wp.com/wp-json/homlity-sync/v1/webhook' \
  -H "Content-Type: application/json" \
  -H "X-Homlity-Signature: ${SIGNATURE}" \
  -d "$BODY"
```

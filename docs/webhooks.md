# Webhooks

El flujo real de Homlity para tareas asincrónicas es este:

1. Tu integrador publica o actualiza inmuebles.
2. Homlity procesa la tarea y genera un `task_id`.
3. Homlity envía la notificación al endpoint que tengas suscrito.
4. El integrador valida los headers `HUB.ID` y `VERIFY-TOKEN`.
5. El cuerpo del webhook contiene el detalle de la tarea procesada.

## Suscribir la URL del integrador

```php
<?php

use Homlity\Sdk\Config;
use Homlity\Sdk\HomlityClient;

$sdk = new HomlityClient(new Config('TU_API_KEY'));

$sdk->webhooks()->subscribeTarget(
    integratorId: '696d939e-4cc3-43ac-a312-6bf2e7f15868',
    targetUrl: 'https://midominio.com/webhooks/homlity'
);
```

Si prefieres construir el payload manualmente:

```php
<?php

use Homlity\Sdk\Webhook\WebhookSubscription;

$payload = WebhookSubscription::target('https://midominio.com/webhooks/homlity');
```

## Recibir y validar el callback

```php
<?php

use Homlity\Sdk\Webhook\WebhookNotification;

$notification = WebhookNotification::fromGlobals();
$notification->assertAuthorized(
    expectedHubId: $_ENV['FINCARAIZ_WEBHOOK_HUB_ID'],
    expectedVerifyToken: $_ENV['FINCARAIZ_WEBHOOK_VERIFY_TOKEN']
);

if ($notification->isListingStatusEvent()) {
    $updates = $notification->listingStatusUpdates();
}
```

## Qué devuelve `listingStatusUpdates()`

Cada elemento incluye:

- `listing_id`
- `external_code`
- `fr_property_id`
- `processing_status`
- `error`

`processing_status` es el estado del procesamiento de la tarea (`ERROR`, `COMPLETED`, `FORWARDED`).

Importante: según el OpenAPI local de Homlity, el webhook de tarea `LISTING_STATUS` no expone explícitamente el estado final del inmueble (`ACTIVE` o `DELETED`); expone el resultado del procesamiento de la operación.

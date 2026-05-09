# FincaRaiz PHP SDK Desarrollado por codwelt Sas

SDK en PHP para integracion con el API de Integradores de Finca Raiz.
Incluye: 
- Cliente HTTP con cURL y manejo de errores.
- Modulos por recurso (`listings`, `clients`, `categories`, `locations`, `tasks`, `webhooks`).
- Validacion de campos requeridos para publicar/actualizar inmuebles.
- Catalogo de esquemas OpenAPI para consultar parametros en runtime.
- Utilidades para suscribir y procesar webhooks de tareas.

## Instalacion

```bash
composer require fincaraiz/sdk-php
```

## Uso rapido

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Fincaraiz\Sdk\Config;
use Fincaraiz\Sdk\FincaRaizClient;

$config = new Config(
    apiKey: 'TU_API_KEY',
    baseUrl: Config::BASE_URL_PRODUCTION,
    timeoutSeconds: 30
);

$sdk = new FincaRaizClient($config);

$clients = $sdk->clients()->all();
print_r($clients);
```

## Publicar inmueble (POST /listing)

```php
<?php

$payload = [
    'external_code' => 'INT-0001',
    'client_id' => 'df03d199-be5c-4c5c-98f6-849361cb7fae',
    'offer' => 'sell',
    'property_type' => 'house',
    'description' => 'Casa amplia y bien ubicada.',
    'price' => 450000000,
    'address' => [
        'address' => 'Calle 12 # 34-56',
    ],
    'locations' => [
        'location_point' => [
            'latitude' => 4.729795079,
            'longitude' => -74.044724493,
        ],
        'location_main_id' => '1895e0a3-60b8-4a9d-858d-f2c7297b48b2',
        'view_map' => 2,
    ],
    'area' => 120,
    'listing_contact' => [
        'emails' => [
            ['email' => 'ventas@midominio.com', 'is_main' => true, 'sort_order' => 0],
        ],
        'phones' => [
            [
                'phone' => '+573001112233',
                'is_whatsapp_number' => true,
                'is_click_to_call' => true,
                'sort_order' => 0,
            ],
        ],
    ],
    'photos' => [
        ['sort_order' => 1, 'is_main' => true, 'image' => 'https://example.com/foto-1.jpg'],
    ],
];

$result = $sdk->listings()->create($payload);
print_r($result);
```

## Obtener inmuebles

```php
<?php

// Listado (la API define header Cookie obligatorio en este endpoint)
$list = $sdk->listings()->list(
    clientCookie: '78bea79c-1f6b-4e6d-a800-43fb327ed7c5',
    query: [
        'page' => 1,
        'page_size' => 10,
        'ordering' => '-created',
    ]
);

// Detalle
$detail = $sdk->listings()->get('78bea79c-1f6b-4e6d-a800-43fb327ed7c5');
```

## Parametros y endpoints

- Endpoints completos: `docs/api-reference.md`
- Parametros para creacion y consulta de inmuebles: `docs/listing-parameters.md`
- Webhooks y recepcion de tareas: `docs/webhooks.md`
- Snapshot OpenAPI usado por el SDK: `resources/openapi/fincaraiz-integradores-1.0.0.json`

## Recursos disponibles en el SDK

```php
$sdk->listings();
$sdk->clients();
$sdk->categories();
$sdk->locations();
$sdk->tasks();
$sdk->webhooks();
```

## Webhook de estados de inmuebles

Suscribir el endpoint del integrador:

```php
<?php

$sdk->webhooks()->subscribeTarget(
    integratorId: '696d939e-4cc3-43ac-a312-6bf2e7f15868',
    targetUrl: 'https://midominio.com/webhooks/fincaraiz'
);
```

Procesar el callback entrante:

```php
<?php

use Fincaraiz\Sdk\Webhook\WebhookNotification;

$notification = WebhookNotification::fromGlobals();
$notification->assertAuthorized(
    expectedHubId: $_ENV['FINCARAIZ_WEBHOOK_HUB_ID'],
    expectedVerifyToken: $_ENV['FINCARAIZ_WEBHOOK_VERIFY_TOKEN']
);

if ($notification->isListingStatusEvent()) {
    $updates = $notification->listingStatusUpdates();
}
```

### Suscribir solo si cambió la URL

La API de FincaRaiz no expone un GET para consultar la suscripción activa, por lo
que el SDK implementa "desired state" con `subscribeTargetIfChanged()`. El integrador
guarda la URL conocida y la pasa en cada llamada; el SDK evita el POST si no hay
cambio.

```php
<?php

use Fincaraiz\Sdk\Webhook\SubscriptionResult;

// $knownUrl es la URL que guardaste la última vez que suscribiste con éxito.
// Puede venir de tu base de datos, cache, variable de entorno, etc.
$knownUrl = Cache::get('fincaraiz_webhook_url');

$result = $sdk->webhooks()->subscribeTargetIfChanged(
    integratorId: '696d939e-4cc3-43ac-a312-6bf2e7f15868',
    targetUrl: 'https://midominio.com/webhooks/fincaraiz',
    knownUrl: $knownUrl,
);

if ($result->subscribed) {
    // Solo llega aquí si la URL era diferente o desconocida.
    // Persiste la nueva URL para evitar re-suscripciones futuras.
    Cache::set('fincaraiz_webhook_url', $result->url);
}
```

Ejemplos listos:

- `examples/subscribe-webhook.php`
- `examples/receive-webhook.php`

## Nota sobre el API key

El OpenAPI mezcla `apikey` y `X-API-KEY` en distintas secciones. El SDK envia ambos headers automaticamente con el mismo token para maximizar compatibilidad.

## Homlity SDK

Se agregó un namespace paralelo `Fincaraiz\\Sdk\\Homlity\\...` con la misma filosofía de arquitectura:
- `Config`, `HomlityClient`
- `Api/BaseApi`, `Api/PropertiesApi`, `Api/WebhooksApi`, `Api/AnalyticsApi`
- `Http/HttpClientInterface`, `Http/ApiResponse`, `Http/CurlHttpClient`
- `Exception/*`, `Schema/*`, `Webhook/*`, `Data/*`

### Uso básico
```php
use Fincaraiz\Sdk\Homlity\Config;
use Fincaraiz\Sdk\Homlity\HomlityClient;

$client = new HomlityClient(new Config('api-key', 'https://tu-wp.com'));
$client->properties()->push([...]);
$client->properties()->deactivate('12345');
$client->webhooks()->notify('property.created', '12345');
$client->analytics()->report(['range' => 30, 'limit' => 20]);
```

### Documentación
- `docs/homlity-api-reference.md`
- `docs/homlity-webhooks.md`

### Ejemplos
- `examples/homlity-push-property.php`
- `examples/homlity-deactivate-property.php`
- `examples/homlity-webhook.php`
- `examples/homlity-analytics-report.php`

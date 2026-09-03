<p align="center">
  <a href="https://homlity.com/desarrolladores/">
    <img src="docs/assets/homlity-developers.png" width="560" alt="Homlity para desarrolladores">
  </a>
</p>

<h1 align="center">Homlity PHP SDK</h1>

<p align="center">
  Integra inmuebles, clientes, leads, tickets, tareas y WordPress con el ecosistema inmobiliario Homlity.
</p>

## Propósito del SDK

Homlity conecta, automatiza y optimiza procesos del sector inmobiliario. Este
SDK lleva ese objetivo a proyectos PHP: encapsula autenticación, solicitudes
HTTP, validaciones y respuestas para que una integración pueda concentrarse en
su lógica de negocio y no en reconstruir la comunicación con cada API.

Con el paquete puedes:

- publicar, actualizar, consultar y desactivar inmuebles;
- consultar en vivo el perfil público de la inmobiliaria autenticada;
- buscar inmuebles del tenant autenticado con filtros y paginación;
- consultar clientes y agentes, y verificar clientes por documento;
- crear leads y relacionarlos con un inmueble o cliente cuando el backend lo admite;
- crear, listar y consultar tickets;
- consultar canales de ingreso y categorías de PQR;
- consultar tareas asíncronas y procesar webhooks;
- sincronizar propiedades y consultar analítica en un sitio WordPress con Homlity.

> Homlity es una plataforma tecnológica para el sector inmobiliario, no una
> inmobiliaria. Consulta [homlity.com](https://homlity.com/) y el portal de
> [Homlity para desarrolladores](https://homlity.com/desarrolladores/).

## Requisitos e instalación

- PHP 8.1 o superior
- extensiones `curl` y `json`
- Composer

```bash
composer require homlity/sdk-php
```

## Elige el cliente correcto

El paquete cubre tres superficies. No mezcles credenciales ni URL base entre
ellas.

| Flujo | Configuración | Autenticación | Para qué sirve |
| --- | --- | --- | --- |
| API de Integradores | `new Homlity\Sdk\Config(...)` | `apikey` + `X-API-KEY` | Publicar/listar inmuebles, clientes, agentes, categorías, ubicaciones, tareas y webhooks |
| API tenant | `Homlity\Sdk\Config::forTenantApi(...)` | Bearer token | Inmuebles, tickets, verificación de clientes y leads aislados por inmobiliaria |
| Sitio WordPress | `Homlity\Sdk\Homlity\Config` | Bearer + firma HMAC en propiedades | Sincronizar/desactivar propiedades, eventos y analítica del plugin |

## Inicio rápido: API tenant

El token representa al usuario y a su inmobiliaria. Los DTO del SDK no reciben
`id_inmobiliaria`; el backend aplica el alcance del tenant autenticado.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Homlity\Sdk\Config;
use Homlity\Sdk\Filter\PropertyFilters;
use Homlity\Sdk\HomlityClient;
use Homlity\Sdk\Request\CreateLeadRequest;

$token = $_ENV['HOMLITY_ACCESS_TOKEN'];
$config = Config::forTenantApi($token);
$client = new HomlityClient($config);

$profile = $client->company()->profile();
echo $profile->name();

$channels = $client->channels()->list();

foreach ($channels as $channel) {
    echo $channel->id();
    echo $channel->name();
}

$categories = $client->tickets()->categories();

foreach ($categories as $category) {
    echo $category->id();
    echo $category->name();
    echo $category->description() ?? '';
    echo $category->parentId() ?? '';
}

$properties = $client->properties()->search(new PropertyFilters(
    search: 'apartamento Laureles',
    propertyTypeIds: [2],
    salePriceMin: 300_000_000,
    salePriceMax: 700_000_000,
    rooms: 3,
    page: 1,
    perPage: 20,
));

$verification = $client->clients()->verifyDocument('AB-123456', 'CE');

if (!$properties->isEmpty()) {
    $result = $client->leads()->create(new CreateLeadRequest(
        name: 'Ada Lovelace',
        email: 'ada@example.com',
        propertyId: $properties->items()[0]->id(),
        clientId: $verification->client()?->id(),
    ));

    echo $result->lead()->id();
}
```

Las aplicaciones consumidoras, como Homlity Chat, deben guardar los IDs que
devuelven estas APIs. No deben depender de IDs hardcodeados para canales o
categorías de PQR.

La guía de [recursos tenant](docs/tenant-resources.md) contiene todos los
filtros, DTO, relaciones, respuestas y contratos pendientes del backend.

## Inicio rápido: API de Integradores

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Homlity\Sdk\Config;
use Homlity\Sdk\HomlityClient;

$sdk = new HomlityClient(new Config(
    apiKey: $_ENV['HOMLITY_API_KEY'],
    baseUrl: Config::BASE_URL_PRODUCTION,
    timeoutSeconds: 30,
));

$clients = $sdk->clients()->all();
$categories = $sdk->categories()->list();
$locations = $sdk->locations()->search('Medellín');
```

Publicar un inmueble:

```php
$result = $sdk->listings()->create([
    'external_code' => 'INT-0001',
    'client_id' => 'df03d199-be5c-4c5c-98f6-849361cb7fae',
    'offer' => 'sell',
    'property_type' => 'house',
    'description' => 'Casa amplia y bien ubicada.',
    'price' => 450_000_000,
    'area' => 120,
    'address' => ['address' => 'Calle 12 # 34-56'],
    'locations' => [
        'location_point' => ['latitude' => 4.729795079, 'longitude' => -74.044724493],
        'location_main_id' => '1895e0a3-60b8-4a9d-858d-f2c7297b48b2',
        'view_map' => 2,
    ],
    'listing_contact' => [
        'emails' => [['email' => 'ventas@example.com', 'is_main' => true, 'sort_order' => 0]],
        'phones' => [['phone' => '+573001112233', 'sort_order' => 0]],
    ],
]);
```

Consulta [parámetros de inmuebles](docs/listing-parameters.md) antes de enviar
un payload de producción.

## Inicio rápido: WordPress

```php
<?php

use Homlity\Sdk\Homlity\Config;
use Homlity\Sdk\Homlity\HomlityClient;

$wordpress = new HomlityClient(new Config(
    apiKey: $_ENV['HOMLITY_WORDPRESS_TOKEN'],
    baseUrl: 'https://inmobiliaria.example.com',
));

$property = [
    'id' => '12345',
    'code' => 'EXT-12345',
    'status' => 'active',
    'operation' => 'venta',
    'type' => 'apartamento',
    'category' => 'residencial',
    'media' => ['photos' => [], 'videos' => []],
];

$wordpress->properties()->push([$property]);
$wordpress->properties()->deactivate('EXT-12345');
$wordpress->webhooks()->notify('property.updated', 'EXT-12345');
$report = $wordpress->analytics()->report(['range' => 30, 'limit' => 20]);
```

`push()` y `deactivate()` firman el cuerpo exacto con HMAC SHA-256 de forma
predeterminada. Consulta la [referencia WordPress](docs/homlity-api-reference.md).

## Documentación

- [Centro de documentación](docs/index.md): propósito, arquitectura y rutas de aprendizaje.
- [Referencia completa de clases y métodos](docs/public-api.md): qué hace cada función, parámetros, retornos y ejemplos.
- [Mapa de endpoints](docs/api-reference.md): método SDK ↔ endpoint HTTP.
- [Recursos tenant](docs/tenant-resources.md): inmuebles, tickets, clientes y leads.
- [Parámetros de inmuebles de Integradores](docs/listing-parameters.md).
- [Tareas y webhooks de Integradores](docs/webhooks.md).
- [API del plugin WordPress](docs/homlity-api-reference.md).
- [Webhooks firmados del plugin](docs/homlity-webhooks.md).
- [OpenAPI local](resources/openapi/homlity-integradores-1.0.0.json).

## Ejemplos ejecutables

Los scripts leen credenciales desde variables de entorno; no guardes secretos
en el repositorio.

| Ejemplo | Flujo |
| --- | --- |
| [`tenant-workflow.php`](examples/tenant-workflow.php) | Buscar inmueble → verificar cliente → crear lead → crear ticket |
| [`publish-listing.php`](examples/publish-listing.php) | Publicar inmueble en Integradores |
| [`list-listings.php`](examples/list-listings.php) | Listar inmuebles en Integradores |
| [`subscribe-webhook.php`](examples/subscribe-webhook.php) | Suscribir un callback |
| [`receive-webhook.php`](examples/receive-webhook.php) | Validar y leer una notificación |
| [`homlity-push-property.php`](examples/homlity-push-property.php) | Sincronizar una propiedad con WordPress |
| [`homlity-deactivate-property.php`](examples/homlity-deactivate-property.php) | Desactivar una propiedad en WordPress |
| [`homlity-analytics-report.php`](examples/homlity-analytics-report.php) | Consultar analítica del plugin |

## Errores y seguridad

La API principal mapea respuestas `401`, `403`, `404`, `409`, `422`, `429` y
`5xx` a excepciones específicas. El transporte conserva el código HTTP, JSON y
tracking ID, pero evita incluir el cuerpo —que puede contener datos personales—
en el mensaje de error.

```php
use Homlity\Sdk\Exception\ApiException;
use Homlity\Sdk\Exception\AuthorizationException;

try {
    $property = $sdk->properties()->get(123);
} catch (AuthorizationException $error) {
    // El token no tiene permiso para este recurso.
} catch (ApiException $error) {
    error_log(sprintf(
        'Homlity HTTP %s; tracking=%s',
        $error->statusCode() ?? 'sin-respuesta',
        $error->trackingId() ?? 'n/a',
    ));
}
```

## Desarrollo

```bash
composer install
composer test
```

Licencia MIT. Desarrollado por Codwelt S.A.S. para el ecosistema Homlity.

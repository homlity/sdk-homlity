<p align="center">
  <img src="assets/homlity-developers.png" width="460" alt="Homlity para desarrolladores">
</p>

# Referencia de clases y métodos públicos

Esta guía documenta la superficie pública del Homlity PHP SDK: para qué sirve
cada método, qué recibe, qué retorna y cómo se usa. Las firmas corresponden al
código actual del repositorio.

> Todos los ejemplos asumen `require __DIR__ . '/vendor/autoload.php';` y usan
> variables de entorno para las credenciales.

[Inicio](index.md) · [Mapa de endpoints](api-reference.md) · [Recursos tenant](tenant-resources.md)

## Contenido

1. [Configuración y cliente principal](#1-configuración-y-cliente-principal)
2. [API de Integradores](#2-api-de-integradores)
3. [API tenant](#3-api-tenant)
4. [Modelos y resultados](#4-modelos-y-resultados)
5. [Recepción de webhooks](#5-recepción-de-webhooks-de-integradores)
6. [Esquemas y validación](#6-esquemas-y-validación-openapi)
7. [Errores y transporte](#7-errores-respuesta-http-y-utilidades)
8. [Cliente WordPress](#8-cliente-wordpress)
9. [Ejemplo integral](#9-ejemplo-integral-tenant)
10. [Capacidades pendientes](#10-capacidades-pendientes-del-backend)

## 1. Configuración y cliente principal

### `Homlity\Sdk\Config`

Controla la URL, el tiempo máximo y el esquema de autenticación de la API
principal.

| Método | Para qué sirve | Resultado / ejemplo |
| --- | --- | --- |
| `__construct(string $apiKey, string $baseUrl = BASE_URL_PRODUCTION, int $timeoutSeconds = 30, string $authMode = AUTH_API_KEY)` | Configura un cliente de Integradores o una URL compatible. Valida credencial, timeout y modo. | `new Config($_ENV['HOMLITY_API_KEY'])` |
| `forTenantApi(string $accessToken, string $baseUrl = BASE_URL_TENANT_PRODUCTION, int $timeoutSeconds = 30): self` | Crea la configuración recomendada para recursos tenant y activa Bearer. | `Config::forTenantApi($_ENV['HOMLITY_ACCESS_TOKEN'])` |
| `apiKey(): string` | Obtiene la credencial configurada. Evita imprimirla o registrarla. | `$config->apiKey()` |
| `baseUrl(): string` | Obtiene la URL base sin `/` final. | `$config->baseUrl()` |
| `timeoutSeconds(): int` | Obtiene el timeout en segundos. | `$config->timeoutSeconds()` |
| `authMode(): string` | Indica `api_key` o `bearer`. | `$config->authMode()` |
| `authenticationHeaders(): array` | Construye los headers que usará el transporte. | `$config->authenticationHeaders()` |

Constantes disponibles:

- `BASE_URL_PRODUCTION`: API de Integradores productiva.
- `BASE_URL_QA`: ambiente QA de Integradores.
- `BASE_URL_MOCK`: mock de SwaggerHub.
- `BASE_URL_TENANT_PRODUCTION`: API operativa en `web.homlity.com/api`.
- `AUTH_API_KEY` y `AUTH_BEARER`: modos de autenticación.

```php
use Homlity\Sdk\Config;

$integratorsConfig = new Config(
    apiKey: $_ENV['HOMLITY_API_KEY'],
    baseUrl: Config::BASE_URL_PRODUCTION,
    timeoutSeconds: 30,
);

$tenantConfig = Config::forTenantApi(
    accessToken: $_ENV['HOMLITY_ACCESS_TOKEN'],
    timeoutSeconds: 20,
);
```

### `Homlity\Sdk\HomlityClient`

Es la fachada del SDK. Crea cada recurso bajo demanda y comparte transporte,
configuración y manejo de errores.

| Método | Para qué sirve | Retorna |
| --- | --- | --- |
| `__construct(Config $config, ?HttpClientInterface $httpClient = null, ?SchemaCatalog $schemaCatalog = null)` | Crea el SDK. Los argumentos opcionales permiten inyectar dobles de prueba. | `HomlityClient` |
| `config(): Config` | Recupera la configuración activa. | `Config` |
| `schemaCatalog(): SchemaCatalog` | Accede al OpenAPI local de Integradores. | `SchemaCatalog` |
| `listings(): ListingsApi` | Publicación y consulta histórica de inmuebles. | `ListingsApi` |
| `clients(): ClientsApi` | Clientes/agentes de Integradores y verificación tenant. | `ClientsApi` |
| `categories(): CategoriesApi` | Catálogo de características. | `CategoriesApi` |
| `locations(): LocationsApi` | Búsqueda de ubicaciones. | `LocationsApi` |
| `properties(): PropertiesApi` | Consulta tipada de inmuebles tenant. | `PropertiesApi` |
| `tickets(): TicketsApi` | Creación y consulta de tickets tenant. | `TicketsApi` |
| `leads(): LeadsApi` | Creación de leads tenant. | `LeadsApi` |
| `tasks(): TasksApi` | Estado de tareas asíncronas de Integradores. | `TasksApi` |
| `webhooks(): WebhooksApi` | Suscripciones y entrega de eventos de Integradores. | `WebhooksApi` |

```php
use Homlity\Sdk\HomlityClient;

$sdk = new HomlityClient($tenantConfig);

echo $sdk->config()->baseUrl();
$propertiesApi = $sdk->properties();
$ticketsApi = $sdk->tickets();
```

No uses, por ejemplo, `properties()` con una configuración de Integradores:
ese recurso espera la URL y el Bearer token de la API tenant.

## 2. API de Integradores

Configura este bloque con `new Config(apiKey: ...)`.

### Inmuebles: `ListingsApi`

| Método | Para qué sirve | Retorna |
| --- | --- | --- |
| `list(string $clientCookie, array $query = []): mixed` | Lista inmuebles del cliente. El endpoint exige el header `Cookie`; admite `search`, `ordering`, `page` y `page_size`. | JSON decodificado de la API |
| `get(string $listingId): mixed` | Obtiene el detalle crudo por UUID de publicación. | JSON decodificado |
| `getSnapshot(string $listingId): ListingSnapshot` | Obtiene el mismo detalle como objeto tipado. | `ListingSnapshot` |
| `findByExternalCode(string $clientCookie, string $externalCode, array $extraQuery = []): ?ListingSnapshot` | Busca un código externo en la página solicitada. | Snapshot o `null` |
| `create(array $listings): mixed` | Publica un objeto o lote; valida campos obligatorios del OpenAPI. | Respuesta/tarea cruda |
| `update(array $listings): mixed` | Actualiza un objeto o lote; cada item requiere `listing_id`. | Respuesta/tarea cruda |
| `updateStatus(array $statuses): mixed` | Cambia estado con `ACTIVE` o `DELETED`. | Respuesta/tarea cruda |
| `validate(array $payload): mixed` | Verifica publicaciones por IDs/códigos; exige `client_id`. | Respuesta cruda |

```php
// Listar y obtener una vista tipada.
$page = $sdk->listings()->list(
    clientCookie: $_ENV['HOMLITY_CLIENT_COOKIE'],
    query: ['page' => 1, 'page_size' => 20, 'ordering' => '-created'],
);

$listing = $sdk->listings()->getSnapshot('uuid-del-listing');
if ($listing->isPublished()) {
    echo $listing->externalCode();
}

// Buscar por código externo en una página concreta.
$found = $sdk->listings()->findByExternalCode(
    $_ENV['HOMLITY_CLIENT_COOKIE'],
    'INT-0001',
    ['page' => 1],
);

// Actualizar y desactivar.
$sdk->listings()->update([
    'listing_id' => 'uuid-del-listing',
    // Incluye los demás campos requeridos de ListingPATCH.
]);

$sdk->listings()->updateStatus([
    'listing_id' => 'uuid-del-listing',
    'client_id' => 'uuid-del-cliente',
    'status' => 'DELETED',
]);

$check = $sdk->listings()->validate([
    'client_id' => 'uuid-del-cliente',
    'integrator_code' => ['INT-0001'],
]);
```

El payload completo de `create()` y `update()` está en
[parámetros de inmuebles](listing-parameters.md). `findByExternalCode()` solo
revisa la página indicada; cambia `page` cuando el conjunto sea grande.

### Clientes: `ClientsApi`

Los primeros cuatro métodos pertenecen a Integradores. `verifyDocument()`
pertenece a la API tenant y se explica también en la sección 3.

| Método | Para qué sirve | Retorna |
| --- | --- | --- |
| `all(): mixed` | Lista los clientes visibles para la credencial de Integradores. | JSON decodificado |
| `get(string $clientId): mixed` | Consulta un cliente por UUID. | JSON decodificado |
| `agents(string $clientId): mixed` | Lista agentes/sucursales asociados al cliente. | Lista cruda |
| `resolveSingleAgentId(string $clientId): int` | Devuelve el ID cuando existe exactamente un agente. Lanza `RuntimeException` si hay cero, varios o falta el campo `id`. | `int` |
| `verifyDocument(string $document, $documentType = null): ClientVerificationResult` | Busca coincidencias exactas dentro de la API tenant. `$documentType` admite `int`, `string` o `null`. | Resultado tipado |

```php
$clients = $sdk->clients()->all();
$client = $sdk->clients()->get('uuid-del-cliente');
$agents = $sdk->clients()->agents('uuid-del-cliente');

try {
    $agentId = $sdk->clients()->resolveSingleAgentId('uuid-del-cliente');
} catch (RuntimeException $error) {
    // Selecciona el agente explícitamente cuando exista más de uno.
}
```

### Categorías: `CategoriesApi`

`list(array $query = []): mixed` consulta `GET /category`. Sirve para obtener
los IDs de características aceptados en el campo `categories` de un inmueble.

```php
$allCategories = $sdk->categories()->list();
$page = $sdk->categories()->list(['page' => 1]);
```

### Ubicaciones: `LocationsApi`

`search(string $name): mixed` consulta `GET /location/{name}` y codifica el
texto de forma segura para la URL.

```php
$locations = $sdk->locations()->search('El Poblado');
```

### Tareas: `TasksApi`

| Método | Para qué sirve | Retorna |
| --- | --- | --- |
| `get(string $taskId): mixed` | Obtiene la respuesta cruda de una tarea. | JSON decodificado |
| `getSnapshot(string $taskId): TaskSnapshot` | Interpreta ID, estado y contenido. | `TaskSnapshot` |
| `waitUntilSettled(string $taskId, array $options = []): TaskSnapshot` | Hace polling hasta `COMPLETED`, `FORWARDED` o `ERROR`. | Último snapshot |

Opciones de `waitUntilSettled()`:

| Opción | Predeterminado | Función |
| --- | ---: | --- |
| `maxAttempts` | `30` | Número máximo de consultas |
| `intervalSeconds` | `3` | Espera entre consultas |
| `throwOnTimeout` | `true` | Lanza `RuntimeException` al agotar intentos |
| `sleepFn` | `sleep(...)` | Callback de espera, útil para pruebas |

```php
$rawTask = $sdk->tasks()->get('task-uuid');
$snapshot = $sdk->tasks()->getSnapshot('task-uuid');

$settled = $sdk->tasks()->waitUntilSettled('task-uuid', [
    'maxAttempts' => 20,
    'intervalSeconds' => 2,
    'throwOnTimeout' => true,
]);

if ($settled->isSuccessful()) {
    foreach ($settled->contentItems() as $item) {
        // Procesa el resultado de cada inmueble.
    }
}
```

### Suscripciones y eventos: `WebhooksApi`

| Método | Para qué sirve | Retorna |
| --- | --- | --- |
| `subscribe(string $integratorId, array $payload): mixed` | Suscribe un payload manual en `/webhook/{id}/subscribe`. | Respuesta cruda |
| `subscribeTarget(string $integratorId, string $targetUrl): mixed` | Suscribe una URL absoluta con el formato oficial. | Respuesta cruda |
| `subscribeTargetIfChanged(string $integratorId, string $targetUrl, ?string $knownUrl = null): SubscriptionResult` | Evita repetir el POST cuando la URL conocida no cambió. | Resultado tipado |
| `unsubscribe(string $integratorId): mixed` | Elimina la suscripción del integrador. | Respuesta cruda |
| `postEvent(string $hubId, string $verifyToken, array $payload): mixed` | Entrega/verifica un evento usando `HUB.ID` y `VERIFY-TOKEN`. | Respuesta cruda |

```php
$sdk->webhooks()->subscribe(
    'integrator-uuid',
    ['target' => 'https://app.example.com/webhooks/homlity'],
);

$sdk->webhooks()->subscribeTarget(
    'integrator-uuid',
    'https://app.example.com/webhooks/homlity',
);

$subscription = $sdk->webhooks()->subscribeTargetIfChanged(
    integratorId: 'integrator-uuid',
    targetUrl: 'https://app.example.com/webhooks/homlity',
    knownUrl: $urlGuardada,
);

if ($subscription->subscribed) {
    $urlGuardada = $subscription->url;
}

$sdk->webhooks()->postEvent('hub-id', 'verify-token', [
    'event' => 'LISTING_STATUS',
]);

$sdk->webhooks()->unsubscribe('integrator-uuid');
```

`knownUrl` es estado del consumidor: Homlity no expone un GET para consultar la
suscripción activa.

## 3. API tenant

Configura este bloque con `Config::forTenantApi(...)`. Todos los recursos se
resuelven dentro de la inmobiliaria asociada al token.

### Inmuebles: `PropertiesApi`

| Método | Para qué sirve | Retorna |
| --- | --- | --- |
| `list(?PropertyFilters $filters = null): PaginatedResult` | Lista inmuebles. Sin argumento usa página 1 y 20 items. | `PaginatedResult<PropertySnapshot>` |
| `search(PropertyFilters $filters): PaginatedResult` | Alias explícito de `list()` cuando se aplican filtros. | `PaginatedResult<PropertySnapshot>` |
| `get($property): PropertySnapshot` | Obtiene un inmueble de integración por ID numérico o código interno; `$property` admite `int` o `string`. | `PropertySnapshot` |
| `getByCode(string $code): PropertySnapshot` | Alias semántico de `get($code)`. | `PropertySnapshot` |

```php
use Homlity\Sdk\Filter\PropertyFilters;

$firstPage = $tenant->properties()->list();
$filtered = $tenant->properties()->search(new PropertyFilters(
    search: 'Laureles',
    propertyTypeIds: [2, 4],
    salePriceMin: 300_000_000,
    salePriceMax: 700_000_000,
    rooms: 3,
    page: 1,
    perPage: 20,
));

$byId = $tenant->properties()->get(123);
$byCode = $tenant->properties()->getByCode('INM-2026-001');
```

#### `PropertyFilters`

El constructor acepta parámetros nombrados y `toQuery(): array` muestra la
serialización final. Los valores `null`, strings vacíos y listas vacías no se
envían.

| Parámetro PHP | Query | Uso |
| --- | --- | --- |
| `search` | `q` | Texto libre |
| `statuses` | `status[]` | IDs de estado |
| `propertyTypeIds` | `tipo_inmueble[]` | IDs de tipo de inmueble |
| `businessTypeId` | `tipo_gestion` | Tipo de negocio/gestión |
| `cityId` | `ciudad` | Ciudad |
| `neighborhoodId` | `barrio` | Barrio |
| `adviserId` | `asesor` | Asesor |
| `stratum` | `estrato` | Estrato |
| `rooms` | `n_cuartos` | Habitaciones |
| `bathrooms` | `n_banos` | Baños |
| `parkingSpaces` | `n_parqueaderos` | Parqueaderos |
| `rentPriceMin` / `rentPriceMax` | `arriendo_desde` / `arriendo_hasta` | Rango de arriendo |
| `salePriceMin` / `salePriceMax` | `venta_desde` / `venta_hasta` | Rango de venta |
| `builtAreaMin` / `builtAreaMax` | `area_desde` / `area_hasta` | Rango de área construida |
| `tags` | `tags[...]` | Pares de etiqueta con valores escalares |
| `origin` | `origin` | Origen del inmueble |
| `page` | `page` | Página, mínimo 1 |
| `perPage` | `per_page` | Tamaño de página, entre 1 y 100 |

```php
$filters = new PropertyFilters(
    statuses: [1],
    tags: ['balcon' => true],
    rentPriceMin: 1_500_000,
    rentPriceMax: 3_000_000,
);

$query = $filters->toQuery();
```

El constructor valida IDs positivos, números no negativos y que cada mínimo no
supere su máximo.

### Tickets: `TicketsApi`

| Método | Para qué sirve | Retorna |
| --- | --- | --- |
| `create(CreateTicketRequest $request): TicketSnapshot` | Crea un ticket con asunto, cuerpo y campos soportados. | `TicketSnapshot` |
| `list(?TicketFilters $filters = null): PaginatedResult` | Lista tickets con filtros. | `PaginatedResult<TicketSnapshot>` |
| `get(int $ticketId): TicketSnapshot` | Obtiene un ticket por ID positivo. | `TicketSnapshot` |

```php
use Homlity\Sdk\Filter\TicketFilters;
use Homlity\Sdk\Request\CreateTicketRequest;
use Homlity\Sdk\Request\TicketLeadReference;

$ticket = $tenant->tickets()->create(new CreateTicketRequest(
    subject: 'Solicitud de visita',
    description: 'Coordinar visita al inmueble INM-2026-001.',
    categoryId: 3,
    recipients: ['Asesor <asesor@example.com>'],
    metadata: ['source' => 'sdk', 'property_id' => 123],
    leads: [new TicketLeadReference(
        name: 'Ada Lovelace',
        email: 'ada@example.com',
    )],
));

$tickets = $tenant->tickets()->list(new TicketFilters(
    search: 'visita',
    role: 'owner',
    finalized: false,
    categoryId: 3,
    propertyId: 123,
    deadlineFrom: '2026-09-01',
    deadlineTo: '2026-09-30',
));

$detail = $tenant->tickets()->get($ticket->id());
```

#### `CreateTicketRequest`

`__construct(string $subject, string $description, ?int $categoryId = null,
array $recipients = [], array $metadata = [], array $leads = [])` crea y valida
el DTO. `toPayload(): array` devuelve el JSON listo para la API.

| Campo | Función |
| --- | --- |
| `subject` | Asunto obligatorio y no vacío |
| `description` | Cuerpo obligatorio y no vacío |
| `categoryId` | Categoría positiva opcional |
| `recipients` | Lista opcional de destinatarios no vacíos |
| `metadata` | Parámetros adicionales admitidos por el backend |
| `leads` | Lista de `TicketLeadReference`; no representa IDs de leads existentes |

#### `TicketLeadReference`

`__construct(string $name, ?string $phone = null, ?string $email = null)` exige
nombre y al menos teléfono o email válido. `toArray(): array` devuelve `name`,
`tel` y `email` normalizados.

#### `TicketFilters`

`toQuery(): array` devuelve solo filtros con valor.

| Parámetro | Query / validación |
| --- | --- |
| `search` | `q` |
| `role` | `role`; solo `owner` u `observer` |
| `finalized` | `finalizado`; serializa `true/false` como `1/0` |
| `categoryId` | `tipo`; ID positivo |
| `priorityId` | `prioridad`; ID positivo |
| `propertyId` | `inmueble`; ID positivo |
| `deadlineFrom` / `deadlineTo` | Fechas `YYYY-MM-DD` en orden válido |
| `page` / `perPage` | Página mínima 1; tamaño entre 1 y 100 |

```php
$filters = new TicketFilters(finalized: true, role: 'observer', perPage: 50);
$query = $filters->toQuery();
```

### Verificación de clientes

`ClientsApi::verifyDocument(string $document, int|string|null $documentType =
null): ClientVerificationResult` normaliza el documento, usa la búsqueda
server-side paginada y conserva coincidencias exactas.

```php
use Homlity\Sdk\Data\ClientVerificationStatus;

$verification = $tenant->clients()->verifyDocument('AB-123456', 'CE');

switch ($verification->status()) {
    case ClientVerificationStatus::CLIENT:
        $client = $verification->client();
        echo $client?->maskedDocument();
        break;
    case ClientVerificationStatus::MULTIPLE_MATCHES:
        $matches = $verification->matches();
        break;
    case ClientVerificationStatus::NOT_CLIENT:
    case ClientVerificationStatus::INVALID_DOCUMENT:
        break;
}
```

`$documentType` puede ser ID numérico, abreviatura o nombre. La respuesta no
expone correo, teléfono ni el documento completo.

### Leads: `LeadsApi`

`create(CreateLeadRequest $request, ?string $idempotencyKey = null):
LeadCreationResult` crea el lead. Si hay `propertyId`, usa la ruta protegida del
inmueble. Si hay `clientId`, enlaza el cliente en una segunda solicitud.

```php
use Homlity\Sdk\Request\CreateLeadRequest;
use Homlity\Sdk\Request\LeadRequirements;

$result = $tenant->leads()->create(new CreateLeadRequest(
    name: 'Ada Lovelace',
    phone: '+57 300 000 0000',
    email: 'ada@example.com',
    adviserId: 8,
    statusId: 1,
    priorityId: 2,
    sourceId: 3,
    description: 'Interés desde campaña web.',
    contactTypeId: 4,
    stageId: 5,
    requirements: new LeadRequirements(
        budgetMin: 300_000_000,
        budgetMax: 700_000_000,
        rooms: '3',
        bathrooms: '2',
        businessType: 'venta',
        propertyTypeIds: [2],
        cityId: 10,
        neighborhoodId: 25,
    ),
    propertyId: 123,
    clientId: 456,
));

echo $result->lead()->id();
```

El backend actual no persiste `Idempotency-Key`. Si el argumento no es `null`,
el método lanza `UnsupportedFeatureException` para no presentar un reintento
como seguro.

#### `CreateLeadRequest`

El constructor acepta estos parámetros; `toPayload(): array` serializa solo los
campos soportados por `POST /v1/leads`.

| Parámetro | Campo API / función |
| --- | --- |
| `name` | `nombre`; obligatorio, máximo 255 caracteres |
| `phone` | `celular`; se requiere teléfono o email |
| `email` | `correo`; se normaliza a minúsculas y valida |
| `adviserId` | `id_asesor` |
| `statusId` | `id_estado` |
| `priorityId` | `urgencia` |
| `sourceId` | `origen` |
| `description` | `detalle`; máximo 10.000 caracteres |
| `contactTypeId` | `tipo_contacto` |
| `stageId` | `stage_id` |
| `requirements` | `requerimiento`, desde `LeadRequirements` |
| `propertyId` | Selecciona la ruta del inmueble; no se incluye en el cuerpo base |
| `clientId` | Activa la relación posterior `attach-client` |

Todos los IDs opcionales deben ser positivos.

#### `LeadRequirements`

Representa preferencias del inmueble. `toArray(): array` genera el bloque
`requerimiento`.

| Parámetros | Función |
| --- | --- |
| `budgetMin`, `budgetMax` | Rango de presupuesto no negativo |
| `rooms`, `bathrooms`, `parkingSpaces` | Preferencias textuales admitidas por el contrato |
| `areaMin`, `areaMax` | Rango de área no negativo |
| `stratum` | Estrato |
| `ageMin`, `ageMax` | Antigüedad entre 0 y 500 |
| `businessType` | Solo `venta` o `arriendo` |
| `propertyType` | Nombre de tipo de inmueble |
| `propertyTypeIds` | Hasta 30 IDs positivos |
| `cityId`, `neighborhoodId` | Ubicación; barrio exige ciudad |

```php
$requirements = new LeadRequirements(
    areaMin: 60,
    areaMax: 120,
    stratum: '4',
    businessType: 'arriendo',
    cityId: 10,
);

$payload = $requirements->toArray();
```

## 4. Modelos y resultados

### Resultados paginados

#### `PaginatedResult<T>`

| Método | Para qué sirve | Ejemplo |
| --- | --- | --- |
| `fromApiResponse(mixed $response, callable $mapper): self` | Fábrica para respuestas `{data, meta}`, paginadores anidados o `results`. | Usada internamente por recursos |
| `items(): array` | Devuelve la lista tipada de la página. | `$page->items()` |
| `metadata(): PaginationMetadata` | Devuelve metadatos normalizados. | `$page->metadata()` |
| `raw(): array` | Conserva la respuesta completa. | `$page->raw()` |
| `isEmpty(): bool` | Indica si la página no contiene items. | `$page->isEmpty()` |

#### `PaginationMetadata`

| Método | Para qué sirve | Ejemplo |
| --- | --- | --- |
| `fromArray(array $data, int $itemCount = 0): self` | Normaliza formatos de paginación. | Uso interno |
| `currentPage(): int` | Página actual. | `$meta->currentPage()` |
| `lastPage(): int` | Última página conocida. | `$meta->lastPage()` |
| `perPage(): int` | Items solicitados por página. | `$meta->perPage()` |
| `total(): int` | Total de resultados. | `$meta->total()` |
| `hasNextPage(): bool` | Indica si existe una página posterior. | `$meta->hasNextPage()` |

```php
$pageNumber = 1;

do {
    $page = $tenant->properties()->list(new PropertyFilters(page: $pageNumber));

    foreach ($page->items() as $property) {
        echo $property->id() . PHP_EOL;
    }

    $meta = $page->metadata();
    $pageNumber++;
} while ($meta->hasNextPage());
```

### `PropertySnapshot`

| Método | Para qué sirve |
| --- | --- |
| `fromArray(array $data): self` | Crea el modelo y exige un ID positivo. |
| `id(): int` | ID tenant del inmueble. |
| `code(): ?string` | Código interno, si fue devuelto. |
| `raw(): array` | Todos los campos autorizados por la API. |

```php
$property = $tenant->properties()->get(123);
printf('%d %s', $property->id(), $property->code() ?? 'sin-código');
$allFields = $property->raw();
```

### `TicketSnapshot`

| Método | Para qué sirve |
| --- | --- |
| `fromArray(array $data): self` | Crea el modelo desde la respuesta. |
| `id(): int` | ID del ticket. |
| `subject(): ?string` | Asunto/título, si existe. |
| `isFinalized(): bool` | Comprueba `fecha_finalizacion`. |
| `raw(): array` | Respuesta completa autorizada. |

```php
$ticket = $tenant->tickets()->get(25);
if (!$ticket->isFinalized()) {
    echo $ticket->subject();
}
```

### `ClientVerificationResult` y `ClientMatch`

| Método | Para qué sirve |
| --- | --- |
| `ClientVerificationResult::invalid(): self` | Construye el estado `INVALID_DOCUMENT`. |
| `ClientVerificationResult::fromMatches(array $matches): self` | Decide `NOT_CLIENT`, `CLIENT` o `MULTIPLE_MATCHES`. |
| `status(): ClientVerificationStatus` | Estado tipado. |
| `isClient(): bool` | `true` solo con una coincidencia. |
| `matches(): array` | Todas las coincidencias exactas autorizadas. |
| `client(): ?ClientMatch` | Devuelve el cliente solo cuando hay exactamente uno. |
| `ClientMatch::fromArray(array $data): self` | Mapea y enmascara el documento. |
| `ClientMatch::id(): int` | ID tenant del cliente. |
| `ClientMatch::name(): string` | Nombre autorizado. |
| `ClientMatch::maskedDocument(): string` | Documento protegido; conserva como máximo los últimos cuatro caracteres. |
| `ClientMatch::documentType(): ?array` | Catálogo del tipo de documento. |
| `ClientMatch::status(): ?array` | Estado devuelto por la API. |
| `ClientMatch::roles(): array` | Roles devueltos por la API. |

Estados de `ClientVerificationStatus`: `CLIENT`, `NOT_CLIENT`,
`INVALID_DOCUMENT` y `MULTIPLE_MATCHES`.

### `LeadCreationResult` y `LeadSnapshot`

| Método | Para qué sirve |
| --- | --- |
| `LeadCreationResult::lead(): LeadSnapshot` | Lead resultante. |
| `LeadCreationResult::status(): LeadCreationStatus` | Estado reportado por la creación. |
| `LeadCreationResult::raw(): array` | Respuesta de creación y, si aplica, relación con cliente. |
| `LeadSnapshot::fromArray(array $data): self` | Mapea un lead y exige ID positivo. |
| `LeadSnapshot::id(): int` | ID del lead. |
| `LeadSnapshot::name(): string` | Nombre del lead. |
| `LeadSnapshot::clientId(): ?int` | Cliente convertido/relacionado cuando aparece en la respuesta. |
| `LeadSnapshot::relatedProperty(): ?array` | Resumen del inmueble relacionado. |
| `LeadSnapshot::raw(): array` | Datos completos autorizados. |

`LeadCreationStatus` define `CREATED`, `REUSED`, `DUPLICATE` y `UNKNOWN`. El
contrato actual devuelve `CREATED`; los demás valores quedan preparados para
un futuro contrato de idempotencia/deduplicación del backend.

### `ListingSnapshot` y `ListingStatus`

| Método | Para qué sirve |
| --- | --- |
| `ListingSnapshot::fromArray(array $data): self` | Crea un snapshot histórico desde una respuesta de Integradores. |
| `id(): string` | UUID de publicación. |
| `externalCode(): ?string` | Código del integrador. |
| `status(): ?ListingStatus` | Estado tipado, si viene en la respuesta. |
| `raw(): array` | Respuesta completa. |
| `isActive(): bool` | Está en estado `ACTIVE`. |
| `isDeleted(): bool` | Está en estado `DELETED`. |
| `isPublished(): bool` | Está visible; hoy equivale a `ACTIVE`. |
| `isPending(): bool` | Está `INCOMPLETE` o `PUBLISHING`. |
| `statusCode(): ?int` | Código numérico del estado. |

`ListingStatus` contiene `INCOMPLETE=0`, `DISABLED=1`, `NO_QUOTA=2`,
`ACTIVE=4`, `EXPIRED=5`, `DELETED=7`, `SYSTEM_ERROR=9`, `PUBLISHING=10` y
`REJECTED=11`. Además de `isActive()`, `isDeleted()`, `isPublished()` e
`isPending()`, ofrece `isTerminalFailure()` para eliminado, error o rechazo.

```php
$listing = $sdk->listings()->getSnapshot('listing-uuid');
$status = $listing->status();

if ($status?->isTerminalFailure()) {
    // Revisar el resultado de la tarea o la respuesta cruda.
}
```

### `TaskSnapshot` y `TaskStatus`

| Método | Para qué sirve |
| --- | --- |
| `TaskSnapshot::fromApiResponse(mixed $data): self` | Admite objeto directo o sobre `{task: ...}`. |
| `id(): string` | ID de tarea. |
| `status(): TaskStatus` | Estado tipado. |
| `raw(): array` | Objeto `task` crudo. |
| `isPending(): bool` | `READY` o `RUNNING`. |
| `isSettled(): bool` | Estado terminal. |
| `isSuccessful(): bool` | `COMPLETED` o `FORWARDED`. |
| `isFailed(): bool` | `ERROR`. |
| `contentItems(): array` | Items procesados de `content`. |

`TaskStatus` contiene `READY`, `RUNNING`, `COMPLETED`, `FORWARDED` y `ERROR`, y
expone los mismos helpers de estado. `FORWARDED` se considera exitoso: indica
que una solicitud idéntica ya fue entregada ese día.

## 5. Recepción de webhooks de Integradores

### `WebhookSubscription`

`target(string $targetUrl): array` valida una URL absoluta, elimina espacios y
devuelve `['target' => $url]`.

```php
use Homlity\Sdk\Webhook\WebhookSubscription;

$payload = WebhookSubscription::target(
    'https://app.example.com/webhooks/homlity'
);
```

### `SubscriptionResult`

| Método/campo | Para qué sirve |
| --- | --- |
| `unchanged(string $url): self` | Resultado sin llamada HTTP. |
| `subscribed(string $url, mixed $response): self` | Resultado de una nueva suscripción. |
| `$subscribed` | Indica si se hizo el POST. |
| `$url` | URL normalizada que debes persistir. |
| `$response` | Respuesta cruda o `null` cuando no cambió. |

### `WebhookNotification`

| Método | Para qué sirve |
| --- | --- |
| `fromRequest(string $rawBody, array $headers = []): self` | Decodifica una petición ya capturada. |
| `fromGlobals(?string $rawBody = null): self` | Lee cuerpo y headers del entorno PHP. |
| `isAuthorized(string $expectedHubId, string $expectedVerifyToken): bool` | Compara credenciales con `hash_equals`. |
| `assertAuthorized(string $expectedHubId, string $expectedVerifyToken): void` | Lanza `WebhookException` si no coinciden. |
| `header(string $name): ?string` | Lee un header sin distinguir mayúsculas. |
| `headers(): array` | Headers normalizados. |
| `payload(): array` | Cuerpo JSON completo. |
| `task(): array` | Objeto `task` o lista vacía. |
| `event(): ?string` | Evento de la tarea. |
| `taskStatus(): ?string` | Estado de la tarea. |
| `isListingStatusEvent(): bool` | Comprueba `LISTING_STATUS`. |
| `contentItems(): array` | Items válidos de `task.content`. |
| `listingResults(): array` | Normaliza resultados y combina errores por ID/código. |
| `listingStatusUpdates(): array` | Devuelve resultados solo para `LISTING_STATUS`. |

```php
use Homlity\Sdk\Webhook\WebhookNotification;

$notification = WebhookNotification::fromGlobals();
$notification->assertAuthorized(
    $_ENV['HOMLITY_WEBHOOK_HUB_ID'],
    $_ENV['HOMLITY_WEBHOOK_VERIFY_TOKEN'],
);

if ($notification->isListingStatusEvent()) {
    foreach ($notification->listingStatusUpdates() as $update) {
        echo $update['external_code'] ?? $update['listing_id'];
    }
}
```

## 6. Esquemas y validación OpenAPI

### `SchemaCatalog`

| Método | Para qué sirve |
| --- | --- |
| `__construct(?string $openApiFilePath = null)` | Carga el OpenAPI incluido o uno alternativo. |
| `title(): string` | Título de la especificación. |
| `version(): string` | Versión de la especificación. |
| `operations(): array` | Lista método, path, operationId y parámetros. |
| `schema(string $name): array` | Obtiene un schema por nombre o lanza `InvalidArgumentException`. |
| `listingCreateRequiredFields(): array` | Requeridos de `ListingPOST`. |
| `listingUpdateRequiredFields(): array` | Requeridos de `ListingPATCH`. |
| `listingStatusRequiredFields(): array` | Requeridos de `ListingStatus`. |
| `listingCreateItemSchema(): array` | Schema completo de cada item de creación. |
| `listingUpdateItemSchema(): array` | Schema completo de cada item de actualización. |
| `listingStatusItemSchema(): array` | Schema completo de cada cambio de estado. |

```php
$catalog = $sdk->schemaCatalog();
printf('%s %s', $catalog->title(), $catalog->version());

foreach ($catalog->operations() as $operation) {
    echo $operation['method'] . ' ' . $operation['path'] . PHP_EOL;
}

$required = $catalog->listingCreateRequiredFields();
$schema = $catalog->schema('ListingPOST');
```

### `ListingPayloadValidator`

| Método | Para qué sirve |
| --- | --- |
| `validateCreatePayload(array $listings, bool $strict = false): void` | Valida requeridos; en modo estricto también tipos, enums y requeridos anidados. |
| `validateUpdatePayload(array $listings, bool $strict = false): void` | Aplica las reglas de `ListingPATCH`. |
| `validateStatusPayload(array $statuses, bool $strict = false): void` | Aplica las reglas de `ListingStatus`. |

```php
use Homlity\Sdk\Schema\ListingPayloadValidator;

$validator = new ListingPayloadValidator($sdk->schemaCatalog());
$validator->validateCreatePayload([$payload], strict: true);
```

`ListingsApi` usa automáticamente la validación básica. El modo estricto se
expone para validaciones previas más exigentes.

## 7. Errores, respuesta HTTP y utilidades

### Jerarquía de excepciones

| Condición | Excepción |
| --- | --- |
| HTTP 401 | `AuthenticationException` |
| HTTP 403 | `AuthorizationException` |
| HTTP 404 | `NotFoundException` |
| HTTP 409 | `ConflictException` |
| HTTP 422 o DTO local inválido | `ValidationException` |
| HTTP 429 | `RateLimitException` |
| HTTP 5xx | `ServerException` |
| Error cURL/transporte | `TransportException` |
| Capacidad deliberadamente no soportada | `UnsupportedFeatureException` |
| Webhook inválido/no autorizado | `WebhookException` |

Todos los errores HTTP anteriores heredan de `ApiException`.

| Método de `ApiException` | Para qué sirve |
| --- | --- |
| `fromResponse(string $method, string $path, ApiResponse $response): self` | Fábrica que elige la subclase por código HTTP. |
| `response(): ?ApiResponse` | Respuesta original. |
| `statusCode(): ?int` | Código HTTP. |
| `json(): mixed` | Cuerpo JSON decodificado. |
| `trackingId(): ?string` | Busca IDs de tracking/request/correlation. |
| `defaultCode(): ?string` | Busca el código canónico del error. |
| `firstErrorMessage(): ?string` | Obtiene el primer mensaje legible del JSON. |

```php
use Homlity\Sdk\Exception\ApiException;
use Homlity\Sdk\Exception\ValidationException;

try {
    $tenant->tickets()->get(0);
} catch (ValidationException $error) {
    // Validación local: el ID debe ser positivo.
} catch (ApiException $error) {
    $context = [
        'status' => $error->statusCode(),
        'code' => $error->defaultCode(),
        'message' => $error->firstErrorMessage(),
        'tracking' => $error->trackingId(),
    ];
}
```

### `ApiResponse`

`__construct(int $statusCode, array $headers, string $body)` modela una respuesta
para transportes personalizados y pruebas.

| Método | Para qué sirve |
| --- | --- |
| `statusCode(): int` | Código HTTP. |
| `headers(): array` | Headers de respuesta. |
| `body(): string` | Cuerpo original. |
| `isSuccessful(): bool` | `true` para códigos 2xx. |
| `json(): mixed` | JSON decodificado o `null`; memoriza el resultado. |

### Transporte extensible

`HttpClientInterface::request(string $method, string $path, array $options =
[]): ApiResponse` permite sustituir cURL. Las opciones admitidas son `query`,
`headers`, `json` y `body`.

```php
use Homlity\Sdk\Http\ApiResponse;
use Homlity\Sdk\Http\HttpClientInterface;

final class TestHttpClient implements HttpClientInterface
{
    public function request(string $method, string $path, array $options = []): ApiResponse
    {
        return new ApiResponse(200, [], '{"data":[]}');
    }
}
```

`CurlHttpClient::__construct(Config $config)` crea el transporte incluido y
`request(...)` ejecuta la llamada con autenticación centralizada. Normalmente no
se instancia directamente: lo hace `HomlityClient`.

### Utilidades públicas

| Método | Para qué sirve | Ejemplo |
| --- | --- | --- |
| `DocumentNormalizer::normalize(string $document): string` | Quita espacios, convierte ASCII a mayúsculas y conserva símbolos válidos. | `DocumentNormalizer::normalize(' ab-12 ')` → `AB-12` |
| `ResponseData::object(mixed $response): array` | Extrae un objeto de respuestas directas o envueltas en `data`. | `ResponseData::object($json)` |

## 8. Cliente WordPress

Este bloque usa el namespace paralelo `Homlity\Sdk\Homlity`. La URL base es el
sitio WordPress que expone `homlity-sync/v1`.

### `Homlity\Sdk\Homlity\Config`

| Método | Para qué sirve |
| --- | --- |
| `__construct(string $apiKey, string $baseUrl, int $timeoutSeconds = 30, bool $signPropertyRequests = true, bool $retrySignedOnSignatureRequired = true)` | Configura token, sitio y firma HMAC. |
| `apiKey(): string` | Token configurado. |
| `baseUrl(): string` | URL del sitio sin `/` final. |
| `timeoutSeconds(): int` | Timeout de red. |
| `signPropertyRequests(): bool` | Indica si propiedades se firman desde el primer intento. |
| `retrySignedOnSignatureRequired(): bool` | Permite reintentar firmado ante `401 Firma requerida`. |

```php
use Homlity\Sdk\Homlity\Config as WordPressConfig;

$config = new WordPressConfig(
    apiKey: $_ENV['HOMLITY_WORDPRESS_TOKEN'],
    baseUrl: 'https://inmobiliaria.example.com',
    timeoutSeconds: 30,
    signPropertyRequests: true,
    retrySignedOnSignatureRequired: true,
);
```

### `Homlity\Sdk\Homlity\HomlityClient`

| Método | Para qué sirve |
| --- | --- |
| `__construct(Config $config, ?HttpClientInterface $httpClient = null)` | Crea el cliente WordPress. |
| `config(): Config` | Recupera la configuración. |
| `properties(): PropertiesApi` | Sincroniza/desactiva propiedades. |
| `analytics(): AnalyticsApi` | Consulta reportes. |
| `webhooks(): WebhooksApi` | Envía eventos de propiedad. |

```php
use Homlity\Sdk\Homlity\HomlityClient as WordPressClient;

$wordpress = new WordPressClient($config);
$propertiesApi = $wordpress->properties();
$analyticsApi = $wordpress->analytics();
$webhooksApi = $wordpress->webhooks();
```

### Propiedades WordPress

| Método | Para qué sirve | Retorna |
| --- | --- | --- |
| `push(array $properties): mixed` | Valida, normaliza y sincroniza un inmueble o lote. | JSON/cuerpo del plugin |
| `deactivate(string $externalId, array $payload = []): mixed` | Desactiva una propiedad por ID externo. | JSON/cuerpo del plugin |

```php
$wordpress->properties()->push([
    'id' => '12345',
    'code' => 'EXT-12345',
    'status' => 'active',
    'operation' => 'venta',
    'type' => 'apartamento',
    'category' => 'residencial',
    'media' => ['photos' => [], 'videos' => []],
]);

$wordpress->properties()->deactivate('12345');
$wordpress->properties()->deactivate('12345', ['reason' => 'sold']);
```

Ambos métodos firman el cuerpo exacto con
`sha256=<hash_hmac('sha256', body, apiKey)>` cuando la firma está habilitada.

### Analítica WordPress

`report(array $filters = []): mixed` consulta el reporte consolidado.

| Filtro | Regla |
| --- | --- |
| `range` | `1`, `7`, `15`, `30`, `60`, `90`, `180` o `365` |
| `from`, `to` | Fechas `YYYY-MM-DD` |
| `advisor_id` | Asesor |
| `property_id` | Propiedad WordPress |
| `external_id` | ID externo de sincronización |
| `limit` | Entre 1 y 50 |

```php
$lastMonth = $wordpress->analytics()->report(['range' => 30, 'limit' => 20]);

$period = $wordpress->analytics()->report([
    'from' => '2026-08-01',
    'to' => '2026-08-31',
    'advisor_id' => 25,
]);
```

### Eventos WordPress

`notify(string $event, string $propertyId): mixed` valida y envía un evento al
endpoint del plugin.

```php
$wordpress->webhooks()->notify('property.created', '12345');
$wordpress->webhooks()->notify('property.updated', '12345');
```

### Normalización y validación WordPress

| Clase / método | Para qué sirve |
| --- | --- |
| `PropertyPayloadNormalizer::normalize(array $property): array` | Convierte operación, tipo, categoría y ubicaciones a slugs; normaliza características, `broshure` → `brochure` y conserva el primer video. |
| `PropertyPayloadValidator::validateUpsertPayload(array $properties): void` | Exige al menos un item y los campos `id`, `code`, `status`, `operation`, `type`, `category`, `media`. |
| `PropertyPayloadValidator::validateWebhookPayload(array $payload): void` | Exige `event` y `property_id` en cada item. |

```php
use Homlity\Sdk\Homlity\Schema\PropertyPayloadNormalizer;
use Homlity\Sdk\Homlity\Schema\PropertyPayloadValidator;

$validator = new PropertyPayloadValidator();
$normalizer = new PropertyPayloadNormalizer();

$validator->validateUpsertPayload([$property]);
$normalized = $normalizer->normalize($property);
```

### Webhook entrante firmado de WordPress

`Homlity\Sdk\Homlity\Webhook\WebhookNotification` ofrece:

| Método | Para qué sirve |
| --- | --- |
| `fromRequest(string $rawBody, array $headers = []): self` | Decodifica el JSON conservando el cuerpo exacto para la firma. |
| `assertAuthorizedSignature(string $secret, string $headerName = 'x-homlity-signature'): void` | Valida `sha256=<hex_hmac>` con `hash_equals`. |
| `header(string $name): ?string` | Lee un header sin distinguir mayúsculas. |
| `payload(): array` | Devuelve el JSON validado. |

```php
use Homlity\Sdk\Homlity\Webhook\WebhookNotification as WordPressNotification;

$rawBody = file_get_contents('php://input') ?: '';
$notification = WordPressNotification::fromRequest($rawBody, getallheaders());
$notification->assertAuthorizedSignature($_ENV['HOMLITY_WEBHOOK_SECRET']);
$payload = $notification->payload();
```

### Modelo y transporte WordPress

`Homlity\Sdk\Homlity\Data\PropertySnapshot` tiene `fromArray(array $data)`,
`id(): ?string`, `status(): ?string` y `toArray(): array`:

```php
use Homlity\Sdk\Homlity\Data\PropertySnapshot as WordPressProperty;

$snapshot = WordPressProperty::fromArray(['id' => 123, 'status' => 'active']);
echo $snapshot->id();
```

El namespace WordPress también publica su propio `HttpClientInterface`,
`CurlHttpClient`, `ApiResponse` y `ApiException` con el mismo propósito general
que sus equivalentes de la API principal. `ApiException::fromResponse()` agrega
una pista específica cuando el plugin responde que la firma es obligatoria.

| Método de infraestructura WordPress | Para qué sirve |
| --- | --- |
| `HttpClientInterface::request(string $method, string $path, array $options = []): ApiResponse` | Contrato para un transporte alternativo. |
| `CurlHttpClient::__construct(Config $config)` | Crea el transporte incluido. |
| `CurlHttpClient::request(string $method, string $path, array $options = []): ApiResponse` | Ejecuta la solicitud autenticada. |
| `ApiResponse::__construct(int $statusCode, array $headers, string $body)` | Modela una respuesta. |
| `ApiResponse::statusCode(): int` | Devuelve el código HTTP. |
| `ApiResponse::headers(): array` | Devuelve los headers. |
| `ApiResponse::body(): string` | Devuelve el cuerpo original. |
| `ApiResponse::isSuccessful(): bool` | Comprueba si el código es 2xx. |
| `ApiResponse::json(): mixed` | Decodifica JSON o devuelve `null`. |
| `ApiException::fromResponse(string $method, string $path, ApiResponse $response): self` | Construye el error HTTP y agrega ayuda para firma requerida. |

La excepción WordPress conserva el cuerpo en su mensaje por compatibilidad con
esa superficie. No registres el mensaje sin filtrarlo si el plugin puede
responder datos personales.

## 9. Ejemplo integral tenant

Este flujo muestra el propósito central del SDK: conectar recursos sin enviar
un selector manual de inmobiliaria.

```php
use Homlity\Sdk\Config;
use Homlity\Sdk\Data\ClientVerificationStatus;
use Homlity\Sdk\Filter\PropertyFilters;
use Homlity\Sdk\HomlityClient;
use Homlity\Sdk\Request\CreateLeadRequest;
use Homlity\Sdk\Request\CreateTicketRequest;
use Homlity\Sdk\Request\TicketLeadReference;

$tenant = new HomlityClient(
    Config::forTenantApi($_ENV['HOMLITY_ACCESS_TOKEN'])
);

$properties = $tenant->properties()->search(new PropertyFilters(
    search: 'apartamento',
    page: 1,
    perPage: 10,
));

if ($properties->isEmpty()) {
    throw new RuntimeException('No se encontraron inmuebles.');
}

$property = $properties->items()[0];
$verification = $tenant->clients()->verifyDocument('AB-123456', 'CE');
$clientId = $verification->status() === ClientVerificationStatus::CLIENT
    ? $verification->client()?->id()
    : null;

$leadRequest = new CreateLeadRequest(
    name: 'Ada Lovelace',
    email: 'ada@example.com',
    propertyId: $property->id(),
    clientId: $clientId,
);
$lead = $tenant->leads()->create($leadRequest)->lead();

$ticketMetadata = [
    'lead_id' => $lead->id(),
    'property_id' => $property->id(),
];
if ($clientId !== null) {
    $ticketMetadata['client_id'] = $clientId;
}

$ticket = $tenant->tickets()->create(new CreateTicketRequest(
    subject: 'Seguimiento lead #' . $lead->id(),
    description: 'Coordinar visita al inmueble ' . ($property->code() ?? $property->id()),
    metadata: $ticketMetadata,
    leads: [new TicketLeadReference(
        name: $leadRequest->name,
        email: $leadRequest->email,
    )],
));

printf('Lead %d; ticket %d', $lead->id(), $ticket->id());
```

La versión ejecutable está en
[`examples/tenant-workflow.php`](../examples/tenant-workflow.php).

## 10. Capacidades pendientes del backend

El SDK documenta explícitamente lo que el contrato actual no soporta:

- endpoint exacto y tenant-wide para verificar documentos;
- idempotencia persistente de leads;
- más campos de marketing/consentimiento en leads;
- relaciones first-class de tickets por IDs de cliente, inmueble y lead;
- filtros adicionales y ordenamiento configurable de inmuebles tenant.

Consulta el detalle técnico y la propuesta de contrato en
[recursos tenant](tenant-resources.md#contratos-backend-pendientes). El SDK no
envía campos ni headers ignorados para aparentar capacidades inexistentes.

## Apéndice: constructores técnicos públicos

Normalmente debes crear recursos a través de `HomlityClient`. Estos
constructores siguen siendo públicos para inyección de dependencias y pruebas:

| Firma | Uso |
| --- | --- |
| `BaseApi::__construct(HttpClientInterface $httpClient)` | Constructor heredado por recursos sin dependencias adicionales. |
| `ListingsApi::__construct(HttpClientInterface $httpClient, ListingPayloadValidator $validator)` | Inyecta transporte y validador de publicaciones. |
| `ListingPayloadValidator::__construct(SchemaCatalog $catalog)` | Vincula la validación con un OpenAPI. |
| `CurlHttpClient::__construct(Config $config)` | Crea el transporte cURL principal. |
| `ApiException::__construct(string $message, ?ApiResponse $response = null, ?Throwable $previous = null)` | Construye manualmente un error con contexto opcional. |
| `PaginationMetadata::__construct(int $currentPage, int $lastPage, int $perPage, int $total)` | Construye metadata manual para adaptadores. |
| `LeadCreationResult::__construct(LeadSnapshot $lead, LeadCreationStatus $status, array $raw)` | Construye un resultado en adaptadores o pruebas. |
| `Homlity\Sdk\Homlity\Api\BaseApi::__construct(Homlity\Sdk\Homlity\Http\HttpClientInterface $httpClient)` | Base de recursos del namespace WordPress. |
| `Homlity\Sdk\Homlity\Api\PropertiesApi::__construct(HttpClientInterface $httpClient, Config $config, PropertyPayloadValidator $validator, PropertyPayloadNormalizer $normalizer)` | Cableado manual del recurso WordPress. |
| `Homlity\Sdk\Homlity\Api\WebhooksApi::__construct(HttpClientInterface $httpClient, PropertyPayloadValidator $validator)` | Cableado manual de eventos WordPress. |

Ejemplo de inyección recomendado para pruebas:

```php
$sdk = new HomlityClient(
    config: $config,
    httpClient: new TestHttpClient(),
    schemaCatalog: new SchemaCatalog(),
);
```

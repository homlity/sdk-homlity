<p align="center">
  <img src="assets/homlity-developers.png" width="360" alt="Homlity para desarrolladores">
</p>

# Recursos tenant: perfil, catálogos, inmuebles, tickets, clientes y leads

[Inicio](index.md) · [Clases y métodos](public-api.md) · [Mapa de endpoints](api-reference.md)

## Arquitectura y autenticación

Estos recursos consumen la API operativa de Homlity, no la API histórica de
Integradores. Se configuran con un access token Bearer:

```php
use Homlity\Sdk\Config;
use Homlity\Sdk\HomlityClient;

$sdk = new HomlityClient(Config::forTenantApi(
    accessToken: $_ENV['HOMLITY_ACCESS_TOKEN'],
));
```

`Config::forTenantApi()` usa `https://web.homlity.com/api` por defecto. En
desarrollo puede indicarse otra base URL como segundo argumento. El transporte
añade `Authorization: Bearer ...` de forma centralizada. Los recursos no
aceptan `id_inmobiliaria`: el backend lo deriva de `Auth::user()` y aplica sus
consultas y relaciones dentro de ese tenant.

La configuración histórica `new Config(apiKey: ...)` conserva los headers
`apikey` y `X-API-KEY`; no se cambió su comportamiento.

## Endpoints verificados

| SDK | HTTP | Aislamiento observado en backend |
| --- | --- | --- |
| `company()->profile` | `GET /v1/inmobiliaria/profile` | inmobiliaria derivada exclusivamente de `Auth::user()->id_inmobiliaria` |
| `channels()->list` | `GET /v1/channels` | catálogo disponible para el tenant autenticado |
| `properties()->list/search` | `GET /v1/propertys` | `where('id_inmobiliaria', Auth::user()->id_inmobiliaria)` |
| `properties()->get/getByCode` | `GET /v1/integrations/properties/{id_or_code}` | ID/código buscado dentro del tenant autenticado |
| `tickets()->create` | `POST /v1/tickets` | el backend asigna `id_inmobiliaria` desde el usuario |
| `tickets()->list/get` | `GET /v1/tickets[/{id}]` | consultas limitadas al tenant y usuario autenticados |
| `tickets()->categories` | `GET /v1/tickets/categories` | categorías de PQR disponibles para el tenant autenticado |
| `clients()->verifyDocument` | `GET /v1/clients?q=...` | búsqueda SQL dentro del tenant y visibilidad del usuario |
| `leads()->create` | `POST /v1/leads` | `LeadCreator` asigna el tenant autenticado |
| lead con inmueble | `POST /sistema/inmuebles/{id}/leads` | middleware `tenant.ownership` y lookup tenant-scoped |
| relación lead/cliente | `POST /v1/leads/{id}/attach-client` | lead y cliente deben pertenecer al tenant autenticado |

No se usan rutas inferidas ni se envían campos desconocidos al backend.

## Perfil de la inmobiliaria

El perfil público se consulta en vivo para el tenant representado por el token:

```php
$profile = $sdk->company()->profile();

echo $profile->id();
echo $profile->name();
echo $profile->phone();
echo $profile->email();
echo $profile->address();
echo $profile->city();
echo $profile->publicUrl();

foreach ($profile->businessHours() as $day => $hours) {
    // La estructura de horarios se conserva tal como la publica Homlity.
}
```

`CompanyProfile` es inmutable. Los datos de contacto ausentes son `null` y
`businessHours()` devuelve un arreglo vacío cuando no hay horarios. El DTO no
contiene credenciales ni datos internos del usuario.

## Inmuebles

Filtros respaldados por `InmueblesController::v1_index`:

```php
use Homlity\Sdk\Filter\PropertyFilters;

$page = $sdk->properties()->search(new PropertyFilters(
    search: 'apartamento laureles',
    statuses: [1, 2],
    propertyTypeIds: [2, 4],
    businessTypeId: 1,
    cityId: 10,
    neighborhoodId: 25,
    adviserId: 8,
    stratum: 4,
    rooms: 3,
    bathrooms: 2,
    parkingSpaces: 1,
    rentPriceMin: 1_500_000,
    rentPriceMax: 3_000_000,
    salePriceMin: 300_000_000,
    salePriceMax: 700_000_000,
    builtAreaMin: 60,
    builtAreaMax: 120,
    tags: ['balcon' => true],
    origin: 'mobile',
    page: 1,
    perPage: 20,
));

foreach ($page->items() as $property) {
    echo $property->id() . ' ' . $property->code() . PHP_EOL;
}

$meta = $page->metadata();
while ($meta->hasNextPage()) {
    $page = $sdk->properties()->search(new PropertyFilters(
        search: 'apartamento laureles',
        page: $meta->currentPage() + 1,
        perPage: $meta->perPage(),
    ));
    $meta = $page->metadata();
}
```

Detalle por ID o código de la inmobiliaria:

```php
$byId = $sdk->properties()->get(123);
$byCode = $sdk->properties()->getByCode('INM-2026-001');
```

El backend fija el orden por creación descendente. No soporta todavía en esta
ruta filtros de país/departamento, área privada, fecha de creación, publicado,
destacado, coordenadas/radio, referencia externa ni ordenamiento configurable.

## Tickets

Los canales de ingreso y las categorías de PQR se consultan en vivo. Sus DTO
conservan también el elemento original mediante `raw()`:

```php
$channels = $sdk->channels()->list();

foreach ($channels as $channel) {
    echo $channel->id();
    echo $channel->name();
}

$categories = $sdk->tickets()->categories();

foreach ($categories as $category) {
    echo $category->id();
    echo $category->name();
    echo $category->description() ?? '';
    echo $category->parentId() ?? '';
}
```

Las aplicaciones consumidoras, incluido Homlity Chat, deben persistir los IDs
devueltos por estos endpoints y no usar IDs hardcodeados.

El contrato JSON actual de creación admite asunto, cuerpo, categoría, metadata,
destinatarios y referencias de lead por nombre más teléfono o correo:

```php
use Homlity\Sdk\Request\CreateTicketRequest;
use Homlity\Sdk\Request\TicketLeadReference;

$ticket = $sdk->tickets()->create(new CreateTicketRequest(
    subject: 'Solicitud de visita',
    description: 'Coordinar visita al inmueble INM-2026-001.',
    categoryId: 3,
    recipients: ['Asesor <asesor@example.com>'],
    metadata: ['source' => 'sdk'],
    leads: [
        new TicketLeadReference(
            name: 'Ada Lovelace',
            email: 'ada@example.com',
        ),
    ],
));
```

Listado y detalle:

```php
use Homlity\Sdk\Filter\TicketFilters;

$tickets = $sdk->tickets()->list(new TicketFilters(
    search: 'visita',
    role: 'owner',
    finalized: false,
    categoryId: 3,
    priorityId: 2,
    propertyId: 123,
    deadlineFrom: '2026-09-01',
    deadlineTo: '2026-09-30',
    page: 1,
    perPage: 20,
));

$detail = $sdk->tickets()->get($ticket->id());
```

La creación v1 aún no admite prioridad, canal, solicitante tipado, `client_id`,
`property_id`, `lead_id`, adjuntos JSON ni ordenamiento. `leads` enlaza por los
datos de contacto soportados por el backend; no equivale a aceptar un `lead_id`.

## Verificación de cliente por documento

```php
use Homlity\Sdk\Data\ClientVerificationStatus;

$verification = $sdk->clients()->verifyDocument('AB-123456', 'CE');

match ($verification->status()) {
    ClientVerificationStatus::CLIENT => $verification->client()?->id(),
    ClientVerificationStatus::NOT_CLIENT => null,
    ClientVerificationStatus::INVALID_DOCUMENT => null,
    ClientVerificationStatus::MULTIPLE_MATCHES => $verification->matches(),
};
```

La normalización elimina espacios, convierte letras ASCII a mayúsculas y
conserva guiones y demás símbolos. El objeto `ClientMatch` no expone correo ni
teléfono y devuelve el documento enmascarado.

El backend no posee aún un endpoint de verificación exacta. La implementación
actual usa su búsqueda server-side paginada (`q`) y conserva coincidencias
exactas; no descarga el catálogo completo. La visibilidad también depende de
los permisos del usuario, de modo que un token de asesor puede no representar
todo el tenant. Consulta “Contratos pendientes”.

## Leads

Creación mínima:

```php
use Homlity\Sdk\Request\CreateLeadRequest;

$result = $sdk->leads()->create(new CreateLeadRequest(
    name: 'Ada Lovelace',
    email: 'ada@example.com',
));
```

Todos los campos respaldados y relaciones disponibles:

```php
use Homlity\Sdk\Request\CreateLeadRequest;
use Homlity\Sdk\Request\LeadRequirements;

$result = $sdk->leads()->create(new CreateLeadRequest(
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
        parkingSpaces: '1',
        areaMin: 60,
        areaMax: 120,
        stratum: '4',
        ageMin: 0,
        ageMax: 10,
        businessType: 'venta',
        propertyTypeIds: [2],
        cityId: 10,
        neighborhoodId: 25,
    ),
    propertyId: 123,
    clientId: 456,
));
```

Con `propertyId`, se usa la ruta del inmueble que valida tenant y conserva un
resumen del inmueble en `requerimiento.inmueble_relacionado`. Con `clientId`, el
SDK llama después a `attach-client`, que vuelve a validar el tenant. Esta
segunda operación no es transaccional con la creación: si falla, el lead ya
puede existir y el error se propaga con su código HTTP.

### Idempotencia

El backend de leads no procesa actualmente `Idempotency-Key`. Para no presentar
un reintento inseguro como seguro, el SDK rechaza explícitamente una clave:

```php
use Homlity\Sdk\Exception\UnsupportedFeatureException;

try {
    $sdk->leads()->create($request, idempotencyKey: 'contact-form-123');
} catch (UnsupportedFeatureException $error) {
    // No reintentar automáticamente hasta desplegar el contrato backend.
}
```

## Errores

`BaseApi` conserva el `ApiResponse`, código HTTP, JSON y tracking ID, pero no
incluye el body —que podría contener datos personales— en el mensaje de la
excepción.

| HTTP | Excepción |
| --- | --- |
| 401 | `AuthenticationException` |
| 403 | `AuthorizationException` |
| 404 | `NotFoundException` |
| 409 | `ConflictException` |
| 422 | `ValidationException` |
| 429 | `RateLimitException` |
| 5xx | `ServerException` |
| transporte | `TransportException` |

Las validaciones locales de DTO también lanzan `ValidationException`.

## Flujo integral

El ejemplo [`examples/tenant-workflow.php`](../examples/tenant-workflow.php)
autentica la inmobiliaria, busca un inmueble, verifica un documento, crea el
lead con las relaciones admitidas y crea un ticket asociado por la referencia
de lead que acepta hoy el backend.

## Contratos backend pendientes

Para cubrir sin supuestos el contrato objetivo completo, el backend debe añadir:

1. `GET /api/v1/clients/verify` con búsqueda exacta por tipo+número, alcance de
   todo el tenant y estados explícitos `client`, `not_client`, `invalid_document`
   y `multiple_matches`. Debe devolver PII mínima/enmascarada.
2. Idempotencia tenant-scoped en `POST /api/v1/leads`: persistir
   `Idempotency-Key` y hash del payload, reproducir la respuesta original y
   responder `409` si una misma clave llega con contenido diferente. La
   respuesta debe indicar `created`, `reused` o `duplicate`.
3. Campos de lead aún ausentes: documento, apellidos separados, código de país,
   campaña, etiquetas, UTM, URL de origen, consentimientos, metadata, relación
   first-class con inmueble y `ticket_id`.
4. Creación de tickets con IDs tenant-scoped de cliente, inmueble y lead;
   prioridad, canal, solicitante y adjuntos. La deduplicación actual por título
   y cuerpo debe incluir `id_inmobiliaria` y devolver `409`, no un `404`
   ambiguo.
5. Los filtros de inmueble mencionados arriba y ordenamiento con whitelist.

Hasta que esos contratos existan, el SDK no envía esos campos, no inventa
deduplicación local y no activa creación automática de leads en otros flujos.

<p align="center">
  <a href="https://homlity.com/desarrolladores/">
    <img src="assets/homlity-developers.png" width="520" alt="Homlity para desarrolladores">
  </a>
</p>

# Documentación del Homlity PHP SDK

El Homlity PHP SDK es la capa de integración para conectar aplicaciones PHP con
los servicios inmobiliarios de Homlity y con sitios WordPress que utilizan su
plugin de sincronización.

## Qué problema resuelve

Una integración inmobiliaria suele tener que autenticar solicitudes, serializar
filtros, validar payloads, recorrer páginas, interpretar tareas asíncronas y
proteger webhooks. El SDK concentra esas responsabilidades en una interfaz PHP
consistente para que el consumidor trabaje con recursos como inmuebles,
clientes, leads y tickets.

Su propósito está alineado con el de Homlity: conectar plataformas, automatizar
procesos y evitar desarrollos repetidos dentro del ecosistema inmobiliario.

## Arquitectura en una mirada

| Superficie | Namespace principal | URL predeterminada | Casos de uso |
| --- | --- | --- | --- |
| Integradores | `Homlity\Sdk\HomlityClient` | `https://kong.homlity.com.co/management/api/1.0` | Publicación de inmuebles, catálogos, tareas y webhooks |
| Tenant | `Homlity\Sdk\HomlityClient` | `https://web.homlity.com/api` mediante `Config::forTenantApi()` | Consulta de inmuebles, tickets, clientes y leads de una inmobiliaria |
| WordPress | `Homlity\Sdk\Homlity\HomlityClient` | URL del sitio proporcionada por el consumidor | Sincronización de propiedades y analítica del plugin |

Integradores y tenant usan el mismo cliente raíz, pero requieren instancias con
configuraciones diferentes. Si una aplicación usa ambos, crea dos clientes:

```php
use Homlity\Sdk\Config;
use Homlity\Sdk\HomlityClient;

$integrators = new HomlityClient(new Config(
    apiKey: $_ENV['HOMLITY_API_KEY'],
));

$tenant = new HomlityClient(Config::forTenantApi(
    accessToken: $_ENV['HOMLITY_ACCESS_TOKEN'],
));
```

## Rutas de aprendizaje

### Quiero operar datos de una inmobiliaria

Empieza en [recursos tenant](tenant-resources.md). Allí encontrarás:

- autenticación Bearer y aislamiento por tenant;
- filtros y paginación de inmuebles;
- creación, listado y detalle de tickets;
- verificación de clientes por documento;
- creación y relación de leads;
- limitaciones confirmadas del contrato backend.

El ejemplo integral es
[`examples/tenant-workflow.php`](../examples/tenant-workflow.php).

### Quiero publicar inmuebles como integrador

Consulta en este orden:

1. [Mapa de endpoints](api-reference.md).
2. [Parámetros de inmuebles](listing-parameters.md).
3. [Tareas y webhooks](webhooks.md).
4. [`examples/publish-listing.php`](../examples/publish-listing.php).

### Quiero sincronizar un sitio WordPress

Consulta la [API del plugin](homlity-api-reference.md), los
[webhooks firmados](homlity-webhooks.md) y los ejemplos cuyo nombre comienza
con `homlity-` en el directorio [`examples`](../examples/).

### Quiero conocer una función específica

La [referencia pública completa](public-api.md) enumera cada método orientado al
consumidor, explica para qué sirve, muestra su firma, su resultado y una forma
de uso.

## Conceptos esenciales

### Autenticación

- Integradores: el transporte envía `apikey` y `X-API-KEY`.
- Tenant: el transporte envía `Authorization: Bearer <token>`.
- WordPress: el transporte envía Bearer; `push()` y `deactivate()` agregan una
  firma `X-Homlity-Signature` calculada sobre el cuerpo exacto.

Las credenciales se configuran una vez en el cliente. No las agregues manualmente
a cada solicitud ni las escribas en logs.

### Multi-tenancy

Los recursos tenant no aceptan un identificador de inmobiliaria. El backend
deriva el tenant desde el usuario autenticado y limita búsquedas y relaciones.
Un `propertyId` o `clientId` de otra inmobiliaria debe ser rechazado por el
backend, no reinterpretado por el SDK.

### Datos tipados y datos crudos

Las operaciones tenant devuelven snapshots y resultados paginados tipados. Sus
métodos `raw()` preservan los campos autorizados que todavía no tienen un
accesor dedicado. Algunas operaciones históricas de Integradores conservan
`mixed` por compatibilidad y devuelven directamente el JSON decodificado.

### Errores

Las respuestas no exitosas de la API principal se convierten en excepciones por
categoría. Puedes consultar `statusCode()`, `defaultCode()`,
`firstErrorMessage()` y `trackingId()` sin perder el contexto HTTP.

## Índice de referencia

- [Clases y métodos públicos](public-api.md)
- [Métodos y endpoints HTTP](api-reference.md)
- [Inmuebles, tickets, clientes y leads](tenant-resources.md)
- [Contrato de publicación de inmuebles](listing-parameters.md)
- [Tareas y webhooks de Integradores](webhooks.md)
- [Sincronización WordPress](homlity-api-reference.md)
- [Webhooks WordPress](homlity-webhooks.md)
- [Especificación OpenAPI incluida](../resources/openapi/homlity-integradores-1.0.0.json)

## Marca y recursos oficiales

La identidad visual utilizada en esta documentación corresponde al recurso de
“Homlity para desarrolladores”. Para contexto de producto, comunicación y
ecosistema consulta [homlity.com](https://homlity.com/) y
[homlity.com/desarrolladores](https://homlity.com/desarrolladores/).

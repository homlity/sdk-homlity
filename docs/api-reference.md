<p align="center">
  <img src="assets/homlity-developers.png" width="360" alt="Homlity para desarrolladores">
</p>

# Mapa de endpoints

[Inicio](index.md) · [Clases y métodos](public-api.md) · [Recursos tenant](tenant-resources.md)

Fuente: `resources/openapi/homlity-integradores-1.0.0.json` (SwaggerHub Integradores v1.0.0).

## Endpoints cubiertos

| Metodo | Path | OperationId | Metodo SDK |
| --- | --- | --- | --- |
| GET | `/location/{name}` | `getLocation` | `locations()->search(string $name)` |
| GET | `/category` | `getCategory` | `categories()->list(array $query = [])` |
| GET | `/client/{client_id}/agent` | `getAgent` | `clients()->agents(string $clientId)` |
| GET | `/client/` | `getClients` | `clients()->all()` |
| GET | `/client/{client_id}` | `getClientId` | `clients()->get(string $clientId)` |
| GET | `/listing/{listing_id}` | `getListing` | `listings()->get(string $listingId)` |
| GET | `/listing` | `getListingClient` | `listings()->list(string $clientCookie, array $query = [])` |
| POST | `/listing` | `listing_post` | `listings()->create(array $listings)` |
| PATCH | `/listing` | `listing_patch` | `listings()->update(array $listings)` |
| PATCH | `/listing/status` | `listing_status_patch` | `listings()->updateStatus(array $statuses)` |
| POST | `/validate-listing` | `listing_valdiate_post` | `listings()->validate(array $payload)` |
| GET | `/task/{task_id}` | `getTask` | `tasks()->get(string $taskId)` |
| POST | `/api/homlity/` | `postWebhook` | `webhooks()->postEvent(string $hubId, string $verifyToken, array $payload)` |
| POST | `/webhook/{id}/subscribe` | `postWebhookSubscribe` | `webhooks()->subscribe(string $integratorId, array $payload)` |
| POST | `/webhook/{id}/unsubscribe` | `postWebhookUnsubscribe` | `webhooks()->unsubscribe(string $integratorId)` |

## Parametros generales

- Header obligatorio de autenticacion para casi todos los endpoints: `apikey`.
- El SDK envia automaticamente `apikey` y `X-API-KEY` con el token configurado.
- Endpoint `GET /listing` exige adicionalmente header `Cookie`.
- Endpoint `POST /api/homlity/` usa headers `HUB.ID` y `VERIFY-TOKEN`.

## API tenant de Homlity

Fuente verificada: rutas, controladores, Form Requests y Resources del backend
`web homlity`. Base URL productiva: `https://web.homlity.com/api`.

| Metodo | Path | Metodo SDK |
| --- | --- | --- |
| GET | `/v1/propertys` | `properties()->list()` / `properties()->search(PropertyFilters $filters)` |
| GET | `/v1/integrations/properties/{id_or_code}` | `properties()->get(int|string $property)` / `getByCode(string $code)` |
| POST | `/v1/tickets` | `tickets()->create(CreateTicketRequest $request)` |
| GET | `/v1/tickets` | `tickets()->list(?TicketFilters $filters = null)` |
| GET | `/v1/tickets/{ticket}` | `tickets()->get(int $ticketId)` |
| GET | `/v1/clients?q={document}` | `clients()->verifyDocument(string $document, int|string|null $documentType = null)` |
| POST | `/v1/leads` | `leads()->create(CreateLeadRequest $request)` sin inmueble |
| POST | `/sistema/inmuebles/{property}/leads` | `leads()->create(CreateLeadRequest $request)` con `propertyId` |
| POST | `/v1/leads/{lead}/attach-client` | ejecutado por `leads()->create()` cuando existe `clientId` |

Todos requieren Bearer token y el backend resuelve el tenant desde el usuario
autenticado. Consulta [`tenant-resources.md`](tenant-resources.md) para el
contrato detallado y sus limitaciones actuales.

## Plugin Homlity para WordPress

La URL base es el dominio del sitio WordPress. Este bloque usa
`Homlity\Sdk\Homlity\HomlityClient`.

| Método | Path | Método SDK |
| --- | --- | --- |
| POST | `/wp-json/homlity-sync/v1/properties` | `properties()->push(array $properties)` |
| POST | `/wp-json/homlity-sync/v1/properties/{id}/deactivate` | `properties()->deactivate(string $externalId, array $payload = [])` |
| POST | `/wp-json/homlity-sync/v1/webhook` | `webhooks()->notify(string $event, string $propertyId)` |
| GET | `/wp-json/homlity-sync/v1/analytics/report` | `analytics()->report(array $filters = [])` |

`push()` y `deactivate()` pueden agregar `X-Homlity-Token` y
`X-Homlity-Signature`. Consulta la
[referencia del plugin](homlity-api-reference.md) para payloads, filtros y firma.

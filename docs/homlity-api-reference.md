# Homlity API Reference

## Base URL
Configurable via `Fincaraiz\Sdk\Homlity\Config`.

## Endpoints
- `POST /wp-json/homlity-sync/v1/properties`
- `POST /wp-json/homlity-sync/v1/properties/{id}/deactivate`
- `POST /wp-json/homlity-sync/v1/webhook`
- `GET /wp-json/homlity-sync/v1/analytics/report`

## Cliente
```php
$client = new \Fincaraiz\Sdk\Homlity\HomlityClient(
    new \Fincaraiz\Sdk\Homlity\Config('api-key', 'https://tu-wp.com')
);
```

## Payload de propiedades
Se acepta objeto único o batch. Campos mínimos requeridos por item:
- `id`, `code`, `status`, `operation`, `type`, `category`, `media`

Reglas importantes:
- Multimedia oficial en `media`.
- `media.brochure` es la clave correcta (`broshure` se normaliza a `brochure`).
- `media.videos` conserva solo el primer video para respetar limitación del plugin.

## Analytics consolidado
```php
$report = $client->analytics()->report([
    'range' => 30,
    'property_id' => 123,
    'limit' => 20,
]);

$reportExact = $client->analytics()->report([
    'from' => '2026-04-01',
    'to' => '2026-04-30',
    'advisor_id' => 25,
    'external_id' => '3297',
    'limit' => 20,
]);
```

Filtros soportados:
- `range`: `1|7|15|30|60|90|180|365` (default del plugin: `30`)
- `from` + `to` (formato `YYYY-MM-DD`)
- `advisor_id` (limita analítica a inmuebles del asesor usando `_property_agent_id`)
- `property_id`
- `external_id` (meta `_homlity_sync_id`)
- `limit` (máximo `50`)

La respuesta puede incluir:
- `visits` (total y únicos)
- `clicks` (total, whatsapp, phone, email)
- `performance` (`ctr`, `clicks_per_visit`)
- `daily_visits`
- `daily_clicks`
- `top_properties`
- `advisor_contacts` (ranking por asesor)
- `most_contacted_advisor` (primer asesor del ranking)

Formato adicional esperado:
- `data.advisor_contacts[]`
- `data.advisor_contacts[].advisor_id`
- `data.advisor_contacts[].advisor_name`
- `data.advisor_contacts[].total_clicks`
- `data.advisor_contacts[].properties[]`
- `data.advisor_contacts[].properties[].property_id`
- `data.advisor_contacts[].properties[].title`
- `data.advisor_contacts[].properties[].clicks`
- `data.most_contacted_advisor`

Cuando envías `advisor_id`, el filtro aplica a todo el reporte: `visits`, `clicks`, `performance`, series diarias, `top_properties` y bloque de asesores. Si el asesor no tiene inmuebles, el plugin debe responder dataset vacío de forma segura.

Si en WordPress no existen tablas de analítica, el plugin debería responder `200` con `available=false` y estructura vacía. El SDK retorna esa respuesta tal cual.

### Prueba rápida (ficha técnica)
```bash
curl -H "X-Homlity-Token: TU_TOKEN" \
"http://localhost:8000/wp-json/homlity-sync/v1/analytics/report?range=30"
```

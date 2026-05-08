# Homlity API Reference

## Base URL
Configurable via `Fincaraiz\Sdk\Homlity\Config`.

## Endpoints
- `POST /wp-json/homlity-sync/v1/properties`
- `POST /wp-json/homlity-sync/v1/properties/{id}/deactivate`
- `POST /wp-json/homlity-sync/v1/webhook`

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

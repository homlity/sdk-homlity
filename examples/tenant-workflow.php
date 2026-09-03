<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Homlity\Sdk\Config;
use Homlity\Sdk\Data\ClientVerificationStatus;
use Homlity\Sdk\Filter\PropertyFilters;
use Homlity\Sdk\HomlityClient;
use Homlity\Sdk\Request\CreateLeadRequest;
use Homlity\Sdk\Request\CreateTicketRequest;
use Homlity\Sdk\Request\LeadRequirements;
use Homlity\Sdk\Request\TicketLeadReference;

$token = getenv('HOMLITY_ACCESS_TOKEN');
$document = getenv('HOMLITY_CLIENT_DOCUMENT');
$email = getenv('HOMLITY_LEAD_EMAIL');

if (!$token || !$document || !$email) {
    fwrite(STDERR, "Define HOMLITY_ACCESS_TOKEN, HOMLITY_CLIENT_DOCUMENT y HOMLITY_LEAD_EMAIL.\n");
    exit(1);
}

$sdk = new HomlityClient(Config::forTenantApi($token));

$properties = $sdk->properties()->search(new PropertyFilters(
    search: getenv('HOMLITY_PROPERTY_SEARCH') ?: 'apartamento',
    page: 1,
    perPage: 10,
));

if ($properties->isEmpty()) {
    fwrite(STDERR, "No se encontraron inmuebles.\n");
    exit(2);
}

$property = $properties->items()[0];
$verification = $sdk->clients()->verifyDocument(
    $document,
    getenv('HOMLITY_CLIENT_DOCUMENT_TYPE') ?: null,
);
$clientId = $verification->status() === ClientVerificationStatus::CLIENT
    ? $verification->client()?->id()
    : null;

$leadRequest = new CreateLeadRequest(
    name: getenv('HOMLITY_LEAD_NAME') ?: 'Contacto SDK',
    email: $email,
    description: 'Contacto creado desde el flujo integral del SDK.',
    requirements: new LeadRequirements(businessType: 'venta'),
    propertyId: $property->id(),
    clientId: $clientId,
);
$leadResult = $sdk->leads()->create($leadRequest);

$ticketMetadata = [
    'lead_id' => $leadResult->lead()->id(),
    'property_id' => $property->id(),
];
if ($clientId !== null) {
    $ticketMetadata['client_id'] = $clientId;
}

$ticket = $sdk->tickets()->create(new CreateTicketRequest(
    subject: 'Seguimiento lead #' . $leadResult->lead()->id(),
    description: sprintf(
        'Seguimiento del lead #%d para el inmueble %s.',
        $leadResult->lead()->id(),
        $property->code() ?? (string) $property->id(),
    ),
    metadata: $ticketMetadata,
    leads: [new TicketLeadReference(
        name: $leadRequest->name,
        email: $leadRequest->email,
    )],
));

printf(
    "Inmueble %d; verificacion %s; lead %d; ticket %d.\n",
    $property->id(),
    $verification->status()->value,
    $leadResult->lead()->id(),
    $ticket->id(),
);

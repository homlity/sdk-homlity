<?php

declare(strict_types=1);

namespace Homlity\Sdk\Api;

use Homlity\Sdk\Data\LeadCreationResult;
use Homlity\Sdk\Data\LeadCreationStatus;
use Homlity\Sdk\Data\LeadSnapshot;
use Homlity\Sdk\Exception\UnsupportedFeatureException;
use Homlity\Sdk\Request\CreateLeadRequest;
use Homlity\Sdk\Support\ResponseData;

final class LeadsApi extends BaseApi
{
    /**
     * Creates a lead and, when requested, relates it to a tenant-owned property
     * and/or client using the backend's dedicated endpoints.
     *
     * Idempotency keys are deliberately rejected until the backend contract
     * persists and replays them; silently sending an ignored header would make
     * retries look safe when they are not.
     */
    public function create(CreateLeadRequest $request, ?string $idempotencyKey = null): LeadCreationResult
    {
        if ($idempotencyKey !== null) {
            throw new UnsupportedFeatureException(
                'Lead idempotency is not supported by the current Homlity backend contract.'
            );
        }

        $path = $request->propertyId === null
            ? '/v1/leads'
            : '/sistema/inmuebles/' . $request->propertyId . '/leads';

        $creationResponse = $this->send('POST', $path, ['json' => $request->toPayload()]);
        $leadData = ResponseData::object($creationResponse);
        $lead = LeadSnapshot::fromArray($leadData);
        $raw = is_array($creationResponse) ? $creationResponse : [];

        if ($request->clientId !== null) {
            $relationResponse = $this->send(
                'POST',
                '/v1/leads/' . $lead->id() . '/attach-client',
                ['json' => ['cliente_id' => $request->clientId]],
            );
            $relatedData = ResponseData::object($relationResponse);

            // The property-specific response contains a direct `inmueble`
            // summary while the follow-up response keeps it in requerimiento.
            if (!isset($relatedData['inmueble']) && isset($leadData['inmueble'])) {
                $relatedData['inmueble'] = $leadData['inmueble'];
            }

            $lead = LeadSnapshot::fromArray($relatedData);
            $raw['client_relation'] = $relationResponse;
        }

        return new LeadCreationResult($lead, LeadCreationStatus::CREATED, $raw);
    }
}

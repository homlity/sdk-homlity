<?php

declare(strict_types=1);

namespace Homlity\Sdk\Api;

use Homlity\Sdk\Data\ClientMatch;
use Homlity\Sdk\Data\ClientVerificationResult;
use Homlity\Sdk\Data\PaginatedResult;
use Homlity\Sdk\Support\DocumentNormalizer;

final class ClientsApi extends BaseApi
{
    /**
     * GET /client/
     */
    public function all(): mixed
    {
        return $this->send('GET', '/client/');
    }

    /**
     * GET /client/{client_id}
     */
    public function get(string $clientId): mixed
    {
        return $this->send('GET', '/client/' . $this->encodePath($clientId));
    }

    /**
     * GET /client/{client_id}/agent
     */
    public function agents(string $clientId): mixed
    {
        return $this->send('GET', '/client/' . $this->encodePath($clientId) . '/agent');
    }

    /**
     * Retorna el `id` del único agente asociado a un cliente.
     *
     * Si el cliente tiene exactamente un agente, devuelve su `id` (entero).
     * Si tiene cero o más de uno lanza una excepción para que el llamador
     * especifique el agente explícitamente.
     *
     * @throws \RuntimeException si el cliente no tiene agentes o tiene más de uno.
     */
    public function resolveSingleAgentId(string $clientId): int
    {
        $data = $this->agents($clientId);

        // La respuesta es un array directo de agentes (AgentResponse200 schema)
        $agents = is_array($data) ? array_values($data) : [];

        if (count($agents) === 0) {
            throw new \RuntimeException(sprintf(
                'Client "%s" has no agents. Assign at least one agent before publishing.',
                $clientId,
            ));
        }

        if (count($agents) > 1) {
            $ids = array_map(static fn ($a) => is_array($a) ? ($a['id'] ?? '?') : '?', $agents);
            throw new \RuntimeException(sprintf(
                'Client "%s" has %d agents (%s). Provide the agent id explicitly.',
                $clientId,
                count($agents),
                implode(', ', $ids),
            ));
        }

        $agent = $agents[0];
        $id = is_array($agent) ? ($agent['id'] ?? null) : null;

        if ($id === null) {
            throw new \RuntimeException(sprintf(
                'Agent response for client "%s" is missing the `id` field.',
                $clientId,
            ));
        }

        return (int) $id;
    }

    /**
     * Verifies a document using the backend's server-side client search. Only
     * exact document matches are retained from the scoped search results.
     *
     * @param int|string|null $documentType Numeric catalog ID, abbreviation or name.
     */
    public function verifyDocument(string $document, int|string|null $documentType = null): ClientVerificationResult
    {
        $normalized = DocumentNormalizer::normalize($document);
        if ($normalized === '' || strlen($normalized) > 100) {
            return ClientVerificationResult::invalid();
        }
        if ((is_int($documentType) && $documentType <= 0)
            || (is_string($documentType) && trim($documentType) === '')) {
            return ClientVerificationResult::invalid();
        }

        $matches = [];
        $page = 1;

        do {
            $response = $this->send('GET', '/v1/clients/', [
                'query' => ['q' => $normalized, 'page' => $page, 'per_page' => 100],
            ]);
            $result = PaginatedResult::fromApiResponse($response, static fn (array $item): array => $item);

            foreach ($result->items() as $item) {
                $candidate = DocumentNormalizer::normalize((string) ($item['identification'] ?? ''));
                if ($candidate !== $normalized || !$this->matchesDocumentType($item, $documentType)) {
                    continue;
                }

                $matches[] = ClientMatch::fromArray($item);
            }

            $page++;
        } while ($page <= $result->metadata()->lastPage());

        return ClientVerificationResult::fromMatches($matches);
    }

    /** @param array<string, mixed> $client */
    private function matchesDocumentType(array $client, int|string|null $documentType): bool
    {
        if ($documentType === null) {
            return true;
        }

        $type = isset($client['type_identification']) && is_array($client['type_identification'])
            ? $client['type_identification']
            : [];

        if (is_int($documentType)) {
            return isset($type['id']) && (int) $type['id'] === $documentType;
        }

        $expected = DocumentNormalizer::normalize($documentType);

        return $expected === DocumentNormalizer::normalize((string) ($type['abbreviation'] ?? ''))
            || $expected === DocumentNormalizer::normalize((string) ($type['name'] ?? ''));
    }
}

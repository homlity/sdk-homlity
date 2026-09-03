<?php

declare(strict_types=1);

namespace Homlity\Sdk\Api;

use Homlity\Sdk\Data\PaginatedResult;
use Homlity\Sdk\Data\TicketCategory;
use Homlity\Sdk\Data\TicketSnapshot;
use Homlity\Sdk\Exception\ValidationException;
use Homlity\Sdk\Filter\TicketFilters;
use Homlity\Sdk\Request\CreateTicketRequest;
use Homlity\Sdk\Support\ResponseData;

final class TicketsApi extends BaseApi
{
    public function create(CreateTicketRequest $request): TicketSnapshot
    {
        $response = $this->send('POST', '/v1/tickets', ['json' => $request->toPayload()]);

        return TicketSnapshot::fromArray(ResponseData::object($response));
    }

    /** @return PaginatedResult<TicketSnapshot> */
    public function list(?TicketFilters $filters = null): PaginatedResult
    {
        $filters ??= new TicketFilters();
        $response = $this->send('GET', '/v1/tickets', ['query' => $filters->toQuery()]);

        return PaginatedResult::fromApiResponse($response, TicketSnapshot::fromArray(...));
    }

    public function get(int $ticketId): TicketSnapshot
    {
        if ($ticketId <= 0) {
            throw new ValidationException('Ticket ID must be positive.');
        }

        $response = $this->send('GET', '/v1/tickets/' . $ticketId);

        return TicketSnapshot::fromArray(ResponseData::object($response));
    }

    /** @return list<TicketCategory> */
    public function categories(): array
    {
        $response = $this->send('GET', '/v1/tickets/categories');
        $categories = [];

        foreach (ResponseData::list($response) as $item) {
            if (!is_array($item)) {
                throw new ValidationException('Ticket category data items must be objects.');
            }

            $categories[] = TicketCategory::fromArray($item);
        }

        return $categories;
    }
}

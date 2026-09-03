<?php

declare(strict_types=1);

namespace Homlity\Sdk\Request;

use Homlity\Sdk\Exception\ValidationException;

final class CreateTicketRequest
{
    /**
     * @param list<string> $recipients
     * @param array<string, mixed> $metadata
     * @param list<TicketLeadReference> $leads
     */
    public function __construct(
        public readonly string $subject,
        public readonly string $description,
        public readonly ?int $categoryId = null,
        public readonly array $recipients = [],
        public readonly array $metadata = [],
        public readonly array $leads = [],
    ) {
        if (trim($this->subject) === '') {
            throw new ValidationException('Ticket subject is required.');
        }
        if (trim($this->description) === '') {
            throw new ValidationException('Ticket description is required.');
        }
        if ($this->categoryId !== null && $this->categoryId <= 0) {
            throw new ValidationException('Ticket category ID must be positive.');
        }
        foreach ($this->recipients as $recipient) {
            if (!is_string($recipient) || trim($recipient) === '') {
                throw new ValidationException('Ticket recipients must be non-empty strings.');
            }
        }
        foreach ($this->leads as $lead) {
            if (!$lead instanceof TicketLeadReference) {
                throw new ValidationException('Ticket leads must be TicketLeadReference instances.');
            }
        }
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        $payload = [
            'data' => array_filter([
                'subject' => trim($this->subject),
                'body' => trim($this->description),
                'params' => $this->metadata === [] ? null : $this->metadata,
                'recipients' => $this->recipients === [] ? null : array_values($this->recipients),
            ], static fn ($value) => $value !== null),
            'type' => $this->categoryId === null ? null : ['id' => $this->categoryId],
            'leads' => $this->leads === []
                ? null
                : array_map(static fn (TicketLeadReference $lead) => $lead->toArray(), $this->leads),
        ];

        return array_filter($payload, static fn ($value) => $value !== null);
    }
}

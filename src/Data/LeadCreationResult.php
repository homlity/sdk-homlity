<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

final class LeadCreationResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        private readonly LeadSnapshot $lead,
        private readonly LeadCreationStatus $status,
        private readonly array $raw,
    ) {
    }

    public function lead(): LeadSnapshot
    {
        return $this->lead;
    }

    public function status(): LeadCreationStatus
    {
        return $this->status;
    }

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->raw;
    }
}

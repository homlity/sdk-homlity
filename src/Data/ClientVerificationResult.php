<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

final class ClientVerificationResult
{
    /** @param list<ClientMatch> $matches */
    private function __construct(
        private readonly ClientVerificationStatus $status,
        private readonly array $matches,
    ) {
    }

    public static function invalid(): self
    {
        return new self(ClientVerificationStatus::INVALID_DOCUMENT, []);
    }

    /** @param list<ClientMatch> $matches */
    public static function fromMatches(array $matches): self
    {
        $status = match (count($matches)) {
            0 => ClientVerificationStatus::NOT_CLIENT,
            1 => ClientVerificationStatus::CLIENT,
            default => ClientVerificationStatus::MULTIPLE_MATCHES,
        };

        return new self($status, $matches);
    }

    public function status(): ClientVerificationStatus
    {
        return $this->status;
    }

    public function isClient(): bool
    {
        return $this->status === ClientVerificationStatus::CLIENT;
    }

    /** @return list<ClientMatch> */
    public function matches(): array
    {
        return $this->matches;
    }

    public function client(): ?ClientMatch
    {
        return count($this->matches) === 1 ? $this->matches[0] : null;
    }
}

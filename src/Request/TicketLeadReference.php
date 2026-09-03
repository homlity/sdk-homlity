<?php

declare(strict_types=1);

namespace Homlity\Sdk\Request;

use Homlity\Sdk\Exception\ValidationException;

final class TicketLeadReference
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
    ) {
        if (trim($this->name) === '') {
            throw new ValidationException('Ticket lead name is required.');
        }
        if (($this->phone === null || trim($this->phone) === '') && ($this->email === null || trim($this->email) === '')) {
            throw new ValidationException('Ticket lead requires a phone or email.');
        }
        if ($this->email !== null && trim($this->email) !== '' && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException('Ticket lead email is invalid.');
        }
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        $data = [
            'name' => trim($this->name),
            'tel' => $this->phone === null ? null : trim($this->phone),
            'email' => $this->email === null ? null : strtolower(trim($this->email)),
        ];

        return array_filter($data, static fn ($value) => $value !== null && $value !== '');
    }
}

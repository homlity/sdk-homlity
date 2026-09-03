<?php

declare(strict_types=1);

namespace Homlity\Sdk\Request;

use Homlity\Sdk\Exception\ValidationException;

final class CreateLeadRequest
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?int $adviserId = null,
        public readonly ?int $statusId = null,
        public readonly ?int $priorityId = null,
        public readonly ?int $sourceId = null,
        public readonly ?string $description = null,
        public readonly ?int $contactTypeId = null,
        public readonly ?int $stageId = null,
        public readonly ?LeadRequirements $requirements = null,
        public readonly ?int $propertyId = null,
        public readonly ?int $clientId = null,
    ) {
        if (trim($this->name) === '') {
            throw new ValidationException('Lead name is required.');
        }
        if (strlen(trim($this->name)) > 255) {
            throw new ValidationException('Lead name cannot exceed 255 characters.');
        }
        if (($this->phone === null || trim($this->phone) === '') && ($this->email === null || trim($this->email) === '')) {
            throw new ValidationException('Lead requires a phone or email.');
        }
        if ($this->phone !== null && strlen(trim($this->phone)) > 50) {
            throw new ValidationException('Lead phone cannot exceed 50 characters.');
        }
        if ($this->email !== null && strlen(trim($this->email)) > 255) {
            throw new ValidationException('Lead email cannot exceed 255 characters.');
        }
        if ($this->email !== null && trim($this->email) !== '' && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException('Lead email is invalid.');
        }
        if ($this->description !== null && strlen(trim($this->description)) > 10000) {
            throw new ValidationException('Lead description cannot exceed 10000 characters.');
        }
        foreach ([$this->adviserId, $this->statusId, $this->priorityId, $this->sourceId, $this->contactTypeId, $this->stageId, $this->propertyId, $this->clientId] as $id) {
            if ($id !== null && $id <= 0) {
                throw new ValidationException('Lead relation IDs must be positive integers.');
            }
        }
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return array_filter([
            'nombre' => trim($this->name),
            'celular' => $this->clean($this->phone),
            'correo' => $this->email === null ? null : strtolower(trim($this->email)),
            'id_asesor' => $this->adviserId,
            'id_estado' => $this->statusId,
            'urgencia' => $this->priorityId,
            'origen' => $this->sourceId,
            'detalle' => $this->clean($this->description),
            'tipo_contacto' => $this->contactTypeId,
            'stage_id' => $this->stageId,
            'requerimiento' => $this->requirements?->toArray(),
        ], static fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    private function clean(?string $value): ?string
    {
        return $value === null || trim($value) === '' ? null : trim($value);
    }
}

<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk\Homlity\Schema;

final class PropertyPayloadValidator
{
    /**
     * @param array<int, array<string, mixed>> $properties
     */
    public function validateUpsertPayload(array $properties): void
    {
        if ($properties === []) {
            throw new \InvalidArgumentException('At least one property payload is required.');
        }

        foreach ($properties as $index => $property) {
            foreach (['id', 'code', 'status', 'operation', 'type', 'category', 'media'] as $field) {
                if (!array_key_exists($field, $property)) {
                    throw new \InvalidArgumentException(sprintf('Missing required field `%s` in property payload at index %d.', $field, $index));
                }
            }

            if (!is_array($property['media'])) {
                throw new \InvalidArgumentException(sprintf('Field `media` at index %d must be an object/array.', $index));
            }

            if (array_key_exists('broshure', $property['media']) && !array_key_exists('brochure', $property['media'])) {
                throw new \InvalidArgumentException(sprintf('Use `media.brochure` instead of `media.broshure` at index %d.', $index));
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $payload
     */
    public function validateWebhookPayload(array $payload): void
    {
        if ($payload === []) {
            throw new \InvalidArgumentException('Webhook payload is required.');
        }

        foreach ($payload as $index => $item) {
            if (!array_key_exists('event', $item) || !array_key_exists('property_id', $item)) {
                throw new \InvalidArgumentException(sprintf('Webhook payload at index %d must include `event` and `property_id`.', $index));
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

/** @template T */
final class PaginatedResult
{
    /**
     * @param list<T> $items
     * @param array<string, mixed> $raw
     */
    private function __construct(
        private readonly array $items,
        private readonly PaginationMetadata $metadata,
        private readonly array $raw,
    ) {
    }

    /**
     * Supports both Homlity response shapes:
     * `{data: [...], meta: {...}}` and `{data: {data: [...], ...pagination}}`.
     *
     * @template M
     * @param mixed $response
     * @param callable(array<string, mixed>): M $mapper
     * @return self<M>
     */
    public static function fromApiResponse(mixed $response, callable $mapper): self
    {
        if (!is_array($response)) {
            return new self([], PaginationMetadata::fromArray([]), []);
        }

        $raw = $response;
        $outerMeta = isset($response['meta']) && is_array($response['meta']) ? $response['meta'] : [];
        $payload = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;
        $pagination = $outerMeta;

        if (array_is_list($payload)) {
            $rawItems = $payload;
        } elseif (isset($payload['data']) && is_array($payload['data'])) {
            $rawItems = $payload['data'];
            $pagination = array_merge($payload, $outerMeta);
        } elseif (isset($payload['results']) && is_array($payload['results'])) {
            $rawItems = $payload['results'];
            $pagination = array_merge($payload, $outerMeta);
        } else {
            $rawItems = [];
        }

        $items = [];
        foreach ($rawItems as $item) {
            if (is_array($item)) {
                $items[] = $mapper($item);
            }
        }

        /** @var list<M> $items */
        return new self($items, PaginationMetadata::fromArray($pagination, count($items)), $raw);
    }

    /** @return list<T> */
    public function items(): array
    {
        return $this->items;
    }

    public function metadata(): PaginationMetadata
    {
        return $this->metadata;
    }

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->raw;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}

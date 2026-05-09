<?php

declare(strict_types=1);

namespace Homlity\Sdk\Homlity\Api;

final class AnalyticsApi extends BaseApi
{
    /**
     * @param array{
     *   range?: 1|7|15|30|60|90|180|365,
     *   from?: string,
     *   to?: string,
     *   advisor_id?: int|string,
     *   property_id?: int|string,
     *   external_id?: int|string,
     *   limit?: int
     * } $filters
     */
    public function report(array $filters = []): mixed
    {
        $query = $this->normalizeFilters($filters);

        return $this->send('GET', '/wp-json/homlity-sync/v1/analytics/report', ['query' => $query]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, scalar>
     */
    private function normalizeFilters(array $filters): array
    {
        $query = [];

        if (isset($filters['range'])) {
            $range = (int) $filters['range'];
            $allowed = [1, 7, 15, 30, 60, 90, 180, 365];
            if (!in_array($range, $allowed, true)) {
                throw new \InvalidArgumentException('Invalid range. Allowed values: 1, 7, 15, 30, 60, 90, 180, 365.');
            }
            $query['range'] = $range;
        }

        if (isset($filters['from'])) {
            $this->assertValidDate((string) $filters['from'], 'from');
            $query['from'] = (string) $filters['from'];
        }

        if (isset($filters['to'])) {
            $this->assertValidDate((string) $filters['to'], 'to');
            $query['to'] = (string) $filters['to'];
        }

        if (isset($filters['advisor_id']) && $filters['advisor_id'] !== '') {
            $query['advisor_id'] = (string) $filters['advisor_id'];
        }

        if (isset($filters['property_id']) && $filters['property_id'] !== '') {
            $query['property_id'] = (string) $filters['property_id'];
        }

        if (isset($filters['external_id']) && $filters['external_id'] !== '') {
            $query['external_id'] = (string) $filters['external_id'];
        }

        if (isset($filters['limit'])) {
            $limit = (int) $filters['limit'];
            if ($limit < 1 || $limit > 50) {
                throw new \InvalidArgumentException('Invalid limit. Allowed range: 1-50.');
            }
            $query['limit'] = $limit;
        }

        return $query;
    }

    private function assertValidDate(string $date, string $field): void
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($dt === false || $dt->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException(sprintf('Invalid %s date format. Expected YYYY-MM-DD.', $field));
        }
    }
}

<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk\Homlity\Schema;

final class PropertyPayloadNormalizer
{
    /**
     * @param array<string, mixed> $property
     * @return array<string, mixed>
     */
    public function normalize(array $property): array
    {
        $property['operation'] = $this->slugify((string) ($property['operation'] ?? ''));
        $property['type'] = $this->slugify((string) ($property['type'] ?? ''));
        $property['category'] = $this->slugify((string) ($property['category'] ?? ''));

        foreach (['country', 'state', 'city', 'neighborhood'] as $locationField) {
            if (array_key_exists($locationField, $property)) {
                $property[$locationField] = $this->slugify((string) $property[$locationField]);
            }
        }

        $features = $property['features'] ?? [];
        if (is_array($features)) {
            $property['features'] = array_values(array_map(fn (mixed $feature): string => $this->slugify((string) $feature), $features));
        }

        $media = $property['media'] ?? [];
        if (is_array($media)) {
            $property['media'] = $this->normalizeMedia($media);
        }

        return $property;
    }

    /**
     * @param array<string, mixed> $media
     * @return array<string, mixed>
     */
    private function normalizeMedia(array $media): array
    {
        if (array_key_exists('broshure', $media) && !array_key_exists('brochure', $media)) {
            $media['brochure'] = $media['broshure'];
            unset($media['broshure']);
        }

        $normalizedVideos = [];
        $videos = $media['videos'] ?? [];
        if (is_array($videos) && $videos !== []) {
            $normalizedVideos[] = $videos[0];
        }
        $media['videos'] = $normalizedVideos;

        return $media;
    }

    private function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value));
        if ($value === '') {
            return '';
        }

        $value = strtr($value, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);

        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
        return trim($value, '-');
    }
}

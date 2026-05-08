<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk\Tests\Homlity;

use Fincaraiz\Sdk\Homlity\Schema\PropertyPayloadNormalizer;
use PHPUnit\Framework\TestCase;

final class PropertyPayloadNormalizerTest extends TestCase
{
    public function testItNormalizesHomologationAndMedia(): void
    {
        $normalizer = new PropertyPayloadNormalizer();

        $normalized = $normalizer->normalize([
            'operation' => 'Venta',
            'type' => 'Casa Campestre',
            'category' => 'Residencial',
            'country' => 'Colombia',
            'state' => 'Cundinamarca',
            'city' => 'Bogotá',
            'neighborhood' => 'Chicó Norte',
            'features' => ['Balcón', 'Zona BBQ'],
            'media' => [
                'broshure' => ['url' => 'https://example.com/file.pdf'],
                'videos' => [
                    ['url' => 'https://example.com/v1'],
                    ['url' => 'https://example.com/v2'],
                ],
            ],
        ]);

        self::assertSame('venta', $normalized['operation']);
        self::assertSame('casa-campestre', $normalized['type']);
        self::assertSame('residencial', $normalized['category']);
        self::assertSame('bogota', $normalized['city']);
        self::assertSame(['balcon', 'zona-bbq'], $normalized['features']);
        self::assertArrayHasKey('brochure', $normalized['media']);
        self::assertArrayNotHasKey('broshure', $normalized['media']);
        self::assertCount(1, $normalized['media']['videos']);
    }
}

<?php

declare(strict_types=1);

namespace Fincaraiz\Sdk\Tests\Homlity;

use Fincaraiz\Sdk\Homlity\Schema\PropertyPayloadValidator;
use PHPUnit\Framework\TestCase;

final class PropertyPayloadValidatorTest extends TestCase
{
    public function testItAcceptsMinimumValidPayload(): void
    {
        $validator = new PropertyPayloadValidator();

        $validator->validateUpsertPayload([[
            'id' => '12345',
            'code' => 'EXT-12345',
            'status' => 'active',
            'operation' => 'venta',
            'type' => 'apartamento',
            'category' => 'residencial',
            'media' => ['photos' => []],
        ]]);

        self::assertTrue(true);
    }

    public function testItFailsWhenRequiredFieldIsMissing(): void
    {
        $validator = new PropertyPayloadValidator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field `code`');

        $validator->validateUpsertPayload([[
            'id' => '12345',
            'status' => 'active',
            'operation' => 'venta',
            'type' => 'apartamento',
            'category' => 'residencial',
            'media' => ['photos' => []],
        ]]);
    }
}

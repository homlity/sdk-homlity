<?php

declare(strict_types=1);

namespace Homlity\Sdk\Tests;

use Homlity\Sdk\Api\PropertiesApi;
use Homlity\Sdk\Config;
use Homlity\Sdk\Data\ClientVerificationStatus;
use Homlity\Sdk\Data\LeadCreationStatus;
use Homlity\Sdk\Exception\AuthenticationException;
use Homlity\Sdk\Exception\AuthorizationException;
use Homlity\Sdk\Exception\ConflictException;
use Homlity\Sdk\Exception\NotFoundException;
use Homlity\Sdk\Exception\RateLimitException;
use Homlity\Sdk\Exception\ServerException;
use Homlity\Sdk\Exception\UnsupportedFeatureException;
use Homlity\Sdk\Exception\ValidationException;
use Homlity\Sdk\Filter\PropertyFilters;
use Homlity\Sdk\Filter\TicketFilters;
use Homlity\Sdk\HomlityClient;
use Homlity\Sdk\Http\ApiResponse;
use Homlity\Sdk\Http\CurlHttpClient;
use Homlity\Sdk\Http\HttpClientInterface;
use Homlity\Sdk\Request\CreateLeadRequest;
use Homlity\Sdk\Request\CreateTicketRequest;
use Homlity\Sdk\Request\LeadRequirements;
use Homlity\Sdk\Request\TicketLeadReference;
use Homlity\Sdk\Support\DocumentNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TenantResourcesTest extends TestCase
{
    public function testTenantConfigUsesBearerWithoutChangingLegacyAuthentication(): void
    {
        $tenant = Config::forTenantApi('tenant-token');
        $legacy = new Config('legacy-key');

        self::assertSame(Config::AUTH_BEARER, $tenant->authMode());
        self::assertSame(['Authorization' => 'Bearer tenant-token'], $tenant->authenticationHeaders());
        self::assertSame([
            'apikey' => 'legacy-key',
            'X-API-KEY' => 'legacy-key',
        ], $legacy->authenticationHeaders());
    }

    public function testCurlTransportAppliesTheConfiguredAuthenticationCentrally(): void
    {
        $transport = new CurlHttpClient(Config::forTenantApi('tenant-token'));
        $method = new \ReflectionMethod($transport, 'buildHeaders');
        $method->setAccessible(true);

        /** @var list<string> $headers */
        $headers = $method->invoke($transport, [], false);

        self::assertContains('Authorization: Bearer tenant-token', $headers);
        self::assertNotContains('apikey: tenant-token', $headers);
    }

    public function testPropertyFiltersSerializeEverySupportedFilterAndRemoveNulls(): void
    {
        $filters = new PropertyFilters(
            search: 'Casa central',
            statuses: [1, 2],
            propertyTypeIds: [3, 4],
            businessTypeId: 5,
            cityId: 6,
            neighborhoodId: 7,
            adviserId: 8,
            stratum: 4,
            rooms: 3,
            bathrooms: 2,
            parkingSpaces: 1,
            rentPriceMin: 1000,
            rentPriceMax: 2000,
            salePriceMin: 3000,
            salePriceMax: 4000,
            builtAreaMin: 50,
            builtAreaMax: 90,
            tags: ['balcon' => true, 'nivel' => 2],
            origin: 'mobile',
            page: 2,
            perPage: 40,
        );

        self::assertSame([
            'q' => 'Casa central',
            'status' => [1, 2],
            'tipo_inmueble' => [3, 4],
            'tipo_gestion' => 5,
            'ciudad' => 6,
            'barrio' => 7,
            'asesor' => 8,
            'estrato' => 4,
            'n_cuartos' => 3,
            'n_banos' => 2,
            'n_parqueaderos' => 1,
            'arriendo_desde' => 1000.0,
            'arriendo_hasta' => 2000.0,
            'venta_desde' => 3000.0,
            'venta_hasta' => 4000.0,
            'area_desde' => 50.0,
            'area_hasta' => 90.0,
            'tags' => ['balcon' => true, 'nivel' => 2],
            'origin' => 'mobile',
            'page' => 2,
            'per_page' => 40,
        ], $filters->toQuery());

        self::assertSame(['page' => 1, 'per_page' => 20], (new PropertyFilters())->toQuery());
    }

    public function testPropertyFiltersRejectInvalidRangesAndPagination(): void
    {
        $this->expectException(ValidationException::class);
        new PropertyFilters(salePriceMin: 20, salePriceMax: 10);
    }

    public function testPropertySearchMapsItemsAndPaginationAndHandlesNoResults(): void
    {
        $http = new TenantRecordingHttpClient([
            self::jsonResponse(200, [
                'status' => 'success',
                'data' => [['id' => 10, 'code' => 'H-10']],
                'meta' => ['total' => 1, 'per_page' => 20, 'current_page' => 1, 'last_page' => 1],
            ]),
            self::jsonResponse(200, [
                'status' => 'success',
                'data' => [],
                'meta' => ['total' => 0, 'per_page' => 20, 'current_page' => 1, 'last_page' => 1],
            ]),
        ]);
        $sdk = self::sdk($http);

        $result = $sdk->properties()->search(new PropertyFilters(search: 'H-10'));
        $empty = $sdk->properties()->list();

        self::assertSame(10, $result->items()[0]->id());
        self::assertSame('H-10', $result->items()[0]->code());
        self::assertSame(1, $result->metadata()->total());
        self::assertTrue($empty->isEmpty());
        self::assertSame('/v1/propertys', $http->calls()[0]['path']);
        self::assertSame('H-10', $http->calls()[0]['options']['query']['q']);
    }

    public function testPropertyCanBeFetchedByCodeUsingTenantSafeEndpoint(): void
    {
        $http = new TenantRecordingHttpClient([
            self::jsonResponse(200, ['status' => 'success', 'data' => ['id' => 7, 'code' => 'A/7']]),
        ]);

        $property = self::sdk($http)->properties()->getByCode('A/7');

        self::assertSame(7, $property->id());
        self::assertSame('/v1/integrations/properties/A%2F7', $http->calls()[0]['path']);
    }

    public function testTicketCreateListAndGetUseDocumentedContracts(): void
    {
        $http = new TenantRecordingHttpClient([
            self::jsonResponse(200, ['status' => 'success', 'data' => ['id' => 91]]),
            self::jsonResponse(200, [
                'status' => 'success',
                'data' => [
                    'current_page' => 2,
                    'data' => [['id' => 91, 'titulo' => 'Visita']],
                    'last_page' => 3,
                    'per_page' => 10,
                    'total' => 21,
                ],
            ]),
            self::jsonResponse(200, ['status' => 'success', 'data' => ['id' => 91, 'titulo' => 'Visita']]),
        ]);
        $sdk = self::sdk($http);
        $request = new CreateTicketRequest(
            subject: 'Visita',
            description: 'Coordinar visita',
            categoryId: 3,
            recipients: ['Asesor <asesor@example.com>'],
            metadata: ['source' => 'sdk'],
            leads: [new TicketLeadReference('Ana', email: 'ana@example.com')],
        );

        $created = $sdk->tickets()->create($request);
        $list = $sdk->tickets()->list(new TicketFilters(
            search: 'Visita',
            role: 'owner',
            finalized: true,
            categoryId: 3,
            priorityId: 2,
            propertyId: 8,
            deadlineFrom: '2026-09-01',
            deadlineTo: '2026-09-30',
            page: 2,
            perPage: 10,
        ));
        $detail = $sdk->tickets()->get(91);

        self::assertSame(91, $created->id());
        self::assertSame('Visita', $detail->subject());
        self::assertSame(3, $list->metadata()->lastPage());
        self::assertSame([
            'data' => [
                'subject' => 'Visita',
                'body' => 'Coordinar visita',
                'params' => ['source' => 'sdk'],
                'recipients' => ['Asesor <asesor@example.com>'],
            ],
            'type' => ['id' => 3],
            'leads' => [['name' => 'Ana', 'email' => 'ana@example.com']],
        ], $http->calls()[0]['options']['json']);
        self::assertSame('1', $http->calls()[1]['options']['query']['finalizado']);
        self::assertSame('/v1/tickets/91', $http->calls()[2]['path']);
    }

    public function testTicketRequiresSubjectAndDescription(): void
    {
        $this->expectException(ValidationException::class);
        new CreateTicketRequest('', 'Description');
    }

    public function testClientVerificationNormalizesDocumentAndReturnsMaskedMatch(): void
    {
        $http = new TenantRecordingHttpClient([
            self::jsonResponse(200, [
                'status' => 'success',
                'data' => [[
                    'id' => 44,
                    'full_name' => 'Ada Lovelace',
                    'identification' => 'ab-1234',
                    'type_identification' => ['id' => 2, 'abbreviation' => 'CE', 'name' => 'Extranjeria'],
                    'status' => ['id' => 1, 'name' => 'Activo'],
                    'rols' => [['id' => 1, 'name' => 'Arrendatario']],
                ]],
                'meta' => ['total' => 1, 'current_page' => 1, 'last_page' => 1, 'per_page' => 100],
            ]),
        ]);

        $result = self::sdk($http)->clients()->verifyDocument(' ab - 1234 ', 'ce');

        self::assertSame('AB-1234', DocumentNormalizer::normalize(' ab - 1234 '));
        self::assertSame(ClientVerificationStatus::CLIENT, $result->status());
        self::assertSame(44, $result->client()?->id());
        self::assertSame('****1234', $result->client()?->maskedDocument());
        self::assertSame('AB-1234', $http->calls()[0]['options']['query']['q']);
    }

    public function testClientVerificationDistinguishesNegativeInvalidAndMultipleResults(): void
    {
        $negativeHttp = new TenantRecordingHttpClient([
            self::jsonResponse(200, ['data' => [], 'meta' => ['last_page' => 1]]),
        ]);
        $multipleHttp = new TenantRecordingHttpClient([
            self::jsonResponse(200, [
                'data' => [
                    ['id' => 1, 'full_name' => 'A', 'identification' => '123', 'type_identification' => ['id' => 1]],
                    ['id' => 2, 'full_name' => 'B', 'identification' => '123', 'type_identification' => ['id' => 1]],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $negative = self::sdk($negativeHttp)->clients()->verifyDocument('999');
        $invalid = self::sdk(new TenantRecordingHttpClient([]))->clients()->verifyDocument('  ');
        $multiple = self::sdk($multipleHttp)->clients()->verifyDocument('123', 1);

        self::assertSame(ClientVerificationStatus::NOT_CLIENT, $negative->status());
        self::assertSame(ClientVerificationStatus::INVALID_DOCUMENT, $invalid->status());
        self::assertSame(ClientVerificationStatus::MULTIPLE_MATCHES, $multiple->status());
        self::assertCount(2, $multiple->matches());
    }

    public function testLeadCreationSupportsMinimumPayload(): void
    {
        $http = new TenantRecordingHttpClient([
            self::jsonResponse(201, ['status' => 'success', 'data' => ['id' => 5, 'nombre' => 'Ana']]),
        ]);

        $result = self::sdk($http)->leads()->create(new CreateLeadRequest(
            name: 'Ana',
            email: 'ANA@EXAMPLE.COM',
        ));

        self::assertSame(LeadCreationStatus::CREATED, $result->status());
        self::assertSame(5, $result->lead()->id());
        self::assertSame('/v1/leads', $http->calls()[0]['path']);
        self::assertSame(['nombre' => 'Ana', 'correo' => 'ana@example.com'], $http->calls()[0]['options']['json']);
    }

    public function testLeadCreationSupportsAllBackendFieldsAndTenantScopedRelations(): void
    {
        $http = new TenantRecordingHttpClient([
            self::jsonResponse(201, ['code' => 201, 'data' => [
                'id' => 6,
                'nombre' => 'Grace Hopper',
                'inmueble' => ['id' => 30, 'codigo' => 'P-30'],
            ]]),
            self::jsonResponse(200, ['status' => 'success', 'data' => [
                'id' => 6,
                'nombre' => 'Grace Hopper',
                'converted_cliente_id' => 40,
                'requerimiento' => [],
            ]]),
        ]);
        $requirements = new LeadRequirements(
            budgetMin: 100000,
            budgetMax: 200000,
            rooms: '3',
            bathrooms: '2',
            parkingSpaces: '1',
            areaMin: 60,
            areaMax: 100,
            stratum: '4',
            ageMin: 1,
            ageMax: 10,
            businessType: 'venta',
            propertyType: 'apartamento',
            propertyTypeIds: [2, 3],
            cityId: 11,
            neighborhoodId: 12,
        );
        $request = new CreateLeadRequest(
            name: 'Grace Hopper',
            phone: '+57 300 000 0000',
            email: 'grace@example.com',
            adviserId: 20,
            statusId: 1,
            priorityId: 2,
            sourceId: 3,
            description: 'Busca apartamento',
            contactTypeId: 4,
            stageId: 5,
            requirements: $requirements,
            propertyId: 30,
            clientId: 40,
        );

        $result = self::sdk($http)->leads()->create($request);
        $calls = $http->calls();

        self::assertSame('/sistema/inmuebles/30/leads', $calls[0]['path']);
        self::assertArrayNotHasKey('id_inmobiliaria', $calls[0]['options']['json']);
        self::assertSame('venta', $calls[0]['options']['json']['requerimiento']['tipo_negocio']);
        self::assertSame('/v1/leads/6/attach-client', $calls[1]['path']);
        self::assertSame(['cliente_id' => 40], $calls[1]['options']['json']);
        self::assertSame(40, $result->lead()->clientId());
        self::assertSame(30, $result->lead()->relatedProperty()['id'] ?? null);
    }

    public function testLeadIdempotencyIsNotPretendedWhenBackendDoesNotSupportIt(): void
    {
        $http = new TenantRecordingHttpClient([]);

        try {
            self::sdk($http)->leads()->create(
                new CreateLeadRequest('Ana', email: 'ana@example.com'),
                idempotencyKey: 'retry-1',
            );
            self::fail('Expected UnsupportedFeatureException.');
        } catch (UnsupportedFeatureException) {
            self::assertSame([], $http->calls());
        }
    }

    /** @return iterable<string, array{int, class-string<\Throwable>}> */
    public static function errorStatusProvider(): iterable
    {
        yield 'unauthenticated' => [401, AuthenticationException::class];
        yield 'forbidden' => [403, AuthorizationException::class];
        yield 'not found and cross-tenant concealment' => [404, NotFoundException::class];
        yield 'conflict or duplicate' => [409, ConflictException::class];
        yield 'validation' => [422, ValidationException::class];
        yield 'rate limit' => [429, RateLimitException::class];
        yield 'server' => [500, ServerException::class];
    }

    /** @param class-string<\Throwable> $exception */
    #[DataProvider('errorStatusProvider')]
    public function testHttpErrorsKeepStatusAndUseSpecificExceptions(int $status, string $exception): void
    {
        $http = new TenantRecordingHttpClient([
            self::jsonResponse($status, ['message' => 'Sensitive document 123456789']),
        ]);
        $api = new PropertiesApi($http);

        try {
            $api->list();
            self::fail('Expected API exception.');
        } catch (\Throwable $caught) {
            self::assertInstanceOf($exception, $caught);
            self::assertSame($status, $caught->statusCode());
            self::assertStringNotContainsString('123456789', $caught->getMessage());
        }
    }

    private static function sdk(TenantRecordingHttpClient $http): HomlityClient
    {
        return new HomlityClient(Config::forTenantApi('test-token'), $http);
    }

    /** @param array<string, mixed> $body */
    private static function jsonResponse(int $status, array $body): ApiResponse
    {
        return new ApiResponse($status, [], json_encode($body, JSON_THROW_ON_ERROR));
    }
}

final class TenantRecordingHttpClient implements HttpClientInterface
{
    /** @var list<ApiResponse> */
    private array $responses;

    /** @var list<array{method: string, path: string, options: array<string, mixed>}> */
    private array $calls = [];

    /** @param list<ApiResponse> $responses */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function request(string $method, string $path, array $options = []): ApiResponse
    {
        $this->calls[] = ['method' => $method, 'path' => $path, 'options' => $options];

        if ($this->responses === []) {
            throw new \LogicException('No response configured.');
        }

        return array_shift($this->responses);
    }

    /** @return list<array{method: string, path: string, options: array<string, mixed>}> */
    public function calls(): array
    {
        return $this->calls;
    }
}

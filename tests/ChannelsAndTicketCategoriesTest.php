<?php

declare(strict_types=1);

namespace Homlity\Sdk\Tests;

use Homlity\Sdk\Config;
use Homlity\Sdk\Data\Channel;
use Homlity\Sdk\Data\TicketCategory;
use Homlity\Sdk\Exception\AuthenticationException;
use Homlity\Sdk\Exception\ServerException;
use Homlity\Sdk\Exception\ValidationException;
use Homlity\Sdk\HomlityClient;
use Homlity\Sdk\Http\ApiResponse;
use Homlity\Sdk\Http\HttpClientInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ChannelsAndTicketCategoriesTest extends TestCase
{
    public function testChannelsUseTheTenantContractAndMapDtos(): void
    {
        $first = ['id' => 1, 'nombre' => 'Página web', 'active' => true];
        $second = ['id' => '15', 'nombre' => 'WhatsApp'];
        $http = new CatalogRecordingHttpClient([
            self::response(200, ['status' => 'success', 'data' => [$first, $second]]),
        ]);
        $sdk = self::sdk($http);

        $channels = $sdk->channels()->list();

        self::assertCount(2, $channels);
        self::assertContainsOnlyInstancesOf(Channel::class, $channels);
        self::assertSame(1, $channels[0]->id());
        self::assertSame('Página web', $channels[0]->name());
        self::assertSame($first, $channels[0]->raw());
        self::assertSame(15, $channels[1]->id());
        self::assertSame('GET', $http->calls()[0]['method']);
        self::assertSame('/v1/channels', $http->calls()[0]['path']);
        self::assertSame([], $http->calls()[0]['options']);
        self::assertSame($sdk->channels(), $sdk->channels());
    }

    public function testChannelsAcceptAnEmptyDataList(): void
    {
        $http = new CatalogRecordingHttpClient([
            self::response(200, ['status' => 'success', 'data' => []]),
        ]);

        self::assertSame([], self::sdk($http)->channels()->list());
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidIdProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'decimal' => ['1.5'];
        yield 'scientific notation' => ['1e2'];
        yield 'boolean' => [true];
        yield 'overflow' => [(string) PHP_INT_MAX . '0'];
    }

    #[DataProvider('invalidIdProvider')]
    public function testChannelsRejectInvalidIds(mixed $id): void
    {
        $http = new CatalogRecordingHttpClient([
            self::response(200, ['status' => 'success', 'data' => [['id' => $id, 'nombre' => 'Invalid']]]),
        ]);

        $this->expectException(ValidationException::class);

        self::sdk($http)->channels()->list();
    }

    public function testTicketCategoriesUseTheTicketApiAndMapNullableFields(): void
    {
        $first = [
            'id' => 1,
            'nombre' => 'Sin categoría',
            'descripcion' => null,
            'id_padre' => null,
            'color' => '#fff',
        ];
        $second = [
            'id' => '2',
            'nombre' => 'Arrendamientos',
            'descripcion' => 'Solicitudes de arrendamiento',
            'id_padre' => '1',
        ];
        $http = new CatalogRecordingHttpClient([
            self::response(200, ['status' => 'success', 'data' => [$first, $second]]),
        ]);
        $sdk = self::sdk($http);

        $categories = $sdk->tickets()->categories();

        self::assertCount(2, $categories);
        self::assertContainsOnlyInstancesOf(TicketCategory::class, $categories);
        self::assertSame(1, $categories[0]->id());
        self::assertSame('Sin categoría', $categories[0]->name());
        self::assertNull($categories[0]->description());
        self::assertNull($categories[0]->parentId());
        self::assertSame($first, $categories[0]->raw());
        self::assertSame('Solicitudes de arrendamiento', $categories[1]->description());
        self::assertSame(1, $categories[1]->parentId());
        self::assertSame('GET', $http->calls()[0]['method']);
        self::assertSame('/v1/tickets/categories', $http->calls()[0]['path']);
        self::assertSame([], $http->calls()[0]['options']);
    }

    public function testTicketCategoriesAcceptAnEmptyDataList(): void
    {
        $http = new CatalogRecordingHttpClient([
            self::response(200, ['status' => 'success', 'data' => []]),
        ]);

        self::assertSame([], self::sdk($http)->tickets()->categories());
    }

    #[DataProvider('invalidIdProvider')]
    public function testTicketCategoriesRejectInvalidIds(mixed $id): void
    {
        $http = new CatalogRecordingHttpClient([
            self::response(200, ['status' => 'success', 'data' => [[
                'id' => $id,
                'nombre' => 'Invalid',
                'descripcion' => null,
                'id_padre' => null,
            ]]]),
        ]);

        $this->expectException(ValidationException::class);

        self::sdk($http)->tickets()->categories();
    }

    public function testTicketCategoriesRejectInvalidParentIds(): void
    {
        $http = new CatalogRecordingHttpClient([
            self::response(200, ['status' => 'success', 'data' => [[
                'id' => 1,
                'nombre' => 'Invalid parent',
                'descripcion' => null,
                'id_padre' => 0,
            ]]]),
        ]);

        $this->expectException(ValidationException::class);

        self::sdk($http)->tickets()->categories();
    }

    /** @return iterable<string, array{string, int, class-string<\Throwable>}> */
    public static function httpErrorProvider(): iterable
    {
        yield 'channels unauthenticated' => ['channels', 401, AuthenticationException::class];
        yield 'ticket categories server error' => ['categories', 500, ServerException::class];
    }

    /** @param class-string<\Throwable> $expectedException */
    #[DataProvider('httpErrorProvider')]
    public function testHttpErrorsUseExistingTranslationWithoutLeakingTheToken(
        string $resource,
        int $status,
        string $expectedException,
    ): void {
        $token = 'catalog-secret-token';
        $http = new CatalogRecordingHttpClient([
            self::response($status, ['message' => $token]),
        ]);
        $sdk = new HomlityClient(Config::forTenantApi($token), $http);

        try {
            $resource === 'channels'
                ? $sdk->channels()->list()
                : $sdk->tickets()->categories();
            self::fail('Expected API exception.');
        } catch (\Throwable $exception) {
            self::assertInstanceOf($expectedException, $exception);
            self::assertSame($status, $exception->statusCode());
            self::assertStringNotContainsString($token, $exception->getMessage());
        }
    }

    public function testTenantConfigGeneratesBearerAuthenticationForCatalogCalls(): void
    {
        $config = Config::forTenantApi('catalog-token');

        self::assertSame(['Authorization' => 'Bearer catalog-token'], $config->authenticationHeaders());
    }

    private static function sdk(CatalogRecordingHttpClient $http): HomlityClient
    {
        return new HomlityClient(Config::forTenantApi('catalog-token'), $http);
    }

    /** @param array<string, mixed> $body */
    private static function response(int $status, array $body): ApiResponse
    {
        return new ApiResponse($status, [], json_encode($body, JSON_THROW_ON_ERROR));
    }
}

final class CatalogRecordingHttpClient implements HttpClientInterface
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

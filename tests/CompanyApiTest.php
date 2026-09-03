<?php

declare(strict_types=1);

namespace Homlity\Sdk\Tests;

use Homlity\Sdk\Config;
use Homlity\Sdk\Exception\AuthenticationException;
use Homlity\Sdk\Exception\AuthorizationException;
use Homlity\Sdk\Exception\NotFoundException;
use Homlity\Sdk\Exception\ServerException;
use Homlity\Sdk\Exception\ValidationException;
use Homlity\Sdk\HomlityClient;
use Homlity\Sdk\Http\ApiResponse;
use Homlity\Sdk\Http\HttpClientInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CompanyApiTest extends TestCase
{
    public function testProfileUsesTheTenantEndpointAndParsesTheCompleteContract(): void
    {
        $http = new CompanyRecordingHttpClient([
            self::response(200, [
                'status' => 'success',
                'data' => [
                    'id' => 123,
                    'name' => 'Inmobiliaria Ejemplo',
                    'phone' => '+57 300 000 0000',
                    'email' => 'contacto@example.com',
                    'address' => 'Calle 1 # 2-3',
                    'city' => 'Medellín',
                    'business_hours' => ['lunes' => ['08:00', '18:00']],
                    'public_url' => 'https://example.com',
                ],
            ]),
        ]);

        $profile = self::sdk($http)->company()->profile();

        self::assertSame('GET', $http->calls()[0]['method']);
        self::assertSame('/v1/inmobiliaria/profile', $http->calls()[0]['path']);
        self::assertSame(123, $profile->id());
        self::assertSame('Inmobiliaria Ejemplo', $profile->name());
        self::assertSame('+57 300 000 0000', $profile->phone());
        self::assertSame('contacto@example.com', $profile->email());
        self::assertSame('Calle 1 # 2-3', $profile->address());
        self::assertSame('Medellín', $profile->city());
        self::assertSame(['lunes' => ['08:00', '18:00']], $profile->businessHours());
        self::assertSame('https://example.com', $profile->publicUrl());
        self::assertSame(123, $profile->raw()['id']);
    }

    public function testProfilePreservesEmptyHoursAndMapsEmptyOptionalFieldsToNull(): void
    {
        $http = new CompanyRecordingHttpClient([
            self::response(200, ['data' => [
                'id' => 9,
                'name' => '',
                'phone' => null,
                'email' => null,
                'address' => null,
                'city' => null,
                'business_hours' => [],
                'public_url' => null,
            ]]),
        ]);

        $profile = self::sdk($http)->company()->profile();

        self::assertNull($profile->name());
        self::assertNull($profile->phone());
        self::assertNull($profile->email());
        self::assertNull($profile->address());
        self::assertNull($profile->city());
        self::assertSame([], $profile->businessHours());
        self::assertNull($profile->publicUrl());
    }

    /** @return iterable<string, array{int, class-string<\Throwable>}> */
    public static function errorStatusProvider(): iterable
    {
        yield 'unauthenticated' => [401, AuthenticationException::class];
        yield 'forbidden' => [403, AuthorizationException::class];
        yield 'not found' => [404, NotFoundException::class];
        yield 'validation' => [422, ValidationException::class];
        yield 'server error' => [500, ServerException::class];
        yield 'upstream unavailable' => [503, ServerException::class];
    }

    /** @param class-string<\Throwable> $expectedException */
    #[DataProvider('errorStatusProvider')]
    public function testProfileUsesTheExistingErrorTranslation(int $status, string $expectedException): void
    {
        $http = new CompanyRecordingHttpClient([
            self::response($status, ['message' => 'Request rejected']),
        ]);

        $this->expectException($expectedException);

        self::sdk($http)->company()->profile();
    }

    public function testTokenIsAbsentFromTheDtoExceptionsAndDebugOutput(): void
    {
        $token = 'tenant-secret-that-must-not-leak';
        $http = new CompanyRecordingHttpClient([
            self::response(200, ['data' => [
                'id' => 7,
                'name' => 'Segura',
                'access_token' => $token,
                'business_hours' => [],
            ]]),
        ]);
        $sdk = new HomlityClient(Config::forTenantApi($token), $http);

        $profile = $sdk->company()->profile();
        ob_start();
        var_dump($sdk, $profile);
        $debugOutput = (string) ob_get_clean();

        self::assertArrayNotHasKey('access_token', $profile->raw());
        self::assertStringNotContainsString($token, $debugOutput);

        $failingSdk = new HomlityClient(Config::forTenantApi($token), new CompanyRecordingHttpClient([
            self::response(401, ['message' => 'Unauthenticated.']),
        ]));

        try {
            $failingSdk->company()->profile();
            self::fail('Expected authentication exception.');
        } catch (AuthenticationException $exception) {
            self::assertStringNotContainsString($token, $exception->getMessage());
        }
    }

    private static function sdk(CompanyRecordingHttpClient $http): HomlityClient
    {
        return new HomlityClient(Config::forTenantApi('tenant-token'), $http);
    }

    /** @param array<string, mixed> $body */
    private static function response(int $status, array $body): ApiResponse
    {
        return new ApiResponse($status, [], json_encode($body, JSON_THROW_ON_ERROR));
    }
}

final class CompanyRecordingHttpClient implements HttpClientInterface
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

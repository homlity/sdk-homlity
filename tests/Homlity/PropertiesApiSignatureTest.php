<?php

declare(strict_types=1);

namespace Homlity\Sdk\Tests\Homlity;

use Homlity\Sdk\Homlity\Api\PropertiesApi;
use Homlity\Sdk\Homlity\Config;
use Homlity\Sdk\Homlity\Exception\ApiException;
use Homlity\Sdk\Homlity\Http\ApiResponse;
use Homlity\Sdk\Homlity\Http\HttpClientInterface;
use Homlity\Sdk\Homlity\Schema\PropertyPayloadNormalizer;
use Homlity\Sdk\Homlity\Schema\PropertyPayloadValidator;
use PHPUnit\Framework\TestCase;

final class PropertiesApiSignatureTest extends TestCase
{
    public function testPushSendsSignedHeadersUsingExactBody(): void
    {
        $http = new RecordingHttpClient([new ApiResponse(200, [], '{"ok":true}')]);
        $api = $this->buildApi($http, signPropertyRequests: true);

        $api->push([
            'id' => '123',
            'code' => 'EXT-123',
            'status' => 'active',
            'operation' => 'venta',
            'type' => 'apartamento',
            'category' => 'residencial',
            'media' => ['photos' => []],
        ]);

        $call = $http->calls()[0];
        self::assertSame('/wp-json/homlity-sync/v1/properties', $call['path']);

        $body = (string) ($call['options']['body'] ?? '');
        $headers = $call['options']['headers'] ?? [];

        self::assertSame('my-api-key', $headers['X-Homlity-Token']);
        self::assertSame('sha256=' . hash_hmac('sha256', $body, 'my-api-key'), $headers['X-Homlity-Signature']);
    }

    public function testDeactivateSendsSignedHeadersWithEmptyBodyWhenNoPayload(): void
    {
        $http = new RecordingHttpClient([new ApiResponse(200, [], '{"ok":true}')]);
        $api = $this->buildApi($http, signPropertyRequests: true);

        $api->deactivate('abc-1');

        $call = $http->calls()[0];
        self::assertSame('/wp-json/homlity-sync/v1/properties/abc-1/deactivate', $call['path']);
        self::assertSame('', $call['options']['body'] ?? null);
        self::assertSame(
            'sha256=' . hash_hmac('sha256', '', 'my-api-key'),
            $call['options']['headers']['X-Homlity-Signature']
        );
    }

    public function testItRetriesSignedWhenServerRequiresSignature(): void
    {
        $http = new RecordingHttpClient([
            new ApiResponse(401, [], '{"success":false,"message":"Firma requerida."}'),
            new ApiResponse(200, [], '{"ok":true}'),
        ]);
        $api = $this->buildApi($http, signPropertyRequests: false, retrySignedOnSignatureRequired: true);

        $api->deactivate('retry-me');

        $calls = $http->calls();
        self::assertCount(2, $calls);
        self::assertArrayNotHasKey('X-Homlity-Signature', $calls[0]['options']['headers']);
        self::assertArrayHasKey('X-Homlity-Signature', $calls[1]['options']['headers']);
    }

    public function testApiExceptionMessageIsClearForSignatureRequired(): void
    {
        $http = new RecordingHttpClient([
            new ApiResponse(401, [], '{"success":false,"message":"Firma requerida."}'),
        ]);
        $api = $this->buildApi($http, signPropertyRequests: false, retrySignedOnSignatureRequired: false);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('requires request signing');

        $api->deactivate('no-retry');
    }

    private function buildApi(
        HttpClientInterface $http,
        bool $signPropertyRequests,
        bool $retrySignedOnSignatureRequired = true
    ): PropertiesApi {
        $config = new Config(
            apiKey: 'my-api-key',
            baseUrl: 'https://example.com',
            signPropertyRequests: $signPropertyRequests,
            retrySignedOnSignatureRequired: $retrySignedOnSignatureRequired
        );

        return new PropertiesApi($http, $config, new PropertyPayloadValidator(), new PropertyPayloadNormalizer());
    }
}

final class RecordingHttpClient implements HttpClientInterface
{
    /** @var list<array{method:string,path:string,options:array}> */
    private array $calls = [];
    /** @var list<ApiResponse> */
    private array $responses;

    /** @param list<ApiResponse> $responses */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function request(string $method, string $path, array $options = []): ApiResponse
    {
        $this->calls[] = [
            'method' => $method,
            'path' => $path,
            'options' => $options,
        ];

        if ($this->responses === []) {
            throw new \LogicException('No response configured.');
        }

        return array_shift($this->responses);
    }

    /** @return list<array{method:string,path:string,options:array}> */
    public function calls(): array
    {
        return $this->calls;
    }
}

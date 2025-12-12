<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Tests\Unit\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Payment\MtlsHmac\Client\GatewayClient;
use Payment\MtlsHmac\Config\GatewayConfig;
use Payment\MtlsHmac\Exception\GatewayException;
use Payment\MtlsHmac\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;

final class GatewayClientTest extends TestCase
{
    private GatewayConfig $config;
    private Client $mockHttp;

    protected function setUp(): void
    {
        $this->config = new GatewayConfig(
            hmacSecret: 'test-secret',
            certPath: '/test/cert.pem',
            keyPath: '/test/key.pem',
            signatureHeader: 'X-Signature'
        );

        $this->mockHttp = $this->createMock(Client::class);
    }

    public function testConstructorWithDefaults(): void
    {
        $client = new GatewayClient($this->config);

        $this->assertInstanceOf(GatewayClient::class, $client);
    }

    public function testConstructorWithMockedHttp(): void
    {
        $client = new GatewayClient($this->config, $this->mockHttp);

        $this->assertInstanceOf(GatewayClient::class, $client);
    }

    public function testGetWithSuccessfulResponse(): void
    {
        $payload = ['transaction_id' => '12345', 'amount' => '99.99'];
        $responseBody = '{"status": "success"}';

        $response = new Response(200, [], $responseBody);

        $this->mockHttp
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://example.com/api', $this->callback(function ($options) {
                return isset($options['query']) && 
                       isset($options['headers']['X-Signature']) &&
                       $options['http_errors'] === false;
            }))
            ->willReturn($response);

        $client = new GatewayClient($this->config, $this->mockHttp);
        $result = $client->get('https://example.com/api', $payload);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame($responseBody, (string) $result->getBody());
    }

    public function testGetWithEmptyEndpoint(): void
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Endpoint URL must not be empty');

        $client = new GatewayClient($this->config, $this->mockHttp);
        $client->get('', ['test' => 'data']);
    }

    public function testGetWith4xxError(): void
    {
        $payload = ['test' => 'data'];

        $response = new Response(404, [], 'Not Found');

        $this->mockHttp
            ->method('request')
            ->willReturn($response);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unexpected HTTP status code: 404');

        $client = new GatewayClient($this->config, $this->mockHttp);
        $client->get('https://example.com/api', $payload);
    }

    public function testGetWith5xxError(): void
    {
        $payload = ['test' => 'data'];

        $response = new Response(500, [], 'Internal Server Error');

        $this->mockHttp
            ->method('request')
            ->willReturn($response);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unexpected HTTP status code: 500');

        $client = new GatewayClient($this->config, $this->mockHttp);
        $client->get('https://example.com/api', $payload);
    }

    /**
     * @dataProvider successStatusCodeProvider
     */
    public function testGetWithVariousSuccessStatusCodes(int $statusCode): void
    {
        $payload = ['test' => 'data'];

        $response = new Response($statusCode, [], 'Success response');

        $this->mockHttp
            ->method('request')
            ->willReturn($response);

        $client = new GatewayClient($this->config, $this->mockHttp);
        $result = $client->get('https://example.com/api', $payload);

        $this->assertSame($statusCode, $result->getStatusCode());
    }

    public static function successStatusCodeProvider(): array
    {
        return [
            '200 OK' => [200],
            '201 Created' => [201],
            '202 Accepted' => [202],
            '204 No Content' => [204],
            '299 Edge case' => [299],
        ];
    }

    public function testGetWithCustomSignatureHeader(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'test-secret',
            certPath: '/test/cert.pem',
            keyPath: '/test/key.pem',
            signatureHeader: 'Authorization'
        );

        $payload = ['test' => 'data'];

        $response = new Response(200, [], 'Success');

        $this->mockHttp
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://example.com/api', $this->callback(function ($options) {
                return isset($options['headers']['Authorization']);
            }))
            ->willReturn($response);

        $client = new GatewayClient($config, $this->mockHttp);
        $client->get('https://example.com/api', $payload);
    }

    public function testGetWithComplexPayload(): void
    {
        $payload = [
            'transaction_id' => '12345',
            'amount' => '99.99',
            'currency' => 'USD',
            'merchant_id' => 'MERCHANT_001',
            'timestamp' => '2023-01-01T00:00:00Z',
            'description' => 'Test payment with special chars: !@#$%^&*()',
        ];

        $response = new Response(200, [], '{"result": "processed"}');

        $this->mockHttp
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.example.com/payment', $this->callback(function ($options) use ($payload) {
                return $options['query'] === $payload;
            }))
            ->willReturn($response);

        $client = new GatewayClient($this->config, $this->mockHttp);
        $result = $client->get('https://api.example.com/payment', $payload);

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testGetFromEnvEndpointCallsGetWithEnvEndpoint(): void
    {
        // Mock environment variable
        putenv('GATEWAY_ENDPOINT=https://env.example.com/api');

        $payload = ['test' => 'env_data'];

        $response = new Response(200, [], 'Env response');

        $this->mockHttp
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://env.example.com/api', $this->anything())
            ->willReturn($response);

        $client = new GatewayClient($this->config, $this->mockHttp);
        $result = $client->getFromEnvEndpoint($payload);

        $this->assertSame(200, $result->getStatusCode());

        // Cleanup
        putenv('GATEWAY_ENDPOINT');
    }

    public function testGetFromEnvEndpointWithMissingEnv(): void
    {
        // Ensure env var is not set
        putenv('GATEWAY_ENDPOINT');

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Missing env var: GATEWAY_ENDPOINT');

        $client = new GatewayClient($this->config, $this->mockHttp);
        $client->getFromEnvEndpoint(['test' => 'data']);
    }

    public function testFromEnvStaticMethod(): void
    {
        // Set up required environment variables
        putenv('GATEWAY_HMAC_SECRET=env-secret');
        putenv('GATEWAY_CERT_PATH=/env/cert.pem');
        putenv('GATEWAY_KEY_PATH=/env/key.pem');

        $client = GatewayClient::fromEnv();

        $this->assertInstanceOf(GatewayClient::class, $client);

        // Cleanup
        putenv('GATEWAY_HMAC_SECRET');
        putenv('GATEWAY_CERT_PATH');
        putenv('GATEWAY_KEY_PATH');
    }

    public function testSignatureIsGeneratedCorrectly(): void
    {
        $payload = ['test' => 'signature'];
        $capturedOptions = null;

        $response = new Response(200, [], 'OK');

        $this->mockHttp
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://example.com/api', $this->callback(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            }))
            ->willReturn($response);

        $client = new GatewayClient($this->config, $this->mockHttp);
        $client->get('https://example.com/api', $payload);

        $this->assertArrayHasKey('headers', $capturedOptions);
        $this->assertArrayHasKey('X-Signature', $capturedOptions['headers']);
        $this->assertNotEmpty($capturedOptions['headers']['X-Signature']);
        
        // Signature should be a hex string
        $signature = $capturedOptions['headers']['X-Signature'];
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $signature);
    }
}
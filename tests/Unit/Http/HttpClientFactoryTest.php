<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Tests\Unit\Http;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Payment\MtlsHmac\Config\GatewayConfig;
use Payment\MtlsHmac\Http\HttpClientFactory;

final class HttpClientFactoryTest extends TestCase
{
    public function testCreateReturnsGuzzleClient(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem'
        );

        $client = HttpClientFactory::create($config);

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testCreateWithMinimalConfig(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/test/cert.pem',
            keyPath: '/test/key.pem'
        );

        $client = HttpClientFactory::create($config);

        // Verify the client was created successfully
        $this->assertInstanceOf(Client::class, $client);
        
        // Test that the client has the expected configuration through reflection
        $reflection = new \ReflectionClass($client);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $clientConfig = $configProperty->getValue($client);

        // Check that mTLS options were applied
        $this->assertArrayHasKey('cert', $clientConfig);
        $this->assertArrayHasKey('ssl_key', $clientConfig);
        $this->assertArrayHasKey('verify', $clientConfig);
        $this->assertArrayHasKey('timeout', $clientConfig);
        $this->assertArrayHasKey('connect_timeout', $clientConfig);
    }

    public function testCreateWithExtraOptions(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem'
        );

        $extra = [
            'base_uri' => 'https://example.com',
            'headers' => ['User-Agent' => 'Test-Client/1.0'],
            'debug' => true,
        ];

        $client = HttpClientFactory::create($config, $extra);

        $this->assertInstanceOf(Client::class, $client);

        // Verify extra options were merged
        $reflection = new \ReflectionClass($client);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $clientConfig = $configProperty->getValue($client);

        $this->assertArrayHasKey('base_uri', $clientConfig);
        $this->assertArrayHasKey('headers', $clientConfig);
        $this->assertArrayHasKey('debug', $clientConfig);
        $this->assertSame('https://example.com', (string) $clientConfig['base_uri']);
        $this->assertTrue($clientConfig['debug']);
    }

    public function testCreateWithExtraOptionsOverriding(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem',
            timeoutSeconds: 15
        );

        $extra = [
            'timeout' => 30, // Override default timeout
            'custom_option' => 'custom_value',
        ];

        $client = HttpClientFactory::create($config, $extra);

        $this->assertInstanceOf(Client::class, $client);

        // Verify extra options override config options
        $reflection = new \ReflectionClass($client);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $clientConfig = $configProperty->getValue($client);

        $this->assertSame(30, $clientConfig['timeout']); // Should be overridden
        $this->assertSame('custom_value', $clientConfig['custom_option']);
    }

    public function testCreateWithPassphrase(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem',
            keyPassphrase: 'test-passphrase'
        );

        $client = HttpClientFactory::create($config);

        $this->assertInstanceOf(Client::class, $client);

        $reflection = new \ReflectionClass($client);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $clientConfig = $configProperty->getValue($client);

        // Both cert and ssl_key should always have passphrase as second element
        $this->assertIsArray($clientConfig['cert']);
        $this->assertIsArray($clientConfig['ssl_key']);
        $this->assertCount(2, $clientConfig['cert']);
        $this->assertCount(2, $clientConfig['ssl_key']);
        $this->assertSame('test-passphrase', $clientConfig['cert'][1]);
        $this->assertSame('test-passphrase', $clientConfig['ssl_key'][1]);
    }

    public function testStaticMethod(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem'
        );

        // Verify it's a static method
        $reflection = new \ReflectionClass(HttpClientFactory::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    public function testCreateWithComplexConfig(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'complex-secret',
            certPath: '/complex/path/cert.pem',
            keyPath: '/complex/path/key.pem',
            keyPassphrase: 'complex-pass',
            verify: '/custom/ca.pem',
            timeoutSeconds: 60,
            connectTimeoutSeconds: 30
        );

        $client = HttpClientFactory::create($config);

        $this->assertInstanceOf(Client::class, $client);

        $reflection = new \ReflectionClass($client);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $clientConfig = $configProperty->getValue($client);

        $this->assertSame(['/complex/path/cert.pem', 'complex-pass'], $clientConfig['cert']);
        $this->assertSame(['/complex/path/key.pem', 'complex-pass'], $clientConfig['ssl_key']);
        $this->assertSame('/custom/ca.pem', $clientConfig['verify']);
        $this->assertSame(60, $clientConfig['timeout']);
        $this->assertSame(30, $clientConfig['connect_timeout']);
    }
}
<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Payment\MtlsHmac\Config\GatewayConfig;
use Payment\MtlsHmac\Http\MtlsOptions;

final class MtlsOptionsTest extends TestCase
{
    public function testForGuzzleWithMinimalConfig(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem'
        );

        $options = MtlsOptions::forGuzzle($config);

        $expected = [
            'cert' => ['/path/to/cert.pem', ''],
            'ssl_key' => ['/path/to/key.pem', ''],
            'verify' => true,
            'timeout' => 15,
            'connect_timeout' => 10,
        ];

        $this->assertSame($expected, $options);
    }

    public function testForGuzzleWithPassphrase(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem',
            keyPassphrase: 'my-passphrase'
        );

        $options = MtlsOptions::forGuzzle($config);

        $expected = [
            'cert' => ['/path/to/cert.pem', 'my-passphrase'],
            'ssl_key' => ['/path/to/key.pem', 'my-passphrase'],
            'verify' => true,
            'timeout' => 15,
            'connect_timeout' => 10,
        ];

        $this->assertSame($expected, $options);
    }

    public function testForGuzzleWithEmptyPassphrase(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem',
            keyPassphrase: ''
        );

        $options = MtlsOptions::forGuzzle($config);

        // Empty passphrase should be passed as empty string
        $expected = [
            'cert' => ['/path/to/cert.pem', ''],
            'ssl_key' => ['/path/to/key.pem', ''],
            'verify' => true,
            'timeout' => 15,
            'connect_timeout' => 10,
        ];

        $this->assertSame($expected, $options);
    }

    public function testForGuzzleWithVerifyFalse(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem',
            verify: false
        );

        $options = MtlsOptions::forGuzzle($config);

        $this->assertFalse($options['verify']);
    }

    public function testForGuzzleWithVerifyPath(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem',
            verify: '/path/to/ca-bundle.pem'
        );

        $options = MtlsOptions::forGuzzle($config);

        $this->assertSame('/path/to/ca-bundle.pem', $options['verify']);
    }

    public function testForGuzzleWithCustomTimeouts(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem',
            timeoutSeconds: 30,
            connectTimeoutSeconds: 20
        );

        $options = MtlsOptions::forGuzzle($config);

        $this->assertSame(30, $options['timeout']);
        $this->assertSame(20, $options['connect_timeout']);
    }

    public function testForGuzzleWithAllOptions(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/custom/cert.pem',
            keyPath: '/custom/key.pem',
            keyPassphrase: 'complex-passphrase',
            verify: '/custom/ca.pem',
            timeoutSeconds: 45,
            connectTimeoutSeconds: 25
        );

        $options = MtlsOptions::forGuzzle($config);

        $expected = [
            'cert' => ['/custom/cert.pem', 'complex-passphrase'],
            'ssl_key' => ['/custom/key.pem', 'complex-passphrase'],
            'verify' => '/custom/ca.pem',
            'timeout' => 45,
            'connect_timeout' => 25,
        ];

        $this->assertSame($expected, $options);
    }

    public function testForGuzzleReturnsArray(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem'
        );

        $options = MtlsOptions::forGuzzle($config);

        $this->assertIsArray($options);
        $this->assertArrayHasKey('cert', $options);
        $this->assertArrayHasKey('ssl_key', $options);
        $this->assertArrayHasKey('verify', $options);
        $this->assertArrayHasKey('timeout', $options);
        $this->assertArrayHasKey('connect_timeout', $options);
    }

    public function testStaticMethod(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem'
        );

        // Verify it's a static method
        $reflection = new \ReflectionClass(MtlsOptions::class);
        $method = $reflection->getMethod('forGuzzle');
        
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }
}
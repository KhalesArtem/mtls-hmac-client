<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Payment\MtlsHmac\Config\GatewayConfig;

final class GatewayConfigTest extends TestCase
{
    public function testConstructorWithRequiredParameters(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret-key',
            certPath: '/path/to/cert.pem',
            keyPath: '/path/to/key.pem'
        );

        $this->assertSame('secret-key', $config->hmacSecret);
        $this->assertSame('/path/to/cert.pem', $config->certPath);
        $this->assertSame('/path/to/key.pem', $config->keyPath);
        
        // Test defaults
        $this->assertNull($config->keyPassphrase);
        $this->assertTrue($config->verify);
        $this->assertSame('sha256', $config->hmacAlgo);
        $this->assertSame('X-Signature', $config->signatureHeader);
        $this->assertSame(15, $config->timeoutSeconds);
        $this->assertSame(10, $config->connectTimeoutSeconds);
    }

    public function testConstructorWithAllParameters(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'test-secret',
            certPath: '/cert.pem',
            keyPath: '/key.pem',
            keyPassphrase: 'passphrase',
            verify: '/ca-bundle.pem',
            hmacAlgo: 'sha512',
            signatureHeader: 'Authorization',
            timeoutSeconds: 30,
            connectTimeoutSeconds: 20
        );

        $this->assertSame('test-secret', $config->hmacSecret);
        $this->assertSame('/cert.pem', $config->certPath);
        $this->assertSame('/key.pem', $config->keyPath);
        $this->assertSame('passphrase', $config->keyPassphrase);
        $this->assertSame('/ca-bundle.pem', $config->verify);
        $this->assertSame('sha512', $config->hmacAlgo);
        $this->assertSame('Authorization', $config->signatureHeader);
        $this->assertSame(30, $config->timeoutSeconds);
        $this->assertSame(20, $config->connectTimeoutSeconds);
    }

    public function testConstructorWithVerifyFalse(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/cert.pem',
            keyPath: '/key.pem',
            verify: false
        );

        $this->assertFalse($config->verify);
    }

    public function testImmutability(): void
    {
        $config = new GatewayConfig(
            hmacSecret: 'secret',
            certPath: '/cert.pem',
            keyPath: '/key.pem'
        );

        // All properties should be readonly
        $reflection = new \ReflectionClass($config);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }
    }
}
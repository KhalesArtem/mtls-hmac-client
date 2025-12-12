<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Payment\MtlsHmac\Config\EnvConfig;
use Payment\MtlsHmac\Config\GatewayConfig;
use Payment\MtlsHmac\Exception\GatewayException;

final class EnvConfigTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        // Backup current environment
        $this->originalEnv = [
            'GATEWAY_ENDPOINT' => getenv('GATEWAY_ENDPOINT'),
            'GATEWAY_HMAC_SECRET' => getenv('GATEWAY_HMAC_SECRET'),
            'GATEWAY_CERT_PATH' => getenv('GATEWAY_CERT_PATH'),
            'GATEWAY_KEY_PATH' => getenv('GATEWAY_KEY_PATH'),
            'GATEWAY_KEY_PASSPHRASE' => getenv('GATEWAY_KEY_PASSPHRASE'),
            'GATEWAY_VERIFY' => getenv('GATEWAY_VERIFY'),
            'GATEWAY_HMAC_ALGO' => getenv('GATEWAY_HMAC_ALGO'),
            'GATEWAY_SIGNATURE_HEADER' => getenv('GATEWAY_SIGNATURE_HEADER'),
        ];
    }

    protected function tearDown(): void
    {
        // Restore original environment
        foreach ($this->originalEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv("{$key}={$value}");
            }
        }
    }

    public function testFromGlobalsWithRequiredEnvVars(): void
    {
        putenv('GATEWAY_HMAC_SECRET=test-secret');
        putenv('GATEWAY_CERT_PATH=/test/cert.pem');
        putenv('GATEWAY_KEY_PATH=/test/key.pem');
        putenv('GATEWAY_KEY_PASSPHRASE'); // Remove passphrase

        $config = EnvConfig::fromGlobals();

        $this->assertInstanceOf(GatewayConfig::class, $config);
        $this->assertSame('test-secret', $config->hmacSecret);
        $this->assertSame('/test/cert.pem', $config->certPath);
        $this->assertSame('/test/key.pem', $config->keyPath);
        $this->assertNull($config->keyPassphrase);
        $this->assertTrue($config->verify);
        $this->assertSame('sha256', $config->hmacAlgo);
        $this->assertSame('X-Signature', $config->signatureHeader);
    }

    public function testFromGlobalsWithAllEnvVars(): void
    {
        putenv('GATEWAY_HMAC_SECRET=full-secret');
        putenv('GATEWAY_CERT_PATH=/full/cert.pem');
        putenv('GATEWAY_KEY_PATH=/full/key.pem');
        putenv('GATEWAY_KEY_PASSPHRASE=my-pass');
        putenv('GATEWAY_VERIFY=/ca/bundle.pem');
        putenv('GATEWAY_HMAC_ALGO=sha512');
        putenv('GATEWAY_SIGNATURE_HEADER=Authorization');

        $config = EnvConfig::fromGlobals();

        $this->assertSame('full-secret', $config->hmacSecret);
        $this->assertSame('/full/cert.pem', $config->certPath);
        $this->assertSame('/full/key.pem', $config->keyPath);
        $this->assertSame('my-pass', $config->keyPassphrase);
        $this->assertSame('/ca/bundle.pem', $config->verify);
        $this->assertSame('sha512', $config->hmacAlgo);
        $this->assertSame('Authorization', $config->signatureHeader);
    }

    public function testFromGlobalsWithMissingSecret(): void
    {
        putenv('GATEWAY_HMAC_SECRET=');
        putenv('GATEWAY_CERT_PATH=/test/cert.pem');
        putenv('GATEWAY_KEY_PATH=/test/key.pem');

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Missing required env vars: GATEWAY_HMAC_SECRET, GATEWAY_CERT_PATH, GATEWAY_KEY_PATH');

        EnvConfig::fromGlobals();
    }

    public function testFromGlobalsWithMissingCert(): void
    {
        putenv('GATEWAY_HMAC_SECRET=test-secret');
        putenv('GATEWAY_CERT_PATH=');
        putenv('GATEWAY_KEY_PATH=/test/key.pem');

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Missing required env vars: GATEWAY_HMAC_SECRET, GATEWAY_CERT_PATH, GATEWAY_KEY_PATH');

        EnvConfig::fromGlobals();
    }

    public function testFromGlobalsWithMissingKey(): void
    {
        putenv('GATEWAY_HMAC_SECRET=test-secret');
        putenv('GATEWAY_CERT_PATH=/test/cert.pem');
        putenv('GATEWAY_KEY_PATH=');

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Missing required env vars: GATEWAY_HMAC_SECRET, GATEWAY_CERT_PATH, GATEWAY_KEY_PATH');

        EnvConfig::fromGlobals();
    }

    /**
     * @dataProvider verifyValueProvider
     */
    public function testVerifyValueParsing(string $envValue, bool|string $expected): void
    {
        putenv('GATEWAY_HMAC_SECRET=test-secret');
        putenv('GATEWAY_CERT_PATH=/test/cert.pem');
        putenv('GATEWAY_KEY_PATH=/test/key.pem');
        putenv("GATEWAY_VERIFY={$envValue}");

        $config = EnvConfig::fromGlobals();
        $this->assertSame($expected, $config->verify);
    }

    public static function verifyValueProvider(): array
    {
        return [
            'false string' => ['false', false],
            'False string' => ['False', false],
            '0 string' => ['0', false],
            'off string' => ['off', false],
            'no string' => ['no', false],
            'true string' => ['true', true],
            'True string' => ['True', true],
            '1 string' => ['1', true],
            'on string' => ['on', true],
            'yes string' => ['yes', true],
            'path string' => ['/custom/ca/path.pem', '/custom/ca/path.pem'],
        ];
    }

    public function testEndpointMethod(): void
    {
        putenv('GATEWAY_ENDPOINT=https://example.com/api');

        $endpoint = EnvConfig::endpoint();
        $this->assertSame('https://example.com/api', $endpoint);
    }

    public function testEndpointMethodWithMissingEnv(): void
    {
        putenv('GATEWAY_ENDPOINT=');

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Missing env var: GATEWAY_ENDPOINT');

        EnvConfig::endpoint();
    }
}
<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Payment\MtlsHmac\Crypto\Canonicalizer;
use Payment\MtlsHmac\Crypto\HmacSigner;
use Payment\MtlsHmac\Exception\GatewayException;

final class HmacSignerTest extends TestCase
{
    public function testCanonicalizerIsDeterministic(): void
    {
        $c = new Canonicalizer();

        $payload = [
            'transaction_id' => '12345',
            'amount' => '99.99',
            'currency' => 'USD',
        ];

        // Must be sorted by key: amount, currency, transaction_id
        $this->assertSame(
            'amount=99.99&currency=USD&transaction_id=12345',
            $c->canonicalize($payload)
        );
    }

    public function testCanonicalizerWithSpecialCharacters(): void
    {
        $c = new Canonicalizer();

        $payload = [
            'message' => 'Hello World!',
            'special' => '!@#$%^&*()+={}[]|\\:;"<>?,./',
            'unicode' => 'тест',
            'spaces' => 'value with spaces',
        ];

        $result = $c->canonicalize($payload);
        
        // Should be URL encoded and sorted by key
        $this->assertStringContainsString('message=Hello%20World%21', $result);
        $this->assertStringContainsString('spaces=value%20with%20spaces', $result);
        $this->assertStringContainsString('unicode=%D1%82%D0%B5%D1%81%D1%82', $result);
    }

    public function testCanonicalizerWithNullValues(): void
    {
        $c = new Canonicalizer();

        $payload = [
            'key1' => 'value1',
            'key2' => null,
            'key3' => '',
            'key4' => 'value4',
        ];

        $result = $c->canonicalize($payload);
        $this->assertSame('key1=value1&key2=&key3=&key4=value4', $result);
    }

    public function testCanonicalizerWithEmptyArray(): void
    {
        $c = new Canonicalizer();
        $result = $c->canonicalize([]);
        $this->assertSame('', $result);
    }

    public function testCanonicalizerSortingOrder(): void
    {
        $c = new Canonicalizer();

        $payload = [
            'zebra' => 'last',
            'apple' => 'first',
            'beta' => 'middle',
            '123' => 'numeric',
        ];

        $result = $c->canonicalize($payload);
        $this->assertSame('123=numeric&apple=first&beta=middle&zebra=last', $result);
    }

    public function testHmacSignerProducesExpectedHexSignature(): void
    {
        $signer = new HmacSigner();

        $payload = [
            'transaction_id' => '12345',
            'amount' => '99.99',
            'currency' => 'USD',
        ];

        $secret = 'top-secret';

        // Precomputed:
        // canonical: amount=99.99&currency=USD&transaction_id=12345
        // algo: sha256
        // output: hex( raw HMAC )
        $expected = 'a208219bd988aee4827485e3be7dfb97e23ed04c19e437a533d455eaaaa9fe34';

        $this->assertSame($expected, $signer->sign($payload, $secret, 'sha256', false));
    }

    public function testHmacSignerWithBase64Output(): void
    {
        $signer = new HmacSigner();

        $payload = ['test' => 'data'];
        $secret = 'test-secret';

        $hexResult = $signer->sign($payload, $secret, 'sha256', false);
        $base64Result = $signer->sign($payload, $secret, 'sha256', true);

        // Base64 should be different from hex
        $this->assertNotSame($hexResult, $base64Result);
        
        // Base64 result should be valid base64
        $this->assertSame(base64_encode(hex2bin($hexResult)), $base64Result);
    }

    public function testHmacSignerWithDifferentAlgorithms(): void
    {
        $signer = new HmacSigner();
        $payload = ['test' => 'data'];
        $secret = 'test-secret';

        $sha256Result = $signer->sign($payload, $secret, 'sha256');
        $sha512Result = $signer->sign($payload, $secret, 'sha512');
        $md5Result = $signer->sign($payload, $secret, 'md5');

        // Different algorithms should produce different results
        $this->assertNotSame($sha256Result, $sha512Result);
        $this->assertNotSame($sha256Result, $md5Result);
        $this->assertNotSame($sha512Result, $md5Result);

        // SHA-512 should produce longer hash than SHA-256
        $this->assertGreaterThan(strlen($sha256Result), strlen($sha512Result));
        
        // MD5 should produce shorter hash than SHA-256
        $this->assertLessThan(strlen($sha256Result), strlen($md5Result));
    }

    public function testHmacSignerWithEmptySecret(): void
    {
        $signer = new HmacSigner();
        $payload = ['test' => 'data'];

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('HMAC secret must not be empty');

        $signer->sign($payload, '', 'sha256');
    }

    public function testHmacSignerWithEmptyPayload(): void
    {
        $signer = new HmacSigner();
        $secret = 'test-secret';

        $result = $signer->sign([], $secret, 'sha256');
        
        // Should produce a valid hash even with empty payload
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $result);
    }

    public function testHmacSignerConsistency(): void
    {
        $signer = new HmacSigner();
        $payload = ['consistent' => 'test'];
        $secret = 'consistent-secret';

        $result1 = $signer->sign($payload, $secret, 'sha256');
        $result2 = $signer->sign($payload, $secret, 'sha256');
        $result3 = $signer->sign($payload, $secret, 'sha256');

        // Same input should always produce same output
        $this->assertSame($result1, $result2);
        $this->assertSame($result2, $result3);
    }

    public function testHmacSignerWithDifferentSecrets(): void
    {
        $signer = new HmacSigner();
        $payload = ['test' => 'data'];

        $result1 = $signer->sign($payload, 'secret1', 'sha256');
        $result2 = $signer->sign($payload, 'secret2', 'sha256');

        // Different secrets should produce different signatures
        $this->assertNotSame($result1, $result2);
    }

    public function testHmacSignerWithDifferentCanonicalization(): void
    {
        $signer = new HmacSigner();
        
        // Test that different canonicalization produces different signatures
        $payload1 = ['a' => '1', 'b' => '2'];
        $payload2 = ['b' => '2', 'a' => '1']; // Same data, different order
        
        $signature1 = $signer->sign($payload1, 'secret', 'sha256');
        $signature2 = $signer->sign($payload2, 'secret', 'sha256');
        
        // Should be the same because canonicalizer sorts keys
        $this->assertSame($signature1, $signature2);
        
        // Test with different values
        $payload3 = ['a' => '2', 'b' => '1']; // Different values
        $signature3 = $signer->sign($payload3, 'secret', 'sha256');
        
        // Should be different
        $this->assertNotSame($signature1, $signature3);
    }
}

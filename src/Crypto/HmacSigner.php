<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Crypto;

use Payment\MtlsHmac\Exception\GatewayException;

/**
 * Computes HMAC signature for payload.
 */
final class HmacSigner
{
    public function __construct(
        private readonly Canonicalizer $canonicalizer = new Canonicalizer(),
    ) {}

    /**
     * @param array<string, scalar|null> $payload
     */
    public function sign(
        array $payload,
        string $secret,
        string $algo = 'sha256',
        bool $base64 = false
    ): string
    {
        if ($secret === '') {
            throw new GatewayException('HMAC secret must not be empty');
        }

        $data = $this->canonicalizer->canonicalize($payload);
        $raw  = hash_hmac($algo, $data, $secret, true);

        return $base64 ? base64_encode($raw) : bin2hex($raw);
    }
}

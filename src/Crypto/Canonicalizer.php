<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Crypto;

/**
 * Creates a deterministic "canonical" representation of payload used for HMAC.
 *
 * Strategy:
 * - one-dimensional array only
 * - keys sorted ascending
 * - RFC3986 encoding
 * - joined as key=value&key2=value2
 */
final class Canonicalizer
{
    /**
     * @param array<string, scalar|null> $payload
     */
    public function canonicalize(array $payload): string
    {
        ksort($payload);

        $pairs = [];
        foreach ($payload as $key => $value) {
            $k = rawurlencode((string) $key);
            $v = rawurlencode($value === null ? '' : (string) $value);
            $pairs[] = $k . '=' . $v;
        }

        return implode('&', $pairs);
    }
}

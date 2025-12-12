<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Config;

use Payment\MtlsHmac\Exception\GatewayException;

final class EnvConfig
{
    public static function fromGlobals(): GatewayConfig
    {
        $secret   = (string) (getenv('GATEWAY_HMAC_SECRET') ?: '');
        $cert     = (string) (getenv('GATEWAY_CERT_PATH') ?: '');
        $key      = (string) (getenv('GATEWAY_KEY_PATH') ?: '');
        $pass     = getenv('GATEWAY_KEY_PASSPHRASE');
        $verify   = getenv('GATEWAY_VERIFY');
        $algo     = (string) (getenv('GATEWAY_HMAC_ALGO') ?: 'sha256');
        $header   = (string) (getenv('GATEWAY_SIGNATURE_HEADER') ?: 'X-Signature');

        if ($secret === '' || $cert === '' || $key === '') {
            throw new GatewayException('Missing required env vars: GATEWAY_HMAC_SECRET, GATEWAY_CERT_PATH, GATEWAY_KEY_PATH');
        }

        $verifyValue = match (true) {
            $verify === false || $verify === '' => true,
            default => match (strtolower((string) $verify)) {
                'false', '0', 'off', 'no' => false,
                'true', '1', 'on', 'yes' => true,
                default => (string) $verify, // treat as path
            }
        };

        return new GatewayConfig(
            hmacSecret: $secret,
            certPath: $cert,
            keyPath: $key,
            keyPassphrase: $pass === false ? null : (string) $pass,
            verify: $verifyValue,
            hmacAlgo: $algo,
            signatureHeader: $header,
        );
    }

    public static function endpoint(): string
    {
        $endpoint = (string) (getenv('GATEWAY_ENDPOINT') ?: '');
        if ($endpoint === '') {
            throw new GatewayException('Missing env var: GATEWAY_ENDPOINT');
        }
        return $endpoint;
    }
}

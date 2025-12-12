<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Config;

final class GatewayConfig
{
    /**
     * @param bool|string $verify  true/false OR path to CA bundle
     */
    public function __construct(
        ?string $hmacSecret = null,
        ?string $certPath = null,
        ?string $keyPath = null,
        ?string $keyPassphrase = null,
        bool|string|null $verify = null,
        ?string $hmacAlgo = null,
        ?string $signatureHeader = null,
        ?int $timeoutSeconds = null,
        ?int $connectTimeoutSeconds = null,
    ) {
        $this->hmacSecret = $hmacSecret ?? (string) (getenv('GATEWAY_HMAC_SECRET') ?: '');
        $this->certPath = $certPath ?? (string) (getenv('GATEWAY_CERT_PATH') ?: '');
        $this->keyPath = $keyPath ?? (string) (getenv('GATEWAY_KEY_PATH') ?: '');
        
        if ($keyPassphrase === null) {
            $envPass = getenv('GATEWAY_KEY_PASSPHRASE');
            if ($envPass === false || $envPass === '') {
                $this->keyPassphrase = null;
            } else {
                $this->keyPassphrase = (string) $envPass;
            }
        } else {
            $this->keyPassphrase = $keyPassphrase;
        }
        
        // Обработка verify
        $this->verify = $verify ?? match (true) {
            ($envVerify = getenv('GATEWAY_VERIFY')) === false || $envVerify === '' => true,
            default => match (strtolower((string) $envVerify)) {
                'false', '0', 'off', 'no' => false,
                'true', '1', 'on', 'yes' => true,
                default => (string) $envVerify, // treat as path
            }
        };
        
        $this->hmacAlgo = $hmacAlgo ?? (string) (getenv('GATEWAY_HMAC_ALGO') ?: 'sha256');
        $this->signatureHeader = $signatureHeader ?? (string) (getenv('GATEWAY_SIGNATURE_HEADER') ?: 'X-Signature');
        $this->timeoutSeconds = $timeoutSeconds ?? (int) (getenv('GATEWAY_TIMEOUT_SECONDS') ?: 15);
        $this->connectTimeoutSeconds = $connectTimeoutSeconds ?? (int) (getenv('GATEWAY_CONNECT_TIMEOUT_SECONDS') ?: 10);
        
        if ($this->hmacSecret === '' || $this->certPath === '' || $this->keyPath === '') {
            throw new \Payment\MtlsHmac\Exception\GatewayException(
                'Missing required parameters: hmacSecret, certPath, keyPath. ' .
                'Pass them directly or set GATEWAY_HMAC_SECRET, GATEWAY_CERT_PATH, GATEWAY_KEY_PATH env vars.'
            );
        }
    }

    public readonly string $hmacSecret;
    public readonly string $certPath;
    public readonly string $keyPath;
    public readonly ?string $keyPassphrase;
    public readonly bool|string $verify;
    public readonly string $hmacAlgo;
    public readonly string $signatureHeader;
    public readonly int $timeoutSeconds;
    public readonly int $connectTimeoutSeconds;
}

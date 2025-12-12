<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Http;

use Payment\MtlsHmac\Config\GatewayConfig;

final class MtlsOptions
{
    public static function forGuzzle(GatewayConfig $config): array
    {

        return [
            'cert'            => [$config->certPath, $config->keyPassphrase ?? ''],
            'ssl_key'         => [$config->keyPath, $config->keyPassphrase ?? ''],
            'verify'          => $config->verify,
            'timeout'         => $config->timeoutSeconds,
            'connect_timeout' => $config->connectTimeoutSeconds,
        ];
    }
}

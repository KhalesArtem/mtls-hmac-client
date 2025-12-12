<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Http;

use GuzzleHttp\Client;
use Payment\MtlsHmac\Config\GatewayConfig;

final class HttpClientFactory
{
    public static function create(GatewayConfig $config, array $extra = []): Client
    {
        $options = array_merge(MtlsOptions::forGuzzle($config), $extra);

        return new Client($options);
    }
}

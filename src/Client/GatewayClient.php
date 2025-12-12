<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Payment\MtlsHmac\Config\EnvConfig;
use Payment\MtlsHmac\Config\GatewayConfig;
use Payment\MtlsHmac\Crypto\HmacSigner;
use Payment\MtlsHmac\Exception\GatewayException;
use Payment\MtlsHmac\Exception\HttpException;
use Payment\MtlsHmac\Http\HttpClientFactory;

final class GatewayClient
{
    public function __construct(
        private readonly GatewayConfig $config,
        ?Client $http = null,
        ?HmacSigner $signer = null,
    ) {
        $this->http = $http ?? HttpClientFactory::create($config);
        $this->signer = $signer ?? new HmacSigner();
    }

    private readonly Client $http;
    private readonly HmacSigner $signer;

    public static function fromEnv(): self
    {
        return new self(EnvConfig::fromGlobals());
    }

    /**
     *
     * @param string $endpointUrl
     * @param array<string, scalar|null> $payload
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function get(string $endpointUrl, array $payload): ResponseInterface
    {
        if ($endpointUrl === '') {
            throw new GatewayException('Endpoint URL must not be empty');
        }

        $signature = $this->signer->sign($payload, $this->config->hmacSecret, $this->config->hmacAlgo);

        $response = $this->http->request('GET', $endpointUrl, [
            'query' => $payload,
            'headers' => [
                $this->config->signatureHeader => $signature,
            ],
            'http_errors' => false,
        ]);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw HttpException::fromResponse($response);
        }

        return $response;
    }

    /**
     *
     * @param array<string, scalar|null> $payload
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function getFromEnvEndpoint(array $payload): ResponseInterface
    {
        return $this->get(EnvConfig::endpoint(), $payload);
    }
}

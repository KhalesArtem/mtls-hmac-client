<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Exception;

use Psr\Http\Message\ResponseInterface;

class HttpException extends GatewayException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
        public readonly array $headers = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct("Unexpected HTTP status code: {$statusCode}", $statusCode, $previous);
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        return new self(
            $response->getStatusCode(),
            (string) $response->getBody(),
            $response->getHeaders()
        );
    }
}

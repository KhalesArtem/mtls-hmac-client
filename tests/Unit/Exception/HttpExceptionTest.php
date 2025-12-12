<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Payment\MtlsHmac\Exception\GatewayException;
use Payment\MtlsHmac\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class HttpExceptionTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new HttpException(
            statusCode: 404,
            body: 'Not Found',
            headers: ['Content-Type' => ['application/json']],
            previous: null
        );

        $this->assertSame(404, $exception->statusCode);
        $this->assertSame('Not Found', $exception->body);
        $this->assertSame(['Content-Type' => ['application/json']], $exception->headers);
        $this->assertSame('Unexpected HTTP status code: 404', $exception->getMessage());
        $this->assertSame(404, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithDefaults(): void
    {
        $exception = new HttpException(
            statusCode: 500,
            body: 'Internal Server Error'
        );

        $this->assertSame(500, $exception->statusCode);
        $this->assertSame('Internal Server Error', $exception->body);
        $this->assertSame([], $exception->headers);
        $this->assertSame('Unexpected HTTP status code: 500', $exception->getMessage());
    }

    public function testConstructorWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        
        $exception = new HttpException(
            statusCode: 503,
            body: 'Service Unavailable',
            previous: $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testFromResponse(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('Error response body');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(400);
        $response->method('getBody')->willReturn($stream);
        $response->method('getHeaders')->willReturn([
            'Content-Type' => ['application/json'],
            'X-Error-Code' => ['INVALID_REQUEST']
        ]);

        $exception = HttpException::fromResponse($response);

        $this->assertSame(400, $exception->statusCode);
        $this->assertSame('Error response body', $exception->body);
        $this->assertSame([
            'Content-Type' => ['application/json'],
            'X-Error-Code' => ['INVALID_REQUEST']
        ], $exception->headers);
        $this->assertSame('Unexpected HTTP status code: 400', $exception->getMessage());
    }

    public function testExtendsGatewayException(): void
    {
        $exception = new HttpException(404, 'Not Found');

        $this->assertInstanceOf(GatewayException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testReadonlyProperties(): void
    {
        $exception = new HttpException(418, "I'm a teapot");

        $reflection = new \ReflectionClass($exception);
        
        $statusCodeProperty = $reflection->getProperty('statusCode');
        $this->assertTrue($statusCodeProperty->isReadOnly());
        
        $bodyProperty = $reflection->getProperty('body');
        $this->assertTrue($bodyProperty->isReadOnly());
        
        $headersProperty = $reflection->getProperty('headers');
        $this->assertTrue($headersProperty->isReadOnly());
    }

    public function testDifferentStatusCodes(): void
    {
        $testCases = [
            [400, 'Bad Request'],
            [401, 'Unauthorized'], 
            [403, 'Forbidden'],
            [404, 'Not Found'],
            [500, 'Internal Server Error'],
            [502, 'Bad Gateway'],
            [503, 'Service Unavailable'],
        ];

        foreach ($testCases as [$statusCode, $body]) {
            $exception = new HttpException($statusCode, $body);
            
            $this->assertSame($statusCode, $exception->statusCode);
            $this->assertSame($body, $exception->body);
            $this->assertSame("Unexpected HTTP status code: {$statusCode}", $exception->getMessage());
            $this->assertSame($statusCode, $exception->getCode());
        }
    }
}
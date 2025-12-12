<?php
declare(strict_types=1);

namespace Payment\MtlsHmac\Tests\Unit\Exception;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Payment\MtlsHmac\Exception\GatewayException;
use RuntimeException;

final class GatewayExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        $exception = new GatewayException('Test message');

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testConstructorWithMessage(): void
    {
        $message = 'Gateway configuration error';
        $exception = new GatewayException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithMessageAndCode(): void
    {
        $message = 'Gateway timeout';
        $code = 408;
        $exception = new GatewayException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithPreviousException(): void
    {
        $previous = new InvalidArgumentException('Previous error');
        $exception = new GatewayException('Gateway error', 500, $previous);

        $this->assertSame('Gateway error', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testCanBeThrownAndCaught(): void
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Test gateway exception');
        $this->expectExceptionCode(123);

        throw new GatewayException('Test gateway exception', 123);
    }

    public function testCanBeCaughtAsRuntimeException(): void
    {
        try {
            throw new GatewayException('Test exception');
        } catch (RuntimeException $e) {
            $this->assertInstanceOf(GatewayException::class, $e);
            $this->assertSame('Test exception', $e->getMessage());
        }
    }

    public function testCanBeCaughtAsBaseException(): void
    {
        try {
            throw new GatewayException('Base exception test');
        } catch (Exception $e) {
            $this->assertInstanceOf(GatewayException::class, $e);
            $this->assertSame('Base exception test', $e->getMessage());
        }
    }
}
<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Invoice\Helpers\GoogleTranslateNetworkException;
use Codeception\Test\Unit;
use RuntimeException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateNetworkExceptionTest extends Unit
{
    public function testExceptionInheritance(): void
    {
        $exception = new GoogleTranslateNetworkException();
        
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(FriendlyExceptionInterface::class, $exception);
    }

    public function testGetName(): void
    {
        $exception = new GoogleTranslateNetworkException();
        
        $expectedName = 'There appears to be a Network error.';
        
        $this->assertSame($expectedName, $exception->getName());
    }

    public function testGetSolution(): void
    {
        $exception = new GoogleTranslateNetworkException();
        
        $expectedSolution = <<<'SOLUTION'
                Please try again later.
            SOLUTION;
        
        $this->assertSame($expectedSolution, $exception->getSolution());
    }

    public function testGetNameContainsExpectedContent(): void
    {
        $exception = new GoogleTranslateNetworkException();
        $name = $exception->getName();
        
        $this->assertStringContainsString('Network error', $name);
        $this->assertStringContainsString('appears to be', $name);
    }

    public function testGetSolutionContainsHelpfulAdvice(): void
    {
        $exception = new GoogleTranslateNetworkException();
        $solution = $exception->getSolution();
        
        $this->assertStringContainsString('try again later', $solution);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'Network connection failed';
        $exception = new GoogleTranslateNetworkException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Connection timeout';
        $code = 504;
        $exception = new GoogleTranslateNetworkException($message, $code);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionWithPreviousException(): void
    {
        $previousException = new RuntimeException('Curl error');
        $exception = new GoogleTranslateNetworkException('Network error', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(GoogleTranslateNetworkException::class);
        $this->expectExceptionMessage('Network issue');
        
        throw new GoogleTranslateNetworkException('Network issue');
    }

    public function testExceptionCanBeCaught(): void
    {
        try {
            throw new GoogleTranslateNetworkException('Connection failed');
        } catch (GoogleTranslateNetworkException $e) {
            $this->assertSame('Connection failed', $e->getMessage());
            $this->assertInstanceOf(GoogleTranslateNetworkException::class, $e);
        }
    }

    public function testGetNameReturnType(): void
    {
        $exception = new GoogleTranslateNetworkException();
        
        $this->assertIsString($exception->getName());
    }

    public function testGetSolutionReturnType(): void
    {
        $exception = new GoogleTranslateNetworkException();
        
        $this->assertIsString($exception->getSolution());
    }

    public function testGetNameIsNotEmpty(): void
    {
        $exception = new GoogleTranslateNetworkException();
        
        $this->assertNotEmpty($exception->getName());
    }

    public function testGetSolutionIsNotEmpty(): void
    {
        $exception = new GoogleTranslateNetworkException();
        
        $this->assertNotEmpty($exception->getSolution());
    }

    public function testExceptionDefaultValues(): void
    {
        $exception = new GoogleTranslateNetworkException();
        
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionStackTrace(): void
    {
        $exception = new GoogleTranslateNetworkException('Stack test');
        
        $this->assertIsArray($exception->getTrace());
        $this->assertIsString($exception->getTraceAsString());
    }

    public function testExceptionFile(): void
    {
        $exception = new GoogleTranslateNetworkException();
        
        $this->assertIsString($exception->getFile());
        $this->assertIsInt($exception->getLine());
    }

    public function testExceptionToString(): void
    {
        $exception = new GoogleTranslateNetworkException('String test');
        
        $stringRepresentation = (string) $exception;
        
        $this->assertIsString($stringRepresentation);
        $this->assertStringContainsString('GoogleTranslateNetworkException', $stringRepresentation);
        $this->assertStringContainsString('String test', $stringRepresentation);
    }

    public function testExceptionImplementsInterface(): void
    {
        $exception = new GoogleTranslateNetworkException();
        
        $this->assertTrue(method_exists($exception, 'getName'));
        $this->assertTrue(method_exists($exception, 'getSolution'));
    }
}

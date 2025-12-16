<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Invoice\Helpers\GoogleTranslateDiffEmptyException;
use Codeception\Test\Unit;
use RuntimeException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateDiffEmptyExceptionTest extends Unit
{
    public function testExceptionInheritance(): void
    {
        $exception = new GoogleTranslateDiffEmptyException();
        
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(FriendlyExceptionInterface::class, $exception);
    }

    public function testGetName(): void
    {
        $exception = new GoogleTranslateDiffEmptyException();
        
        $expectedName = 'The diff array that has been built is empty. The existing target locale app.php already has all the necessary keys of the source app.php.';
        
        $this->assertSame($expectedName, $exception->getName());
    }

    public function testGetSolution(): void
    {
        $exception = new GoogleTranslateDiffEmptyException();
        
        $expectedSolution = <<<'SOLUTION'
               There is no need to translate
            SOLUTION;
        
        $this->assertSame($expectedSolution, $exception->getSolution());
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'Custom error message';
        $exception = new GoogleTranslateDiffEmptyException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Custom error message';
        $code = 404;
        $exception = new GoogleTranslateDiffEmptyException($message, $code);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionWithPreviousException(): void
    {
        $previousException = new RuntimeException('Previous exception');
        $exception = new GoogleTranslateDiffEmptyException('Current exception', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(GoogleTranslateDiffEmptyException::class);
        $this->expectExceptionMessage('Test message');
        
        throw new GoogleTranslateDiffEmptyException('Test message');
    }

    public function testExceptionCanBeCaught(): void
    {
        try {
            throw new GoogleTranslateDiffEmptyException('Test exception');
        } catch (GoogleTranslateDiffEmptyException $e) {
            $this->assertSame('Test exception', $e->getMessage());
            $this->assertInstanceOf(GoogleTranslateDiffEmptyException::class, $e);
        }
    }

    public function testGetNameReturnType(): void
    {
        $exception = new GoogleTranslateDiffEmptyException();
        
        $this->assertIsString($exception->getName());
    }

    public function testGetSolutionReturnType(): void
    {
        $exception = new GoogleTranslateDiffEmptyException();
        
        $this->assertIsString($exception->getSolution());
    }

    public function testGetNameIsNotEmpty(): void
    {
        $exception = new GoogleTranslateDiffEmptyException();
        
        $this->assertNotEmpty($exception->getName());
    }

    public function testGetSolutionIsNotEmpty(): void
    {
        $exception = new GoogleTranslateDiffEmptyException();
        
        $this->assertNotEmpty($exception->getSolution());
    }

    public function testExceptionDefaultValues(): void
    {
        $exception = new GoogleTranslateDiffEmptyException();
        
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionStackTrace(): void
    {
        $exception = new GoogleTranslateDiffEmptyException('Stack trace test');
        
        $this->assertIsArray($exception->getTrace());
        $this->assertIsString($exception->getTraceAsString());
    }

    public function testExceptionFile(): void
    {
        $exception = new GoogleTranslateDiffEmptyException();
        
        $this->assertIsString($exception->getFile());
        $this->assertIsInt($exception->getLine());
    }

    public function testExceptionToString(): void
    {
        $exception = new GoogleTranslateDiffEmptyException('String conversion test');
        
        $stringRepresentation = (string) $exception;
        
        $this->assertIsString($stringRepresentation);
        $this->assertStringContainsString('GoogleTranslateDiffEmptyException', $stringRepresentation);
        $this->assertStringContainsString('String conversion test', $stringRepresentation);
    }

    public function testExceptionImplementsInterface(): void
    {
        $exception = new GoogleTranslateDiffEmptyException();
        
        $this->assertTrue(method_exists($exception, 'getName'));
        $this->assertTrue(method_exists($exception, 'getSolution'));
    }
}

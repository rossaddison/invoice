<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Invoice\Helpers\GoogleTranslateTypeNotFoundException;
use Codeception\Test\Unit;
use RuntimeException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateTypeNotFoundExceptionTest extends Unit
{
    public function testExceptionInheritance(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(FriendlyExceptionInterface::class, $exception);
    }

    public function testGetName(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        
        $expectedName = 'There appears to be no language related file selected.';
        
        $this->assertSame($expectedName, $exception->getName());
    }

    public function testGetSolution(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        
        $expectedSolution = <<<'SOLUTION'
                Please try again later.
            SOLUTION;
        
        $this->assertSame($expectedSolution, $exception->getSolution());
    }

    public function testGetNameContainsExpectedContent(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        $name = $exception->getName();
        
        $this->assertStringContainsString('no language', $name);
        $this->assertStringContainsString('file selected', $name);
        $this->assertStringContainsString('appears to be', $name);
    }

    public function testGetSolutionContainsHelpfulAdvice(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        $solution = $exception->getSolution();
        
        $this->assertStringContainsString('try again later', $solution);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'Language file not found';
        $exception = new GoogleTranslateTypeNotFoundException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'No language file available';
        $code = 404;
        $exception = new GoogleTranslateTypeNotFoundException($message, $code);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionWithPreviousException(): void
    {
        $previousException = new RuntimeException('File not found');
        $exception = new GoogleTranslateTypeNotFoundException('Language file error', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(GoogleTranslateTypeNotFoundException::class);
        $this->expectExceptionMessage('File not selected');
        
        throw new GoogleTranslateTypeNotFoundException('File not selected');
    }

    public function testExceptionCanBeCaught(): void
    {
        try {
            throw new GoogleTranslateTypeNotFoundException('Type not found');
        } catch (GoogleTranslateTypeNotFoundException $e) {
            $this->assertSame('Type not found', $e->getMessage());
            $this->assertInstanceOf(GoogleTranslateTypeNotFoundException::class, $e);
        }
    }

    public function testGetNameReturnType(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        
        $this->assertIsString($exception->getName());
    }

    public function testGetSolutionReturnType(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        
        $this->assertIsString($exception->getSolution());
    }

    public function testGetNameIsNotEmpty(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        
        $this->assertNotEmpty($exception->getName());
    }

    public function testGetSolutionIsNotEmpty(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        
        $this->assertNotEmpty($exception->getSolution());
    }

    public function testExceptionDefaultValues(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionStackTrace(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException('Stack test');
        
        $this->assertIsArray($exception->getTrace());
        $this->assertIsString($exception->getTraceAsString());
    }

    public function testExceptionFile(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        
        $this->assertIsString($exception->getFile());
        $this->assertIsInt($exception->getLine());
    }

    public function testExceptionToString(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException('String test');
        
        $stringRepresentation = (string) $exception;
        
        $this->assertIsString($stringRepresentation);
        $this->assertStringContainsString('GoogleTranslateTypeNotFoundException', $stringRepresentation);
        $this->assertStringContainsString('String test', $stringRepresentation);
    }

    public function testExceptionImplementsInterface(): void
    {
        $exception = new GoogleTranslateTypeNotFoundException();
        
        $this->assertTrue(method_exists($exception, 'getName'));
        $this->assertTrue(method_exists($exception, 'getSolution'));
    }
}

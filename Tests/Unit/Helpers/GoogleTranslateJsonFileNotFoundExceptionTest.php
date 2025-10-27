<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Invoice\Helpers\GoogleTranslateJsonFileNotFoundException;
use Codeception\Test\Unit;
use RuntimeException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateJsonFileNotFoundExceptionTest extends Unit
{
    public function testExceptionInheritance(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException();
        
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(FriendlyExceptionInterface::class, $exception);
    }

    public function testGetName(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException();
        
        $expectedName = 'The Json file that you downloaded at https://console.cloud.google.com/iam-admin/serviceaccounts/details/{unique_project_id}/keys?project={your_project_name} cannot be found in .../src/Invoice/Google_translate_unique_folder.';
        
        $this->assertSame($expectedName, $exception->getName());
    }

    public function testGetSolution(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException();
        
        $expectedSolution = <<<'SOLUTION'
                Please try again
            SOLUTION;
        
        $this->assertSame($expectedSolution, $exception->getSolution());
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'JSON file not found';
        $exception = new GoogleTranslateJsonFileNotFoundException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'JSON file not found';
        $code = 404;
        $exception = new GoogleTranslateJsonFileNotFoundException($message, $code);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionWithPreviousException(): void
    {
        $previousException = new RuntimeException('Previous exception');
        $exception = new GoogleTranslateJsonFileNotFoundException('Current exception', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(GoogleTranslateJsonFileNotFoundException::class);
        $this->expectExceptionMessage('File not found');
        
        throw new GoogleTranslateJsonFileNotFoundException('File not found');
    }

    public function testExceptionCanBeCaught(): void
    {
        try {
            throw new GoogleTranslateJsonFileNotFoundException('JSON exception');
        } catch (GoogleTranslateJsonFileNotFoundException $e) {
            $this->assertSame('JSON exception', $e->getMessage());
            $this->assertInstanceOf(GoogleTranslateJsonFileNotFoundException::class, $e);
        }
    }

    public function testGetNameReturnType(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException();
        
        $this->assertIsString($exception->getName());
    }

    public function testGetSolutionReturnType(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException();
        
        $this->assertIsString($exception->getSolution());
    }

    public function testGetNameContainsExpectedContent(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException();
        $name = $exception->getName();
        
        $this->assertStringContainsString('Json file', $name);
        $this->assertStringContainsString('console.cloud.google.com', $name);
        $this->assertStringContainsString('Google_translate_unique_folder', $name);
    }

    public function testGetSolutionContainsHelpfulAdvice(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException();
        $solution = $exception->getSolution();
        
        $this->assertStringContainsString('try again', $solution);
    }

    public function testExceptionDefaultValues(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException();
        
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionStackTrace(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException('Stack trace test');
        
        $this->assertIsArray($exception->getTrace());
        $this->assertIsString($exception->getTraceAsString());
    }

    public function testExceptionFile(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException();
        
        $this->assertIsString($exception->getFile());
        $this->assertIsInt($exception->getLine());
    }

    public function testExceptionToString(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException('String test');
        
        $stringRepresentation = (string) $exception;
        
        $this->assertIsString($stringRepresentation);
        $this->assertStringContainsString('GoogleTranslateJsonFileNotFoundException', $stringRepresentation);
        $this->assertStringContainsString('String test', $stringRepresentation);
    }

    public function testExceptionImplementsInterface(): void
    {
        $exception = new GoogleTranslateJsonFileNotFoundException();
        
        $this->assertTrue(method_exists($exception, 'getName'));
        $this->assertTrue(method_exists($exception, 'getSolution'));
    }
}
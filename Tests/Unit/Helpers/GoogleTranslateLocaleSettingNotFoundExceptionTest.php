<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Invoice\Helpers\GoogleTranslateLocaleSettingNotFoundException;
use Codeception\Test\Unit;
use RuntimeException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class GoogleTranslateLocaleSettingNotFoundExceptionTest extends Unit
{
    public function testExceptionInheritance(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(FriendlyExceptionInterface::class, $exception);
    }

    public function testGetName(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        
        $expectedName = 'Settings...View...Google Translate...Locale has not been chosen.';
        
        $this->assertSame($expectedName, $exception->getName());
    }

    public function testGetSolution(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        
        $expectedSolution = <<<'SOLUTION'
                Please select a locale. The translation to the eg. ip_lang can then start.
            SOLUTION;
        
        $this->assertSame($expectedSolution, $exception->getSolution());
    }

    public function testGetNameContainsExpectedContent(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        $name = $exception->getName();
        
        $this->assertStringContainsString('Settings', $name);
        $this->assertStringContainsString('Google Translate', $name);
        $this->assertStringContainsString('Locale', $name);
        $this->assertStringContainsString('not been chosen', $name);
    }

    public function testGetSolutionContainsHelpfulAdvice(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        $solution = $exception->getSolution();
        
        $this->assertStringContainsString('select a locale', $solution);
        $this->assertStringContainsString('translation', $solution);
        $this->assertStringContainsString('ip_lang', $solution);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'Locale setting missing';
        $exception = new GoogleTranslateLocaleSettingNotFoundException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'No locale configured';
        $code = 400;
        $exception = new GoogleTranslateLocaleSettingNotFoundException($message, $code);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionWithPreviousException(): void
    {
        $previousException = new RuntimeException('Configuration error');
        $exception = new GoogleTranslateLocaleSettingNotFoundException('Locale error', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(GoogleTranslateLocaleSettingNotFoundException::class);
        $this->expectExceptionMessage('Locale not found');
        
        throw new GoogleTranslateLocaleSettingNotFoundException('Locale not found');
    }

    public function testExceptionCanBeCaught(): void
    {
        try {
            throw new GoogleTranslateLocaleSettingNotFoundException('Locale exception');
        } catch (GoogleTranslateLocaleSettingNotFoundException $e) {
            $this->assertSame('Locale exception', $e->getMessage());
            $this->assertInstanceOf(GoogleTranslateLocaleSettingNotFoundException::class, $e);
        }
    }

    public function testGetNameReturnType(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        
        $this->assertIsString($exception->getName());
    }

    public function testGetSolutionReturnType(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        
        $this->assertIsString($exception->getSolution());
    }

    public function testGetNameIsNotEmpty(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        
        $this->assertNotEmpty($exception->getName());
    }

    public function testGetSolutionIsNotEmpty(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        
        $this->assertNotEmpty($exception->getSolution());
    }

    public function testExceptionDefaultValues(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionStackTrace(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException('Stack test');
        
        $this->assertIsArray($exception->getTrace());
        $this->assertIsString($exception->getTraceAsString());
    }

    public function testExceptionFile(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        
        $this->assertIsString($exception->getFile());
        $this->assertIsInt($exception->getLine());
    }

    public function testExceptionToString(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException('String test');
        
        $stringRepresentation = (string) $exception;
        
        $this->assertIsString($stringRepresentation);
        $this->assertStringContainsString('GoogleTranslateLocaleSettingNotFoundException', $stringRepresentation);
        $this->assertStringContainsString('String test', $stringRepresentation);
    }

    public function testExceptionImplementsInterface(): void
    {
        $exception = new GoogleTranslateLocaleSettingNotFoundException();
        
        $this->assertTrue(method_exists($exception, 'getName'));
        $this->assertTrue(method_exists($exception, 'getSolution'));
    }
}
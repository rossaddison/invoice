<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Invoice\Helpers\CaCertFileNotFoundException;
use Codeception\Test\Unit;
use RuntimeException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class CaCertFileNotFoundExceptionTest extends Unit
{
    public function testExceptionInheritance(): void
    {
        $exception = new CaCertFileNotFoundException();
        
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(FriendlyExceptionInterface::class, $exception);
    }

    public function testGetName(): void
    {
        $exception = new CaCertFileNotFoundException();
        $name = $exception->getName();
        
        $this->assertIsString($name);
        $this->assertStringContainsString('SSL certificate', $name);
        $this->assertStringContainsString('cacert.pem', $name);
        $this->assertStringContainsString(PHP_VERSION, $name);
        $this->assertStringContainsString('curl.haxx.se', $name);
    }

    public function testGetNameContainsPHPVersion(): void
    {
        $exception = new CaCertFileNotFoundException();
        $name = $exception->getName();
        
        // Should contain the current PHP version twice
        $occurrences = substr_count($name, PHP_VERSION);
        $this->assertGreaterThanOrEqual(2, $occurrences);
    }

    public function testGetSolution(): void
    {
        $exception = new CaCertFileNotFoundException();
        $solution = $exception->getSolution();
        
        $this->assertIsString($solution);
        $this->assertStringContainsString('Download from this website', $solution);
        $this->assertStringContainsString('cloud.google.com', $solution);
        $this->assertStringContainsString('service account', $solution);
        $this->assertStringContainsString('Translation API', $solution);
        $this->assertStringContainsString('Json file', $solution);
    }

    public function testGetSolutionContainsSteps(): void
    {
        $exception = new CaCertFileNotFoundException();
        $solution = $exception->getSolution();
        
        $this->assertStringContainsString('1.', $solution);
        $this->assertStringContainsString('2.', $solution);
        $this->assertStringContainsString('3.', $solution);
        $this->assertStringContainsString('4.', $solution);
        $this->assertStringContainsString('5.', $solution);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'Custom certificate error';
        $exception = new CaCertFileNotFoundException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Certificate not found';
        $code = 500;
        $exception = new CaCertFileNotFoundException($message, $code);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionWithPreviousException(): void
    {
        $previousException = new RuntimeException('SSL error');
        $exception = new CaCertFileNotFoundException('Certificate error', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(CaCertFileNotFoundException::class);
        $this->expectExceptionMessage('Cert file missing');
        
        throw new CaCertFileNotFoundException('Cert file missing');
    }

    public function testExceptionCanBeCaught(): void
    {
        try {
            throw new CaCertFileNotFoundException('Certificate exception');
        } catch (CaCertFileNotFoundException $e) {
            $this->assertSame('Certificate exception', $e->getMessage());
            $this->assertInstanceOf(CaCertFileNotFoundException::class, $e);
        }
    }

    public function testGetNameReturnType(): void
    {
        $exception = new CaCertFileNotFoundException();
        
        $this->assertIsString($exception->getName());
    }

    public function testGetSolutionReturnType(): void
    {
        $exception = new CaCertFileNotFoundException();
        
        $this->assertIsString($exception->getSolution());
    }

    public function testGetNameIsNotEmpty(): void
    {
        $exception = new CaCertFileNotFoundException();
        
        $this->assertNotEmpty($exception->getName());
    }

    public function testGetSolutionIsNotEmpty(): void
    {
        $exception = new CaCertFileNotFoundException();
        
        $this->assertNotEmpty($exception->getSolution());
    }

    public function testExceptionDefaultValues(): void
    {
        $exception = new CaCertFileNotFoundException();
        
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionStackTrace(): void
    {
        $exception = new CaCertFileNotFoundException('Stack test');
        
        $this->assertIsArray($exception->getTrace());
        $this->assertIsString($exception->getTraceAsString());
    }

    public function testExceptionFile(): void
    {
        $exception = new CaCertFileNotFoundException();
        
        $this->assertIsString($exception->getFile());
        $this->assertIsInt($exception->getLine());
    }

    public function testExceptionToString(): void
    {
        $exception = new CaCertFileNotFoundException('String test');
        
        $stringRepresentation = (string) $exception;
        
        $this->assertIsString($stringRepresentation);
        $this->assertStringContainsString('CaCertFileNotFoundException', $stringRepresentation);
        $this->assertStringContainsString('String test', $stringRepresentation);
    }

    public function testExceptionImplementsInterface(): void
    {
        $exception = new CaCertFileNotFoundException();
        
        $this->assertTrue(method_exists($exception, 'getName'));
        $this->assertTrue(method_exists($exception, 'getSolution'));
    }

    public function testGetSolutionStepsAreOrdered(): void
    {
        $exception = new CaCertFileNotFoundException();
        $solution = $exception->getSolution();
        
        $step1Pos = strpos($solution, '1.');
        $step2Pos = strpos($solution, '2.');
        $step3Pos = strpos($solution, '3.');
        
        $this->assertLessThan($step2Pos, $step1Pos);
        $this->assertLessThan($step3Pos, $step2Pos);
    }
}
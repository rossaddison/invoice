<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Auth\Client\GovUkException;
use App\Auth\Client\OpenBankingClientException;
use App\Command\InstallCommandException;
use App\Command\Invoice\ItemsCommandException;
use App\Invoice\CompanyPrivate\CompanyPrivateException;
use App\Invoice\Generator\GeneratorException;
use App\Invoice\Helpers\Peppol\PeppolHelperException;
use App\Invoice\Helpers\ZugFerdHelperException;
use App\Invoice\Libraries\CryptorException;
use App\Invoice\PaymentInformation\Service\OpenBankingPaymentException;
use App\Invoice\Setting\SettingException;
use App\Invoice\Ubl\AttachmentException;
use PHPUnit\Framework\TestCase;

class DedicatedExceptionTest extends TestCase
{
    public function testAttachmentExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new AttachmentException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testPeppolHelperExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new PeppolHelperException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testGovUkExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new GovUkException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testOpenBankingClientExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new OpenBankingClientException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testInstallCommandExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new InstallCommandException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testItemsCommandExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new ItemsCommandException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testCompanyPrivateExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new CompanyPrivateException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testCryptorExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new CryptorException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testSettingExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new SettingException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testOpenBankingPaymentExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new OpenBankingPaymentException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testGeneratorExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new GeneratorException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testZugFerdHelperExceptionIsRuntimeException(): void
    {
        $previous = new \RuntimeException('previous');
        $e = new ZugFerdHelperException('test message', 42, $previous);

        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertSame('test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }
}

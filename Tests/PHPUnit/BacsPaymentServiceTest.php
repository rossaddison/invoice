<?php

declare(strict_types=1);

namespace Tests\PHPUnit;

use App\Infrastructure\Persistence\Company\Company;
use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository;
use App\Invoice\PaymentInformation\Service\BacsPaymentService;
use Cycle\ORM\Select;
use PHPUnit\Framework\TestCase;

class BacsPaymentServiceTest extends TestCase
{
    /**
     * Build a BacsPaymentService by providing a real CompanyPrivateRepository
     * constructed from a mocked (non-final) Cycle\ORM\Select.
     * The repository's query methods are not exercised by pure-logic tests.
     */
    private function makeRepo(?CompanyPrivate $cp = null): CompanyPrivateRepository
    {
        $select = $this->createStub(Select::class);

        // repoCompanyPrivateActive() calls $this->select()->load()->where()->fetchOne()
        // Chain fluent calls back to the same mock so fetchOne() returns $cp.
        $select->method('load')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('andWhere')->willReturnSelf();
        $select->method('fetchOne')->willReturn($cp);

        // CompanyPrivateRepository is final, and its constructor requires a final
        // EntityWriter. Bypass the constructor and inject only the Select mock into
        // the parent Select\Repository's protected $select property directly.
        $repo = (new \ReflectionClass(CompanyPrivateRepository::class))
            ->newInstanceWithoutConstructor();

        $prop = new \ReflectionProperty(\Cycle\ORM\Select\Repository::class, 'select');
        $prop->setValue($repo, $select);

        return $repo;
    }

    private function makeService(?CompanyPrivate $cp = null): BacsPaymentService
    {
        return new BacsPaymentService($this->makeRepo($cp));
    }

    private function makeCompanyPrivate(
        string $sortCode = '123456',
        string $accountNumber = '87654321',
        string $companyName = 'Acme Ltd',
    ): CompanyPrivate {
        $company = $this->createStub(Company::class);
        $company->method('getName')->willReturn($companyName);

        $cp = new CompanyPrivate();
        $cp->setBacsSortCode($sortCode);
        $cp->setBacsAccountNumber($accountNumber);
        $cp->setCompany($company);
        return $cp;
    }

    // ── isBacsConfigured ────────────────────────────────────────────────────

    public function testIsBacsConfiguredReturnsFalseWhenNoActiveRecord(): void
    {
        $this->assertFalse($this->makeService(null)->isBacsConfigured());
    }

    public function testIsBacsConfiguredReturnsFalseWhenSortCodeEmpty(): void
    {
        $this->assertFalse(
            $this->makeService($this->makeCompanyPrivate(sortCode: ''))->isBacsConfigured()
        );
    }

    public function testIsBacsConfiguredReturnsFalseWhenAccountNumberEmpty(): void
    {
        $this->assertFalse(
            $this->makeService($this->makeCompanyPrivate(accountNumber: ''))->isBacsConfigured()
        );
    }

    public function testIsBacsConfiguredReturnsTrueWhenBothSet(): void
    {
        $this->assertTrue($this->makeService($this->makeCompanyPrivate())->isBacsConfigured());
    }

    // ── getSortCode ──────────────────────────────────────────────────────────

    public function testGetSortCodeReturnsEmptyWhenNoActiveRecord(): void
    {
        $this->assertSame('', $this->makeService(null)->getSortCode());
    }

    public function testGetSortCodeReturnsValue(): void
    {
        $this->assertSame('112233', $this->makeService($this->makeCompanyPrivate(sortCode: '112233'))->getSortCode());
    }

    // ── getAccountNumber ─────────────────────────────────────────────────────

    public function testGetAccountNumberReturnsEmptyWhenNoActiveRecord(): void
    {
        $this->assertSame('', $this->makeService(null)->getAccountNumber());
    }

    public function testGetAccountNumberReturnsValue(): void
    {
        $this->assertSame('12345678', $this->makeService($this->makeCompanyPrivate(accountNumber: '12345678'))->getAccountNumber());
    }

    // ── getBeneficiaryName ───────────────────────────────────────────────────

    public function testGetBeneficiaryNameReturnsEmptyWhenNoActiveRecord(): void
    {
        $this->assertSame('', $this->makeService(null)->getBeneficiaryName());
    }

    public function testGetBeneficiaryNameReturnsCompanyName(): void
    {
        $this->assertSame('Test Corp', $this->makeService($this->makeCompanyPrivate(companyName: 'Test Corp'))->getBeneficiaryName());
    }

    public function testGetBeneficiaryNameReturnsEmptyWhenCompanyIsNull(): void
    {
        $cp = new CompanyPrivate();
        $cp->setBacsSortCode('123456');
        $cp->setBacsAccountNumber('12345678');
        $this->assertSame('', $this->makeService($cp)->getBeneficiaryName());
    }

    // ── generateReference ────────────────────────────────────────────────────

    public function testGenerateReferenceMatchesExpectedFormat(): void
    {
        $ref = $this->makeService()->generateReference(42);
        $this->assertMatchesRegularExpression('/^WIN-42-\d{6}$/', $ref);
    }

    public function testGenerateReferenceContainsCurrentYearMonth(): void
    {
        $ref = $this->makeService()->generateReference(1);
        $this->assertStringEndsWith('-' . date('Ym'), $ref);
    }

    public function testGenerateReferenceMaxLength(): void
    {
        $ref = $this->makeService()->generateReference(1234567);
        $this->assertLessThanOrEqual(18, strlen($ref));
    }

    // ── buildQrContent ───────────────────────────────────────────────────────

    public function testBuildQrContentContainsAllFields(): void
    {
        $service = $this->makeService($this->makeCompanyPrivate('123456', '87654321', 'Acme Ltd'));
        $content = $service->buildQrContent('INV-001', 99.99);

        $this->assertStringContainsString('Acme Ltd', $content);
        $this->assertStringContainsString('12-34-56', $content);
        $this->assertStringContainsString('87654321', $content);
        $this->assertStringContainsString('INV-001', $content);
        $this->assertStringContainsString('99.99', $content);
    }

    public function testBuildQrContentOmitsAmountLineWhenZero(): void
    {
        $service = $this->makeService($this->makeCompanyPrivate());
        $this->assertStringNotContainsString('Amount:', $service->buildQrContent('INV-002', 0.00));
    }

    public function testBuildQrContentUsesGbpByDefault(): void
    {
        $service = $this->makeService($this->makeCompanyPrivate());
        $this->assertStringContainsString('GBP', $service->buildQrContent('INV-003', 50.00));
    }

    public function testBuildQrContentRespectsCurrencyOverride(): void
    {
        $service = $this->makeService($this->makeCompanyPrivate());
        $content = $service->buildQrContent('INV-004', 50.00, 'EUR');
        $this->assertStringContainsString('EUR', $content);
        $this->assertStringNotContainsString('GBP', $content);
    }

    // ── sort-code formatting (via buildQrContent) ────────────────────────────

    public function testSortCodeFormattedWithDashesFrom6Digits(): void
    {
        $service = $this->makeService($this->makeCompanyPrivate(sortCode: '123456'));
        $this->assertStringContainsString('12-34-56', $service->buildQrContent('REF', 1.00));
    }

    public function testSortCodeAlreadyFormattedPassedThrough(): void
    {
        $service = $this->makeService($this->makeCompanyPrivate(sortCode: '12-34-56'));
        $this->assertStringContainsString('12-34-56', $service->buildQrContent('REF', 1.00));
    }

    public function testSortCodeWithNonSixDigitsPassedThroughRaw(): void
    {
        $service = $this->makeService($this->makeCompanyPrivate(sortCode: '12345'));
        $this->assertStringContainsString('12345', $service->buildQrContent('REF', 1.00));
    }

    // ── renderQrDataUri ──────────────────────────────────────────────────────

    public function testRenderQrDataUriReturnsNonEmptyString(): void
    {
        $this->assertNotEmpty($this->makeService()->renderQrDataUri('test payment data'));
    }

    public function testRenderQrDataUriReturnsSvgOrDataUri(): void
    {
        $uri = $this->makeService()->renderQrDataUri('Sort code: 12-34-56');
        $this->assertTrue(
            str_starts_with($uri, '<svg') || str_starts_with($uri, 'data:'),
            'Expected SVG markup or data URI, got: ' . substr($uri, 0, 40)
        );
    }
}

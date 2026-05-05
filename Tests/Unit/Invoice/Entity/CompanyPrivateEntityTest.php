<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Company\Company;
use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CompanyPrivateEntityTest extends TestCase
{
    public string $atUto8 = 'AT U12345678';

    public string $seqNumbers = '1234567890123';

    public function testConstructorWithDefaults(): void
    {
        $companyPrivate = new CompanyPrivate();

        $this->assertFalse($companyPrivate->hasIdentity());
        $this->assertSame('', $companyPrivate->getVatId());
        $this->assertSame('', $companyPrivate->getTaxCode());
        $this->assertSame('', $companyPrivate->getIban());
        $this->assertSame('', $companyPrivate->getGln());
        $this->assertSame('', $companyPrivate->getRcc());
        $this->assertSame('', $companyPrivate->getLogoFilename());
        $this->assertNull($companyPrivate->getLogoWidth());
        $this->assertNull($companyPrivate->getLogoHeight());
        $this->assertNull($companyPrivate->getLogoMargin());
        $this->assertNull($companyPrivate->getStartDate());
        $this->assertNull($companyPrivate->getEndDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $companyPrivate->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $companyPrivate->getDateModified());
        $this->assertNull($companyPrivate->getCompany());
    }

    public function testReqCompanyIdThrowsWhenNotPersisted(): void
    {
        $companyPrivate = new CompanyPrivate();
        $this->expectException(\LogicException::class);
        $companyPrivate->reqCompanyId();
    }

    public function testConstructorWithAllParameters(): void
    {
        $companyPrivate = new CompanyPrivate(
            id: 1,
            company_id: 123,
            vat_id: 'VAT123456789',
            tax_code: 'TAX987654321',
            iban: 'DE89370400440532013000',
            gln: $this->seqNumbers,
            rcc: 'RCC1234',
            logo_filename: 'company_logo.png',
            logo_width: 150,
            logo_height: 75,
            logo_margin: 15
        );

        $this->assertSame(1, $companyPrivate->reqId());
        $this->assertSame(123, $companyPrivate->reqCompanyId());
        $this->assertSame('VAT123456789', $companyPrivate->getVatId());
        $this->assertSame('TAX987654321', $companyPrivate->getTaxCode());
        $this->assertSame('DE89370400440532013000', $companyPrivate->getIban());
        $this->assertSame($this->seqNumbers, $companyPrivate->getGln());
        $this->assertSame('RCC1234', $companyPrivate->getRcc());
        $this->assertSame('company_logo.png', $companyPrivate->getLogoFilename());
        $this->assertSame(150, $companyPrivate->getLogoWidth());
        $this->assertSame(75, $companyPrivate->getLogoHeight());
        $this->assertSame(15, $companyPrivate->getLogoMargin());
    }

    public function testIdSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setId(100);

        $this->assertSame(100, $companyPrivate->reqId());
    }

    public function testCompanyIdSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setCompanyId(456);

        $this->assertSame(456, $companyPrivate->reqCompanyId());
    }

    public function testCompanySetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $company = $this->createMock(Company::class);
        $companyPrivate->setCompany($company);

        $this->assertSame($company, $companyPrivate->getCompany());
    }

    public function testVatIdSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setVatId('GB123456789');

        $this->assertSame('GB123456789', $companyPrivate->getVatId());
    }

    public function testTaxCodeSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setTaxCode('US987654321');

        $this->assertSame('US987654321', $companyPrivate->getTaxCode());
    }

    public function testIbanSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setIban('GB82WEST12345698765432');

        $this->assertSame('GB82WEST12345698765432', $companyPrivate->getIban());
    }

    public function testGlnSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setGln('9876543210987');

        $this->assertSame('9876543210987', $companyPrivate->getGln());
    }

    public function testRccSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setRcc('RCC5678');

        $this->assertSame('RCC5678', $companyPrivate->getRcc());
    }

    public function testLogoFilenameSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setLogoFilename('brand_logo.jpg');

        $this->assertSame('brand_logo.jpg', $companyPrivate->getLogoFilename());
    }

    public function testLogoWidthSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setLogoWidth(200);

        $this->assertSame(200, $companyPrivate->getLogoWidth());
    }

    public function testLogoHeightSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setLogoHeight(100);

        $this->assertSame(100, $companyPrivate->getLogoHeight());
    }

    public function testLogoMarginSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setLogoMargin(20);

        $this->assertSame(20, $companyPrivate->getLogoMargin());
    }

    public function testStartDateSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $startDate = new DateTime('2024-06-01');
        $companyPrivate->setStartDate($startDate);

        $result = $companyPrivate->getStartDate();
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2024-06-01', $result->format('Y-m-d'));
    }

    public function testEndDateSetterAndGetter(): void
    {
        $companyPrivate = new CompanyPrivate();
        $endDate = new DateTime('2024-12-31');
        $companyPrivate->setEndDate($endDate);

        $result = $companyPrivate->getEndDate();
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2024-12-31', $result->format('Y-m-d'));
    }

    public function testhasIdentity(): void
    {
        $companyPrivate = new CompanyPrivate();
        $this->assertFalse($companyPrivate->hasIdentity());

        $companyPrivate->setId(1);
        $this->assertTrue($companyPrivate->hasIdentity());
    }

    public function testIsActiveTodayWithNullDates(): void
    {
        $companyPrivate = new CompanyPrivate();
        $this->assertFalse($companyPrivate->isActiveToday());
    }

    public function testIsActiveTodayWithPastRange(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setStartDate(new DateTime('2023-01-01'));
        $companyPrivate->setEndDate(new DateTime('2023-12-31'));

        $this->assertFalse($companyPrivate->isActiveToday());
    }

    public function testIsActiveTodayWithCurrentRange(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setStartDate(new DateTime('yesterday'));
        $companyPrivate->setEndDate(new DateTime('tomorrow'));

        $this->assertTrue($companyPrivate->isActiveToday());
    }

    public function testIsActiveTodayStartsToday(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setStartDate(new DateTime('today'));
        $companyPrivate->setEndDate(new DateTime('tomorrow'));

        $this->assertTrue($companyPrivate->isActiveToday());
    }

    public function testIsActiveTodayEndsToday(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setStartDate(new DateTime('yesterday'));
        $companyPrivate->setEndDate(new DateTime('today'));

        $this->assertTrue($companyPrivate->isActiveToday());
    }

    public function testIsActiveTodayWithFutureRange(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setStartDate(new DateTime('+1 day'));
        $companyPrivate->setEndDate(new DateTime('+30 days'));

        $this->assertFalse($companyPrivate->isActiveToday());
    }

    public function testIsActiveTodayWithNullStart(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setEndDate(new DateTime('tomorrow'));

        $this->assertFalse($companyPrivate->isActiveToday());
    }

    public function testIsActiveTodayWithNullEnd(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setStartDate(new DateTime('yesterday'));

        $this->assertFalse($companyPrivate->isActiveToday());
    }

    public function testDateTimeImmutableProperties(): void
    {
        $companyPrivate = new CompanyPrivate();

        $dateCreated = $companyPrivate->getDateCreated();
        $dateModified = $companyPrivate->getDateModified();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateCreated);
        $this->assertInstanceOf(DateTimeImmutable::class, $dateModified);
        $this->assertLessThanOrEqual(time(), $dateCreated->getTimestamp());
        $this->assertLessThanOrEqual(time(), $dateModified->getTimestamp());
    }

    public function testEuropeanCompanyScenario(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setVatId('DE123456789');
        $companyPrivate->setIban('DE89370400440532013000');
        $companyPrivate->setGln('4012345678901');
        $companyPrivate->setLogoFilename('eu_company_logo.png');
        $companyPrivate->setLogoWidth(120);
        $companyPrivate->setLogoHeight(60);

        $this->assertSame('DE123456789', $companyPrivate->getVatId());
        $this->assertSame('DE89370400440532013000', $companyPrivate->getIban());
        $this->assertSame('4012345678901', $companyPrivate->getGln());
        $this->assertSame('eu_company_logo.png', $companyPrivate->getLogoFilename());
        $this->assertSame(120, $companyPrivate->getLogoWidth());
        $this->assertSame(60, $companyPrivate->getLogoHeight());
    }

    public function testUkCompanyScenario(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setVatId('GB999999999');
        $companyPrivate->setIban('GB82WEST12345698765432');
        $companyPrivate->setTaxCode('UTR1234567890');
        $companyPrivate->setRcc('12345');

        $this->assertSame('GB999999999', $companyPrivate->getVatId());
        $this->assertSame('GB82WEST12345698765432', $companyPrivate->getIban());
        $this->assertSame('UTR1234567890', $companyPrivate->getTaxCode());
        $this->assertSame('12345', $companyPrivate->getRcc());
    }

    public function testUsCompanyScenario(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setVatId('');
        $companyPrivate->setTaxCode('123456789');
        $companyPrivate->setLogoFilename('us_corp_logo.svg');
        $companyPrivate->setLogoWidth(200);
        $companyPrivate->setLogoHeight(50);

        $this->assertSame('', $companyPrivate->getVatId());
        $this->assertSame('123456789', $companyPrivate->getTaxCode());
        $this->assertSame('us_corp_logo.svg', $companyPrivate->getLogoFilename());
        $this->assertSame(200, $companyPrivate->getLogoWidth());
        $this->assertSame(50, $companyPrivate->getLogoHeight());
    }

    public function testLogoConfigurationVariations(): void
    {
        $companyPrivate = new CompanyPrivate();

        $companyPrivate->setLogoFilename('small_logo.png');
        $companyPrivate->setLogoWidth(50);
        $companyPrivate->setLogoHeight(25);
        $companyPrivate->setLogoMargin(5);

        $this->assertSame('small_logo.png', $companyPrivate->getLogoFilename());
        $this->assertSame(50, $companyPrivate->getLogoWidth());
        $this->assertSame(25, $companyPrivate->getLogoHeight());
        $this->assertSame(5, $companyPrivate->getLogoMargin());

        $companyPrivate->setLogoFilename('large_logo.jpg');
        $companyPrivate->setLogoWidth(300);
        $companyPrivate->setLogoHeight(150);
        $companyPrivate->setLogoMargin(30);

        $this->assertSame('large_logo.jpg', $companyPrivate->getLogoFilename());
        $this->assertSame(300, $companyPrivate->getLogoWidth());
        $this->assertSame(150, $companyPrivate->getLogoHeight());
        $this->assertSame(30, $companyPrivate->getLogoMargin());
    }

    public function testVatIdFormats(): void
    {
        $companyPrivate = new CompanyPrivate();

        $vatFormats = [
            'DE123456789',
            'FR12345678901',
            'IT12345678901',
            'ES12345678Z',
            'NL123456789B01',
            'BE0123456789',
            $this->atUto8
        ];

        foreach ($vatFormats as $vatId) {
            $companyPrivate->setVatId($vatId);
            $this->assertSame($vatId, $companyPrivate->getVatId());
        }
    }

    public function testIbanFormats(): void
    {
        $companyPrivate = new CompanyPrivate();

        $ibanFormats = [
            'DE89370400440532013000',
            'GB82WEST12345698765432',
            'FR1420041010050500013M02606',
            'IT60X0542811101000000123456',
            'ES9121000418450200051332'
        ];

        foreach ($ibanFormats as $iban) {
            $companyPrivate->setIban($iban);
            $this->assertSame($iban, $companyPrivate->getIban());
        }
    }

    public function testSpecialCharactersInFields(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setVatId($this->atUto8);
        $companyPrivate->setTaxCode('TAX-CODE-123');
        $companyPrivate->setLogoFilename('logo-with-dashes.png');

        $this->assertSame($this->atUto8, $companyPrivate->getVatId());
        $this->assertSame('TAX-CODE-123', $companyPrivate->getTaxCode());
        $this->assertSame('logo-with-dashes.png', $companyPrivate->getLogoFilename());
    }

    public function testCompleteCompanyPrivateSetup(): void
    {
        $companyPrivate = new CompanyPrivate();
        $company = $this->createMock(Company::class);

        $companyPrivate->setId(1);
        $companyPrivate->setCompanyId(100);
        $companyPrivate->setCompany($company);
        $companyPrivate->setVatId('DE987654321');
        $companyPrivate->setTaxCode('TAX12345');
        $companyPrivate->setIban('DE89370400440532013000');
        $companyPrivate->setGln($this->seqNumbers);
        $companyPrivate->setRcc('RCC9876');
        $companyPrivate->setLogoFilename('complete_logo.png');
        $companyPrivate->setLogoWidth(160);
        $companyPrivate->setLogoHeight(80);
        $companyPrivate->setLogoMargin(12);
        $companyPrivate->setStartDate(new DateTime('2024-01-01'));
        $companyPrivate->setEndDate(new DateTime('2024-12-31'));

        $this->assertSame(1, $companyPrivate->reqId());
        $this->assertSame(100, $companyPrivate->reqCompanyId());
        $this->assertSame($company, $companyPrivate->getCompany());
        $this->assertSame('DE987654321', $companyPrivate->getVatId());
        $this->assertSame('TAX12345', $companyPrivate->getTaxCode());
        $this->assertSame('DE89370400440532013000', $companyPrivate->getIban());
        $this->assertSame($this->seqNumbers, $companyPrivate->getGln());
        $this->assertSame('RCC9876', $companyPrivate->getRcc());
        $this->assertSame('complete_logo.png', $companyPrivate->getLogoFilename());
        $this->assertSame(160, $companyPrivate->getLogoWidth());
        $this->assertSame(80, $companyPrivate->getLogoHeight());
        $this->assertSame(12, $companyPrivate->getLogoMargin());
        $this->assertSame('2024-01-01', $companyPrivate->getStartDate()?->format('Y-m-d'));
        $this->assertSame('2024-12-31', $companyPrivate->getEndDate()?->format('Y-m-d'));
        $this->assertTrue($companyPrivate->hasIdentity());
    }

    public function testGetterMethodsConsistency(): void
    {
        $companyPrivate = new CompanyPrivate(
            id: 1,
            company_id: 123,
            vat_id: 'VAT123',
            tax_code: 'TAX456',
            iban: 'DE89370400440532013000',
            gln: $this->seqNumbers,
            rcc: 'RCC789',
            logo_filename: 'test_logo.png',
            logo_width: 100,
            logo_height: 50,
            logo_margin: 10
        );

        $this->assertIsInt($companyPrivate->reqId());
        $this->assertIsInt($companyPrivate->reqCompanyId());
        $this->assertIsString($companyPrivate->getVatId());
        $this->assertIsString($companyPrivate->getTaxCode());
        $this->assertIsString($companyPrivate->getIban());
        $this->assertIsString($companyPrivate->getGln());
        $this->assertIsString($companyPrivate->getRcc());
        $this->assertIsString($companyPrivate->getLogoFilename());
        $this->assertIsInt($companyPrivate->getLogoWidth());
        $this->assertIsInt($companyPrivate->getLogoHeight());
        $this->assertIsInt($companyPrivate->getLogoMargin());
        $this->assertInstanceOf(DateTimeImmutable::class, $companyPrivate->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $companyPrivate->getDateModified());
    }

    public function testNullDateHandling(): void
    {
        $companyPrivate = new CompanyPrivate();

        $companyPrivate->setStartDate(null);
        $companyPrivate->setEndDate(null);

        $this->assertNull($companyPrivate->getStartDate());
        $this->assertNull($companyPrivate->getEndDate());
        $this->assertFalse($companyPrivate->isActiveToday());
    }

    public function testCompanyRelationshipManagement(): void
    {
        $companyPrivate = new CompanyPrivate();
        $company1 = $this->createMock(Company::class);
        $company2 = $this->createMock(Company::class);

        $companyPrivate->setCompanyId(100);
        $companyPrivate->setCompany($company1);
        $this->assertSame($company1, $companyPrivate->getCompany());

        $companyPrivate->setCompanyId(200);
        $companyPrivate->setCompany($company2);
        $this->assertSame($company2, $companyPrivate->getCompany());
    }

    public function testTimezoneHandling(): void
    {
        $beforeTime = time();
        $companyPrivate = new CompanyPrivate();
        $afterTime = time();

        $createdTime = $companyPrivate->getDateCreated()->getTimestamp();
        $modifiedTime = $companyPrivate->getDateModified()->getTimestamp();

        $this->assertGreaterThanOrEqual($beforeTime, $createdTime);
        $this->assertLessThanOrEqual($afterTime, $createdTime);
        $this->assertGreaterThanOrEqual($beforeTime, $modifiedTime);
        $this->assertLessThanOrEqual($afterTime, $modifiedTime);
    }

    public function testEntityStateAfterConstruction(): void
    {
        $companyPrivate = new CompanyPrivate();

        $this->assertFalse($companyPrivate->hasIdentity());
        $this->assertNull($companyPrivate->getCompany());
        $this->assertInstanceOf(DateTimeImmutable::class, $companyPrivate->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $companyPrivate->getDateModified());
    }

    public function testLogoZeroDimensions(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setLogoWidth(0);
        $companyPrivate->setLogoHeight(0);
        $companyPrivate->setLogoMargin(0);

        $this->assertSame(0, $companyPrivate->getLogoWidth());
        $this->assertSame(0, $companyPrivate->getLogoHeight());
        $this->assertSame(0, $companyPrivate->getLogoMargin());
    }

    public function testNegativeCompanyId(): void
    {
        $companyPrivate = new CompanyPrivate();
        $companyPrivate->setCompanyId(-1);

        $this->assertSame(-1, $companyPrivate->reqCompanyId());
    }
}

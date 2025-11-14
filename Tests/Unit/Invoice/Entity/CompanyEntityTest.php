<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\Company;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class CompanyEntityTest extends TestCase
{
    public string $unitedStates = 'United States';
    
    public function testConstructorWithDefaults(): void
    {
        $company = new Company();
        
        $this->assertNull($company->getId());
        $this->assertSame(0, $company->getCurrent());
        $this->assertSame('', $company->getName());
        $this->assertSame('', $company->getAddress_1());
        $this->assertSame('', $company->getAddress_2());
        $this->assertSame('', $company->getCity());
        $this->assertSame('', $company->getState());
        $this->assertSame('', $company->getZip());
        $this->assertSame('', $company->getCountry());
        $this->assertSame('', $company->getPhone());
        $this->assertSame('', $company->getFax());
        $this->assertSame('', $company->getEmail());
        $this->assertSame('', $company->getWeb());
        $this->assertSame('', $company->getSlack());
        $this->assertSame('', $company->getFacebook());
        $this->assertSame('', $company->getTwitter());
        $this->assertSame('', $company->getLinkedIn());
        $this->assertSame('', $company->getWhatsapp());
        $this->assertSame('', $company->getArbitrationBody());
        $this->assertSame('', $company->getArbitrationJurisdiction());
        $this->assertInstanceOf(DateTimeImmutable::class, $company->getDate_created());
        $this->assertInstanceOf(DateTimeImmutable::class, $company->getDate_modified());
        $this->assertInstanceOf(ArrayCollection::class, $company->getCompanyPrivates());
        $this->assertTrue($company->isNewRecord());
    }

    public function testConstructorWithAllParameters(): void
    {
        $company = new Company(
            id: 1,
            current: 1,
            name: 'Tech Solutions Inc.',
            address_1: '123 Business Ave',
            address_2: 'Suite 200',
            city: 'Tech City',
            state: 'CA',
            zip: '90210',
            country: 'USA',
            phone: '+1-555-123-4567',
            fax: '+1-555-123-4568',
            email: 'info@techsolutions.com',
            web: 'https://techsolutions.com',
            slack: 'techsolutions.slack.com',
            facebook: 'facebook.com/techsolutions',
            twitter: '@techsolutions',
            linkedin: 'linkedin.com/company/techsolutions',
            whatsapp: '+1-555-123-4569',
            arbitrationBody: 'American Arbitration Association',
            arbitrationJurisdiction: 'State of California'
        );
        
        $this->assertSame(1, $company->getId());
        $this->assertSame(1, $company->getCurrent());
        $this->assertSame('Tech Solutions Inc.', $company->getName());
        $this->assertSame('123 Business Ave', $company->getAddress_1());
        $this->assertSame('Suite 200', $company->getAddress_2());
        $this->assertSame('Tech City', $company->getCity());
        $this->assertSame('CA', $company->getState());
        $this->assertSame('90210', $company->getZip());
        $this->assertSame('USA', $company->getCountry());
        $this->assertSame('+1-555-123-4567', $company->getPhone());
        $this->assertSame('+1-555-123-4568', $company->getFax());
        $this->assertSame('info@techsolutions.com', $company->getEmail());
        $this->assertSame('https://techsolutions.com', $company->getWeb());
        $this->assertSame('techsolutions.slack.com', $company->getSlack());
        $this->assertSame('facebook.com/techsolutions', $company->getFacebook());
        $this->assertSame('@techsolutions', $company->getTwitter());
        $this->assertSame('linkedin.com/company/techsolutions', $company->getLinkedIn());
        $this->assertSame('+1-555-123-4569', $company->getWhatsapp());
        $this->assertSame('American Arbitration Association', $company->getArbitrationBody());
        $this->assertSame('State of California', $company->getArbitrationJurisdiction());
    }

    public function testIdSetterAndGetter(): void
    {
        $company = new Company();
        $company->setId(100);
        
        $this->assertSame(100, $company->getId());
    }

    public function testCurrentSetterAndGetter(): void
    {
        $company = new Company();
        $company->setCurrent(1);
        
        $this->assertSame(1, $company->getCurrent());
    }

    public function testNameSetterAndGetter(): void
    {
        $company = new Company();
        $company->setName('Global Enterprise LLC');
        
        $this->assertSame('Global Enterprise LLC', $company->getName());
    }

    public function testAddress1SetterAndGetter(): void
    {
        $company = new Company();
        $company->setAddress_1('456 Corporate Blvd');
        
        $this->assertSame('456 Corporate Blvd', $company->getAddress_1());
    }

    public function testAddress2SetterAndGetter(): void
    {
        $company = new Company();
        $company->setAddress_2('Floor 15');
        
        $this->assertSame('Floor 15', $company->getAddress_2());
    }

    public function testCitySetterAndGetter(): void
    {
        $company = new Company();
        $company->setCity('Business City');
        
        $this->assertSame('Business City', $company->getCity());
    }

    public function testStateSetterAndGetter(): void
    {
        $company = new Company();
        $company->setState('New York');
        
        $this->assertSame('New York', $company->getState());
    }

    public function testZipSetterAndGetter(): void
    {
        $company = new Company();
        $company->setZip('10001');
        
        $this->assertSame('10001', $company->getZip());
    }

    public function testCountrySetterAndGetter(): void
    {
        $company = new Company();
        $company->setCountry($this->unitedStates);
        
        $this->assertSame($this->unitedStates, $company->getCountry());
    }

    public function testPhoneSetterAndGetter(): void
    {
        $company = new Company();
        $company->setPhone('+1-800-555-0199');
        
        $this->assertSame('+1-800-555-0199', $company->getPhone());
    }

    public function testFaxSetterAndGetter(): void
    {
        $company = new Company();
        $company->setFax('+1-800-555-0200');
        
        $this->assertSame('+1-800-555-0200', $company->getFax());
    }

    public function testEmailSetterAndGetter(): void
    {
        $company = new Company();
        $company->setEmail('contact@company.com');
        
        $this->assertSame('contact@company.com', $company->getEmail());
    }

    public function testWebSetterAndGetter(): void
    {
        $company = new Company();
        $company->setWeb('https://www.company.com');
        
        $this->assertSame('https://www.company.com', $company->getWeb());
    }

    public function testSlackSetterAndGetter(): void
    {
        $company = new Company();
        $company->setSlack('company.slack.com');
        
        $this->assertSame('company.slack.com', $company->getSlack());
    }

    public function testTwitterSetterAndGetter(): void
    {
        $company = new Company();
        $company->setTwitter('@company');
        
        $this->assertSame('@company', $company->getTwitter());
    }

    public function testFacebookSetterAndGetter(): void
    {
        $company = new Company();
        $company->setFacebook('facebook.com/company');
        
        $this->assertSame('facebook.com/company', $company->getFacebook());
    }

    public function testLinkedInSetterAndGetter(): void
    {
        $company = new Company();
        $company->setLinkedIn('linkedin.com/company/mycompany');
        
        $this->assertSame('linkedin.com/company/mycompany', $company->getLinkedIn());
    }

    public function testWhatsappSetterAndGetter(): void
    {
        $company = new Company();
        $company->setWhatsapp('+1-555-WHATSAPP');
        
        $this->assertSame('+1-555-WHATSAPP', $company->getWhatsapp());
    }

    public function testArbitrationBodySetterAndGetter(): void
    {
        $company = new Company();
        $company->setArbitrationBody('International Chamber of Commerce');
        
        $this->assertSame('International Chamber of Commerce', $company->getArbitrationBody());
    }

    public function testArbitrationJurisdictionSetterAndGetter(): void
    {
        $company = new Company();
        $company->setArbitrationJurisdiction('New York State');
        
        $this->assertSame('New York State', $company->getArbitrationJurisdiction());
    }

    public function testIsNewRecord(): void
    {
        $company = new Company();
        $this->assertTrue($company->isNewRecord());
        
        $company->setId(1);
        $this->assertFalse($company->isNewRecord());
    }

    public function testDateTimeImmutableProperties(): void
    {
        $company = new Company();
        
        $dateCreated = $company->getDate_created();
        $dateModified = $company->getDate_modified();
        
        $this->assertInstanceOf(DateTimeImmutable::class, $dateCreated);
        $this->assertInstanceOf(DateTimeImmutable::class, $dateModified);
        $this->assertLessThanOrEqual(time(), $dateCreated->getTimestamp());
        $this->assertLessThanOrEqual(time(), $dateModified->getTimestamp());
    }

    public function testCompanyPrivatesCollection(): void
    {
        $company = new Company();
        $companyPrivates = $company->getCompanyPrivates();
        
        $this->assertInstanceOf(ArrayCollection::class, $companyPrivates);
        $this->assertCount(0, $companyPrivates);
        
        // Test that it's initialized as empty collection
        $this->assertTrue($companyPrivates->isEmpty());
    }

    public function testTechCompanyScenario(): void
    {
        $company = new Company();
        $company->setName('TechCorp Solutions');
        $company->setAddress_1('100 Silicon Valley Drive');
        $company->setCity('Palo Alto');
        $company->setState('California');
        $company->setZip('94301');
        $company->setCountry('USA');
        $company->setPhone('+1-650-555-0100');
        $company->setEmail('hello@techcorp.com');
        $company->setWeb('https://techcorp.com');
        $company->setTwitter('@techcorp');
        $company->setLinkedIn('linkedin.com/company/techcorp');
        
        $this->assertSame('TechCorp Solutions', $company->getName());
        $this->assertSame('100 Silicon Valley Drive', $company->getAddress_1());
        $this->assertSame('Palo Alto', $company->getCity());
        $this->assertSame('California', $company->getState());
        $this->assertSame('94301', $company->getZip());
        $this->assertSame('USA', $company->getCountry());
        $this->assertSame('+1-650-555-0100', $company->getPhone());
        $this->assertSame('hello@techcorp.com', $company->getEmail());
        $this->assertSame('https://techcorp.com', $company->getWeb());
        $this->assertSame('@techcorp', $company->getTwitter());
        $this->assertSame('linkedin.com/company/techcorp', $company->getLinkedIn());
    }

    public function testManufacturingCompanyScenario(): void
    {
        $company = new Company();
        $company->setName('Industrial Manufacturing Co.');
        $company->setAddress_1('500 Factory Road');
        $company->setAddress_2('Building A');
        $company->setCity('Detroit');
        $company->setState('Michigan');
        $company->setZip('48201');
        $company->setCountry($this->unitedStates);
        $company->setPhone('+1-313-555-0200');
        $company->setFax('+1-313-555-0201');
        $company->setEmail('orders@manufacturing.com');
        
        $this->assertSame('Industrial Manufacturing Co.', $company->getName());
        $this->assertSame('500 Factory Road', $company->getAddress_1());
        $this->assertSame('Building A', $company->getAddress_2());
        $this->assertSame('Detroit', $company->getCity());
        $this->assertSame('Michigan', $company->getState());
        $this->assertSame('48201', $company->getZip());
        $this->assertSame($this->unitedStates, $company->getCountry());
        $this->assertSame('+1-313-555-0200', $company->getPhone());
        $this->assertSame('+1-313-555-0201', $company->getFax());
        $this->assertSame('orders@manufacturing.com', $company->getEmail());
    }

    public function testInternationalCompanyScenario(): void
    {
        $company = new Company();
        $company->setName('Global Consulting GmbH');
        $company->setAddress_1('Friedrichstraße 123');
        $company->setCity('Berlin');
        $company->setZip('10117');
        $company->setCountry('Germany');
        $company->setPhone('+49-30-12345678');
        $company->setEmail('info@globalconsulting.de');
        $company->setWeb('https://globalconsulting.de');
        
        $this->assertSame('Global Consulting GmbH', $company->getName());
        $this->assertSame('Friedrichstraße 123', $company->getAddress_1());
        $this->assertSame('Berlin', $company->getCity());
        $this->assertSame('10117', $company->getZip());
        $this->assertSame('Germany', $company->getCountry());
        $this->assertSame('+49-30-12345678', $company->getPhone());
        $this->assertSame('info@globalconsulting.de', $company->getEmail());
        $this->assertSame('https://globalconsulting.de', $company->getWeb());
    }

    public function testLongCompanyFields(): void
    {
        $longName = str_repeat('Very Long Company Name With Extended Details ', 10);
        $longAddress = str_repeat('Super Extended Business Address With Many Additional Details ', 8);
        $longArbitration = str_repeat('Extended Arbitration Body Name With Full Legal Description ', 5);
        
        $company = new Company();
        $company->setName($longName);
        $company->setAddress_1($longAddress);
        $company->setArbitrationBody($longArbitration);
        
        $this->assertSame($longName, $company->getName());
        $this->assertSame($longAddress, $company->getAddress_1());
        $this->assertSame($longArbitration, $company->getArbitrationBody());
    }

    public function testSpecialCharactersInCompanyFields(): void
    {
        $company = new Company();
        $company->setName('Müller & Associates, Inc.');
        $company->setAddress_1('123 Résidence Street');
        $company->setEmail('müller@company.com');
        $company->setWeb('https://müller-associates.com');
        
        $this->assertSame('Müller & Associates, Inc.', $company->getName());
        $this->assertSame('123 Résidence Street', $company->getAddress_1());
        $this->assertSame('müller@company.com', $company->getEmail());
        $this->assertSame('https://müller-associates.com', $company->getWeb());
    }

    public function testUnicodeCharactersInCompanyFields(): void
    {
        $company = new Company();
        $company->setName('株式会社テクノロジー');
        $company->setAddress_1('東京都新宿区西新宿1-1-1');
        $company->setCity('東京');
        $company->setCountry('日本');
        $company->setEmail('info@テクノロジー.jp');
        
        $this->assertSame('株式会社テクノロジー', $company->getName());
        $this->assertSame('東京都新宿区西新宿1-1-1', $company->getAddress_1());
        $this->assertSame('東京', $company->getCity());
        $this->assertSame('日本', $company->getCountry());
        $this->assertSame('info@テクノロジー.jp', $company->getEmail());
    }

    public function testSocialMediaHandles(): void
    {
        $company = new Company();
        $company->setTwitter('@company_official');
        $company->setFacebook('facebook.com/company.official');
        $company->setLinkedIn('linkedin.com/company/company-official');
        $company->setSlack('company-team.slack.com');
        $company->setWhatsapp('+1-555-COMPANY');
        
        $this->assertSame('@company_official', $company->getTwitter());
        $this->assertSame('facebook.com/company.official', $company->getFacebook());
        $this->assertSame('linkedin.com/company/company-official', $company->getLinkedIn());
        $this->assertSame('company-team.slack.com', $company->getSlack());
        $this->assertSame('+1-555-COMPANY', $company->getWhatsapp());
    }

    public function testArbitrationInformation(): void
    {
        $company = new Company();
        $company->setArbitrationBody('London Court of International Arbitration');
        $company->setArbitrationJurisdiction('English Law');
        
        $this->assertSame('London Court of International Arbitration', $company->getArbitrationBody());
        $this->assertSame('English Law', $company->getArbitrationJurisdiction());
    }

    public function testCompleteCompanySetup(): void
    {
        $company = new Company();
        
        $company->setId(1);
        $company->setCurrent(1);
        $company->setName('Complete Business Solutions Ltd.');
        $company->setAddress_1('789 Enterprise Way');
        $company->setAddress_2('Executive Suite');
        $company->setCity('Business District');
        $company->setState('Business State');
        $company->setZip('12345');
        $company->setCountry('Business Country');
        $company->setPhone('+1-555-BUSINESS');
        $company->setFax('+1-555-BIZFAX');
        $company->setEmail('contact@business.com');
        $company->setWeb('https://business.com');
        $company->setSlack('business.slack.com');
        $company->setFacebook('facebook.com/business');
        $company->setTwitter('@business');
        $company->setLinkedIn('linkedin.com/company/business');
        $company->setWhatsapp('+1-555-BIZWHAT');
        $company->setArbitrationBody('Business Arbitration Panel');
        $company->setArbitrationJurisdiction('Business Law');
        
        $this->assertSame(1, $company->getId());
        $this->assertSame(1, $company->getCurrent());
        $this->assertSame('Complete Business Solutions Ltd.', $company->getName());
        $this->assertSame('789 Enterprise Way', $company->getAddress_1());
        $this->assertSame('Executive Suite', $company->getAddress_2());
        $this->assertSame('Business District', $company->getCity());
        $this->assertSame('Business State', $company->getState());
        $this->assertSame('12345', $company->getZip());
        $this->assertSame('Business Country', $company->getCountry());
        $this->assertSame('+1-555-BUSINESS', $company->getPhone());
        $this->assertSame('+1-555-BIZFAX', $company->getFax());
        $this->assertSame('contact@business.com', $company->getEmail());
        $this->assertSame('https://business.com', $company->getWeb());
        $this->assertSame('business.slack.com', $company->getSlack());
        $this->assertSame('facebook.com/business', $company->getFacebook());
        $this->assertSame('@business', $company->getTwitter());
        $this->assertSame('linkedin.com/company/business', $company->getLinkedIn());
        $this->assertSame('+1-555-BIZWHAT', $company->getWhatsapp());
        $this->assertSame('Business Arbitration Panel', $company->getArbitrationBody());
        $this->assertSame('Business Law', $company->getArbitrationJurisdiction());
        $this->assertFalse($company->isNewRecord());
    }

    public function testGetterMethodsConsistency(): void
    {
        $company = new Company(
            id: 1,
            current: 1,
            name: 'Test Company',
            address_1: 'Test Address',
            city: 'Test City',
            phone: 'Test Phone',
            email: 'test@company.com'
        );
        
        $this->assertIsInt($company->getId());
        $this->assertIsInt($company->getCurrent());
        $this->assertIsString($company->getName());
        $this->assertIsString($company->getAddress_1());
        $this->assertIsString($company->getCity());
        $this->assertIsString($company->getPhone());
        $this->assertIsString($company->getEmail());
        $this->assertInstanceOf(DateTimeImmutable::class, $company->getDate_created());
        $this->assertInstanceOf(DateTimeImmutable::class, $company->getDate_modified());
        $this->assertInstanceOf(ArrayCollection::class, $company->getCompanyPrivates());
    }

    public function testCurrentFlagValues(): void
    {
        $company = new Company();
        
        // Test default
        $this->assertSame(0, $company->getCurrent());
        
        // Test setting to current
        $company->setCurrent(1);
        $this->assertSame(1, $company->getCurrent());
        
        // Test setting back to not current
        $company->setCurrent(0);
        $this->assertSame(0, $company->getCurrent());
    }

    public function testContactInformation(): void
    {
        $company = new Company();
        
        // Test various phone number formats
        $company->setPhone('+1 (555) 123-4567');
        $this->assertSame('+1 (555) 123-4567', $company->getPhone());
        
        $company->setPhone('555.123.4567');
        $this->assertSame('555.123.4567', $company->getPhone());
        
        // Test email formats
        $company->setEmail('info@company.co.uk');
        $this->assertSame('info@company.co.uk', $company->getEmail());
        
        // Test website URLs
        $company->setWeb('https://www.company.com/');
        $this->assertSame('https://www.company.com/', $company->getWeb());
        
        $company->setWeb('http://company.org');
        $this->assertSame('http://company.org', $company->getWeb());
    }

    public function testPropertyTypes(): void
    {
        $company = new Company(
            id: 1,
            current: 1
        );
        
        $this->assertIsInt($company->getId());
        $this->assertIsInt($company->getCurrent());
        $this->assertInstanceOf(DateTimeImmutable::class, $company->getDate_created());
        $this->assertInstanceOf(DateTimeImmutable::class, $company->getDate_modified());
        $this->assertInstanceOf(ArrayCollection::class, $company->getCompanyPrivates());
    }

    public function testCompanyWorkflow(): void
    {
        // Create new company
        $company = new Company();
        $this->assertTrue($company->isNewRecord());
        
        // Set company details
        $company->setName('Workflow Test Company');
        $company->setAddress_1('123 Test Street');
        $company->setCity('Test City');
        
        // Still new until ID is set
        $this->assertTrue($company->isNewRecord());
        
        // Assign ID (simulating database save)
        $company->setId(1);
        $this->assertFalse($company->isNewRecord());
        
        // Update company details
        $company->setName('Updated Company Name');
        $this->assertSame('Updated Company Name', $company->getName());
        $this->assertFalse($company->isNewRecord());
    }

    public function testTimezoneHandling(): void
    {
        $beforeTime = time();
        $company = new Company();
        $afterTime = time();
        
        $createdTime = $company->getDate_created()->getTimestamp();
        $modifiedTime = $company->getDate_modified()->getTimestamp();
        
        $this->assertGreaterThanOrEqual($beforeTime, $createdTime);
        $this->assertLessThanOrEqual($afterTime, $createdTime);
        $this->assertGreaterThanOrEqual($beforeTime, $modifiedTime);
        $this->assertLessThanOrEqual($afterTime, $modifiedTime);
    }

    public function testEntityStateAfterConstruction(): void
    {
        $company = new Company();
        
        $this->assertTrue($company->isNewRecord());
        $this->assertInstanceOf(ArrayCollection::class, $company->getCompanyPrivates());
        $this->assertTrue($company->getCompanyPrivates()->isEmpty());
        $this->assertInstanceOf(DateTimeImmutable::class, $company->getDate_created());
        $this->assertInstanceOf(DateTimeImmutable::class, $company->getDate_modified());
    }
}

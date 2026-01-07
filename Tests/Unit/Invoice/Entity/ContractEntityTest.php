<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\Client;
use App\Invoice\Entity\Contract;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ContractEntityTest extends TestCase
{
    public string $beg2024 = '2024-01-01';
    
    public string $end2024 = '2024-12-31';
    
    public function testConstructorWithDefaults(): void
    {
        $contract = new Contract();
        
        $this->assertNull($contract->getId());
        $this->assertSame('', $contract->getName());
        $this->assertSame('', $contract->getReference());
        $this->assertSame('', $contract->getClient_id());
        $this->assertNull($contract->getClient());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriod_start());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriod_end());
        $this->assertTrue($contract->isNewRecord());
    }

    public function testConstructorWithAllParameters(): void
    {
        $contract = new Contract(
            name: 'Annual Service Contract',
            reference: 'ASC-2024-001',
            client_id: 123
        );
        
        $this->assertNull($contract->getId());
        $this->assertSame('Annual Service Contract', $contract->getName());
        $this->assertSame('ASC-2024-001', $contract->getReference());
        $this->assertSame('123', $contract->getClient_id());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriod_start());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriod_end());
    }

    public function testClientIdSetterAndGetter(): void
    {
        $contract = new Contract();
        $contract->setClient_id(456);
        
        $this->assertSame('456', $contract->getClient_id());
    }

    public function testClientSetterAndGetter(): void
    {
        $contract = new Contract();
        $client = $this->createMock(Client::class);
        $contract->setClient($client);
        
        $this->assertSame($client, $contract->getClient());
    }

    public function testNameSetterAndGetter(): void
    {
        $contract = new Contract();
        $contract->setName('Software Maintenance Contract');
        
        $this->assertSame('Software Maintenance Contract', $contract->getName());
    }

    public function testReferenceSetterAndGetter(): void
    {
        $contract = new Contract();
        $contract->setReference('SMC-2024-002');
        
        $this->assertSame('SMC-2024-002', $contract->getReference());
    }

    public function testPeriodStartSetterAndGetter(): void
    {
        $contract = new Contract();
        $startDate = new DateTimeImmutable($this->beg2024);
        $contract->setPeriod_start($startDate);
        
        $this->assertSame($startDate, $contract->getPeriod_start());
    }

    public function testPeriodEndSetterAndGetter(): void
    {
        $contract = new Contract();
        $endDate = new DateTimeImmutable($this->end2024);
        $contract->setPeriod_end($endDate);
        
        $this->assertSame($endDate, $contract->getPeriod_end());
    }

    public function testIsNewRecord(): void
    {
        $contract = new Contract();
        $this->assertTrue($contract->isNewRecord());
        
        // Contract doesn't have setId method, but id is public
        $contract->id = 1;
        $this->assertFalse($contract->isNewRecord());
        
        $contract->id = null;
        $this->assertTrue($contract->isNewRecord());
    }

    public function testDateTimeImmutableProperties(): void
    {
        $contract = new Contract();
        
        $periodStart = $contract->getPeriod_start();
        $periodEnd = $contract->getPeriod_end();
        
        $this->assertInstanceOf(DateTimeImmutable::class, $periodStart);
        $this->assertInstanceOf(DateTimeImmutable::class, $periodEnd);
        $this->assertLessThanOrEqual(time(), $periodStart->getTimestamp());
        $this->assertLessThanOrEqual(time(), $periodEnd->getTimestamp());
    }

    public function testServiceContract(): void
    {
        $contract = new Contract();
        $contract->setName('Monthly Support Contract');
        $contract->setReference('MSC-2024-Q1');
        $contract->setClient_id(789);
        
        $startDate = new DateTimeImmutable($this->beg2024);
        $endDate = new DateTimeImmutable('2024-03-31');
        $contract->setPeriod_start($startDate);
        $contract->setPeriod_end($endDate);
        
        $this->assertSame('Monthly Support Contract', $contract->getName());
        $this->assertSame('MSC-2024-Q1', $contract->getReference());
        $this->assertSame('789', $contract->getClient_id());
        $this->assertSame($startDate, $contract->getPeriod_start());
        $this->assertSame($endDate, $contract->getPeriod_end());
    }

    public function testMaintenanceContract(): void
    {
        $contract = new Contract();
        $contract->setName('Hardware Maintenance');
        $contract->setReference('HM-2024-ANNUAL');
        
        $startDate = new DateTimeImmutable($this->beg2024);
        $endDate = new DateTimeImmutable($this->end2024);
        $contract->setPeriod_start($startDate);
        $contract->setPeriod_end($endDate);
        
        $this->assertSame('Hardware Maintenance', $contract->getName());
        $this->assertSame('HM-2024-ANNUAL', $contract->getReference());
        $this->assertSame($startDate, $contract->getPeriod_start());
        $this->assertSame($endDate, $contract->getPeriod_end());
    }

    public function testLicenseContract(): void
    {
        $contract = new Contract();
        $contract->setName('Software License Agreement');
        $contract->setReference('SLA-2024-ENT-001');
        
        $this->assertSame('Software License Agreement', $contract->getName());
        $this->assertSame('SLA-2024-ENT-001', $contract->getReference());
    }

    public function testLongContractFields(): void
    {
        $longName = str_repeat('Very Long Contract Name With Many Details ', 10);
        $longReference = str_repeat('LONG-REF-', 20) . '001';
        
        $contract = new Contract();
        $contract->setName($longName);
        $contract->setReference($longReference);
        
        $this->assertSame($longName, $contract->getName());
        $this->assertSame($longReference, $contract->getReference());
    }

    public function testSpecialCharactersInContract(): void
    {
        $contract = new Contract();
        $contract->setName('Müller & Co. Service Contract');
        $contract->setReference('M&C-2024-#001');
        
        $this->assertSame('Müller & Co. Service Contract', $contract->getName());
        $this->assertSame('M&C-2024-#001', $contract->getReference());
    }

    public function testUnicodeCharactersInContract(): void
    {
        $contract = new Contract();
        $contract->setName('サービス契約書');
        $contract->setReference('契約-2024-001');
        
        $this->assertSame('サービス契約書', $contract->getName());
        $this->assertSame('契約-2024-001', $contract->getReference());
    }

    public function testZeroAndLargeClientIds(): void
    {
        $contract = new Contract();
        
        $contract->setClient_id(0);
        $this->assertSame('0', $contract->getClient_id());
        
        $contract->setClient_id(999999);
        $this->assertSame('999999', $contract->getClient_id());
        
        $contract->setClient_id(-1);
        $this->assertSame('-1', $contract->getClient_id());
    }

    public function testCompleteContractSetup(): void
    {
        $contract = new Contract();
        $client = $this->createMock(Client::class);
        
        $contract->id = 1;
        $contract->setName('Complete Test Contract');
        $contract->setReference('CTC-2024-001');
        $contract->setClient_id(100);
        $contract->setClient($client);
        
        $startDate = new DateTimeImmutable('2024-06-01');
        $endDate = new DateTimeImmutable($this->end2024);
        $contract->setPeriod_start($startDate);
        $contract->setPeriod_end($endDate);
        
        $this->assertSame(1, $contract->getId());
        $this->assertSame('Complete Test Contract', $contract->getName());
        $this->assertSame('CTC-2024-001', $contract->getReference());
        $this->assertSame('100', $contract->getClient_id());
        $this->assertSame($client, $contract->getClient());
        $this->assertSame($startDate, $contract->getPeriod_start());
        $this->assertSame($endDate, $contract->getPeriod_end());
        $this->assertFalse($contract->isNewRecord());
    }

    public function testGetterMethodsConsistency(): void
    {
        $contract = new Contract(
            name: 'Test Contract',
            reference: 'TC-001',
            client_id: 123
        );
        
        $this->assertIsString($contract->getName());
        $this->assertIsString($contract->getReference());
        $this->assertIsString($contract->getClient_id());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriod_start());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriod_end());
    }

    public function testContractPeriods(): void
    {
        $contract = new Contract();
        
        // Monthly contract
        $monthStart = new DateTimeImmutable($this->beg2024);
        $monthEnd = new DateTimeImmutable('2024-01-31');
        $contract->setPeriod_start($monthStart);
        $contract->setPeriod_end($monthEnd);
        
        $this->assertSame($monthStart, $contract->getPeriod_start());
        $this->assertSame($monthEnd, $contract->getPeriod_end());
        
        // Yearly contract
        $yearStart = new DateTimeImmutable($this->beg2024);
        $yearEnd = new DateTimeImmutable($this->end2024);
        $contract->setPeriod_start($yearStart);
        $contract->setPeriod_end($yearEnd);
        
        $this->assertSame($yearStart, $contract->getPeriod_start());
        $this->assertSame($yearEnd, $contract->getPeriod_end());
    }

    public function testPropertyTypes(): void
    {
        $contract = new Contract(
            name: 'Test Contract',
            reference: 'TC-001',
            client_id: 123
        );
        
        $contract->id = 1;
        
        $this->assertIsInt($contract->getId());
        $this->assertIsString($contract->getClient_id());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriod_start());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriod_end());
    }

    public function testNegativeClientIds(): void
    {
        $contract = new Contract();
        
        $contract->setClient_id(-100);
        $this->assertSame('-100', $contract->getClient_id());
        
        $contract->setClient_id(-1);
        $this->assertSame('-1', $contract->getClient_id());
    }

    public function testContractWorkflow(): void
    {
        // Create new contract
        $contract = new Contract();
        $this->assertTrue($contract->isNewRecord());
        
        // Set contract details
        $contract->setName('Workflow Test Contract');
        $contract->setReference('WTC-2024-001');
        $contract->setClient_id(500);
        
        // Still new until ID is set
        $this->assertTrue($contract->isNewRecord());
        
        // Assign ID (simulating database save)
        $contract->id = 1;
        $this->assertFalse($contract->isNewRecord());
        
        // Update contract details
        $contract->setName('Updated Workflow Contract');
        $this->assertSame('Updated Workflow Contract', $contract->getName());
        $this->assertFalse($contract->isNewRecord());
    }

    public function testClientRelationshipManagement(): void
    {
        $contract = new Contract();
        $client1 = $this->createMock(Client::class);
        $client2 = $this->createMock(Client::class);
        
        // Set initial client
        $contract->setClient_id(100);
        $contract->setClient($client1);
        $this->assertSame($client1, $contract->getClient());
                
        // Set new client
        $contract->setClient_id(200);
        $contract->setClient($client2);
        $this->assertSame($client2, $contract->getClient());        
    }

    public function testTimezoneHandling(): void
    {
        $beforeTime = time();
        $contract = new Contract();
        $afterTime = time();
        
        $startTime = $contract->getPeriod_start()->getTimestamp();
        $endTime = $contract->getPeriod_end()->getTimestamp();
        
        $this->assertGreaterThanOrEqual($beforeTime, $startTime);
        $this->assertLessThanOrEqual($afterTime, $startTime);
        $this->assertGreaterThanOrEqual($beforeTime, $endTime);
        $this->assertLessThanOrEqual($afterTime, $endTime);
    }

    public function testEntityStateAfterConstruction(): void
    {
        $contract = new Contract();
        
        $this->assertTrue($contract->isNewRecord());
        $this->assertNull($contract->getClient());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriod_start());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriod_end());
    }

    public function testPublicProperties(): void
    {
        $contract = new Contract(
            name: 'Public Property Test',
            reference: 'PPT-001'
        );
        
        // Test public properties can be accessed directly
        $this->assertSame('Public Property Test', $contract->name);
        $this->assertSame('PPT-001', $contract->reference);
        $this->assertNull($contract->id);
    }

    public function testContractDateComparisons(): void
    {
        $contract = new Contract();
        
        $startDate = new DateTimeImmutable($this->beg2024);
        $endDate = new DateTimeImmutable($this->end2024);
        
        $contract->setPeriod_start($startDate);
        $contract->setPeriod_end($endDate);
        
        $this->assertLessThan(
            $contract->getPeriod_end()->getTimestamp(),
            $contract->getPeriod_start()->getTimestamp()
        );
    }

    public function testNullClientHandling(): void
    {
        $contract = new Contract();
        $client = $this->createMock(Client::class);
        
        // Set client then set to null
        $contract->setClient($client);
        $this->assertSame($client, $contract->getClient());
        
        $contract->setClient(null);
        $this->assertNull($contract->getClient());
    }
}

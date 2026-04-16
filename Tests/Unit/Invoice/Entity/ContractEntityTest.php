<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Client\Client;
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
        $this->assertSame('', $contract->getClientId());
        $this->assertNull($contract->getClient());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriodStart());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriodEnd());
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
        $this->assertSame('123', $contract->getClientId());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriodStart());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriodEnd());
    }

    public function testClientIdSetterAndGetter(): void
    {
        $contract = new Contract();
        $contract->setClientId(456);
        
        $this->assertSame('456', $contract->getClientId());
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
        $contract->setPeriodStart($startDate);
        
        $this->assertSame($startDate, $contract->getPeriodStart());
    }

    public function testPeriodEndSetterAndGetter(): void
    {
        $contract = new Contract();
        $endDate = new DateTimeImmutable($this->end2024);
        $contract->setPeriodEnd($endDate);
        
        $this->assertSame($endDate, $contract->getPeriodEnd());
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
        
        $periodStart = $contract->getPeriodStart();
        $periodEnd = $contract->getPeriodEnd();
        
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
        $contract->setClientId(789);
        
        $startDate = new DateTimeImmutable($this->beg2024);
        $endDate = new DateTimeImmutable('2024-03-31');
        $contract->setPeriodStart($startDate);
        $contract->setPeriodEnd($endDate);
        
        $this->assertSame('Monthly Support Contract', $contract->getName());
        $this->assertSame('MSC-2024-Q1', $contract->getReference());
        $this->assertSame('789', $contract->getClientId());
        $this->assertSame($startDate, $contract->getPeriodStart());
        $this->assertSame($endDate, $contract->getPeriodEnd());
    }

    public function testMaintenanceContract(): void
    {
        $contract = new Contract();
        $contract->setName('Hardware Maintenance');
        $contract->setReference('HM-2024-ANNUAL');
        
        $startDate = new DateTimeImmutable($this->beg2024);
        $endDate = new DateTimeImmutable($this->end2024);
        $contract->setPeriodStart($startDate);
        $contract->setPeriodEnd($endDate);
        
        $this->assertSame('Hardware Maintenance', $contract->getName());
        $this->assertSame('HM-2024-ANNUAL', $contract->getReference());
        $this->assertSame($startDate, $contract->getPeriodStart());
        $this->assertSame($endDate, $contract->getPeriodEnd());
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
        
        $contract->setClientId(0);
        $this->assertSame('0', $contract->getClientId());
        
        $contract->setClientId(999999);
        $this->assertSame('999999', $contract->getClientId());
        
        $contract->setClientId(-1);
        $this->assertSame('-1', $contract->getClientId());
    }

    public function testCompleteContractSetup(): void
    {
        $contract = new Contract();
        $client = $this->createMock(Client::class);
        
        $contract->id = 1;
        $contract->setName('Complete Test Contract');
        $contract->setReference('CTC-2024-001');
        $contract->setClientId(100);
        $contract->setClient($client);
        
        $startDate = new DateTimeImmutable('2024-06-01');
        $endDate = new DateTimeImmutable($this->end2024);
        $contract->setPeriodStart($startDate);
        $contract->setPeriodEnd($endDate);
        
        $this->assertSame(1, $contract->getId());
        $this->assertSame('Complete Test Contract', $contract->getName());
        $this->assertSame('CTC-2024-001', $contract->getReference());
        $this->assertSame('100', $contract->getClientId());
        $this->assertSame($client, $contract->getClient());
        $this->assertSame($startDate, $contract->getPeriodStart());
        $this->assertSame($endDate, $contract->getPeriodEnd());
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
        $this->assertIsString($contract->getClientId());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriodStart());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriodEnd());
    }

    public function testContractPeriods(): void
    {
        $contract = new Contract();
        
        // Monthly contract
        $monthStart = new DateTimeImmutable($this->beg2024);
        $monthEnd = new DateTimeImmutable('2024-01-31');
        $contract->setPeriodStart($monthStart);
        $contract->setPeriodEnd($monthEnd);
        
        $this->assertSame($monthStart, $contract->getPeriodStart());
        $this->assertSame($monthEnd, $contract->getPeriodEnd());
        
        // Yearly contract
        $yearStart = new DateTimeImmutable($this->beg2024);
        $yearEnd = new DateTimeImmutable($this->end2024);
        $contract->setPeriodStart($yearStart);
        $contract->setPeriodEnd($yearEnd);
        
        $this->assertSame($yearStart, $contract->getPeriodStart());
        $this->assertSame($yearEnd, $contract->getPeriodEnd());
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
        $this->assertIsString($contract->getClientId());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriodStart());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriodEnd());
    }

    public function testNegativeClientIds(): void
    {
        $contract = new Contract();
        
        $contract->setClientId(-100);
        $this->assertSame('-100', $contract->getClientId());
        
        $contract->setClientId(-1);
        $this->assertSame('-1', $contract->getClientId());
    }

    public function testContractWorkflow(): void
    {
        // Create new contract
        $contract = new Contract();
        $this->assertTrue($contract->isNewRecord());
        
        // Set contract details
        $contract->setName('Workflow Test Contract');
        $contract->setReference('WTC-2024-001');
        $contract->setClientId(500);
        
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
        $contract->setClientId(100);
        $contract->setClient($client1);
        $this->assertSame($client1, $contract->getClient());
                
        // Set new client
        $contract->setClientId(200);
        $contract->setClient($client2);
        $this->assertSame($client2, $contract->getClient());
    }

    public function testTimezoneHandling(): void
    {
        $beforeTime = time();
        $contract = new Contract();
        $afterTime = time();
        
        $startTime = $contract->getPeriodStart()->getTimestamp();
        $endTime = $contract->getPeriodEnd()->getTimestamp();
        
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
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriodStart());
        $this->assertInstanceOf(DateTimeImmutable::class, $contract->getPeriodEnd());
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
        
        $contract->setPeriodStart($startDate);
        $contract->setPeriodEnd($endDate);
        
        $this->assertLessThan(
            $contract->getPeriodEnd()->getTimestamp(),
            $contract->getPeriodStart()->getTimestamp()
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

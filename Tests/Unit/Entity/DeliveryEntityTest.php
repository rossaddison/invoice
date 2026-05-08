<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Infrastructure\Persistence\Delivery\Delivery;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use Codeception\Test\Unit;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;

class DeliveryEntityTest extends Unit
{
    private MockObject $deliveryLocation;
    
    public string $ymdHis = 'Y-m-d H:i:s';
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->deliveryLocation = $this->createMock(DeliveryLocation::class);
    }

    public function testConstructorWithDefaults(): void
    {
        $delivery = new Delivery();
        
        $this->assertFalse($delivery->hasIdentity());
        $this->assertNull($delivery->getInvId());
        $this->assertNull($delivery->getInvItemId());
        $this->assertFalse($delivery->hasDeliveryLocationId());
        $this->assertFalse($delivery->hasDeliveryPartyId());
        $this->assertNull($delivery->getDeliveryLocation());
        
        // Check that dates are set automatically
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getDateModified());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getStartDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getEndDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getActualDeliveryDate());
    }

    public function testConstructorWithAllParameters(): void
    {
        $delivery = new Delivery(1, 100, 200, 5, 10);
        
        $this->assertSame(1, $delivery->reqId());
        $this->assertSame(100, $delivery->getInvId());
        $this->assertSame(200, $delivery->getInvItemId());
        $this->assertSame(5, $delivery->reqDeliveryLocationId());
        $this->assertSame(10, $delivery->reqDeliveryPartyId());
        $this->assertNull($delivery->getDeliveryLocation()); // Relationship set by ORM
    }

    public function testIdSetterAndGetter(): void
    {
        $delivery = new Delivery();
        
        $delivery->setId(123);
        $this->assertSame(123, $delivery->reqId());
        
        $delivery->setId(456);
        $this->assertSame(456, $delivery->reqId());
    }

    public function testInvIdSetterAndGetter(): void
    {
        $delivery = new Delivery();
        
        $delivery->setInvId(100);
        $this->assertSame(100, $delivery->getInvId());
        
        $delivery->setInvId(200);
        $this->assertSame(200, $delivery->getInvId());
    }

    public function testInvItemIdSetterAndGetter(): void
    {
        $delivery = new Delivery();
        
        $delivery->setInvItemId(50);
        $this->assertSame(50, $delivery->getInvItemId());
        
        $delivery->setInvItemId(75);
        $this->assertSame(75, $delivery->getInvItemId());
    }

    public function testDeliveryLocationIdSetterAndGetter(): void
    {
        $delivery = new Delivery();
        
        $delivery->setDeliveryLocationId(1);
        $this->assertSame(1, $delivery->reqDeliveryLocationId());
        
        $delivery->setDeliveryLocationId(999);
        $this->assertSame(999, $delivery->reqDeliveryLocationId());
    }

    public function testDeliveryPartyIdSetterAndGetter(): void
    {
        $delivery = new Delivery();
        
        $delivery->setDeliveryPartyId(5);
        $this->assertSame(5, $delivery->reqDeliveryPartyId());
        
        $delivery->setDeliveryPartyId(888);
        $this->assertSame(888, $delivery->reqDeliveryPartyId());
    }

    public function testStartDateSetterAndGetter(): void
    {
        $delivery = new Delivery();
        $startDate = new DateTimeImmutable('2023-01-15 10:00:00');
        
        $delivery->setStartDate($startDate);
        $this->assertSame($startDate, $delivery->getStartDate());
        $this->assertSame('2023-01-15 10:00:00', $delivery->getStartDate()->format($this->ymdHis));
    }

    public function testActualDeliveryDateSetterAndGetter(): void
    {
        $delivery = new Delivery();
        $actualDate = new DateTimeImmutable('2023-02-20 15:30:00');
        
        $delivery->setActualDeliveryDate($actualDate);
        $this->assertSame($actualDate, $delivery->getActualDeliveryDate());
        $this->assertSame('2023-02-20 15:30:00', $delivery->getActualDeliveryDate()->format($this->ymdHis));
        
        // Test setting to null
        $delivery->setActualDeliveryDate(null);
        $this->assertNull($delivery->getActualDeliveryDate());
    }

    public function testEndDateSetterAndGetter(): void
    {
        $delivery = new Delivery();
        $endDate = new DateTimeImmutable('2023-03-31 23:59:59');
        
        $delivery->setEndDate($endDate);
        $this->assertSame($endDate, $delivery->getEndDate());
        $this->assertSame('2023-03-31 23:59:59', $delivery->getEndDate()->format($this->ymdHis));
    }

    public function testDateCreatedSetterAndGetter(): void
    {
        $delivery = new Delivery();
        $dateCreated = new DateTimeImmutable('2023-01-01 12:00:00');
        
        $delivery->setDateCreated($dateCreated);
        $this->assertSame($dateCreated, $delivery->getDateCreated());
        $this->assertSame('2023-01-01 12:00:00', $delivery->getDateCreated()->format($this->ymdHis));
    }

    public function testDateModifiedSetterAndGetter(): void
    {
        $delivery = new Delivery();
        $dateModified = new DateTimeImmutable('2023-12-31 18:45:30');
        
        $delivery->setDateModified($dateModified);
        $this->assertSame($dateModified, $delivery->getDateModified());
        $this->assertSame('2023-12-31 18:45:30', $delivery->getDateModified()->format($this->ymdHis));
    }

    public function testGetDeliveryLocation(): void
    {
        $delivery = new Delivery();
        
        // Initially null (relationship set by ORM)
        $this->assertNull($delivery->getDeliveryLocation());
    }

    public function testhasIdentity(): void
    {
        $delivery = new Delivery();

        $this->assertFalse($delivery->hasIdentity());

        $delivery->setId(1);
        $this->assertTrue($delivery->hasIdentity());
    }

    public function testConstructorDateDefaults(): void
    {
        $delivery = new Delivery();
        
        // Check that start_date is set to first day of current month
        $expectedStartDate = new DateTimeImmutable(date('Y-m-01'));
        $actualStartDate = $delivery->getStartDate();
        $this->assertSame($expectedStartDate->format('Y-m-d'), $actualStartDate->format('Y-m-d'));
        
        // Check that end_date is set to last day of current month
        $expectedEndDate = new DateTimeImmutable(date('Y-m-t'));
        $actualEndDate = $delivery->getEndDate();
        $this->assertSame($expectedEndDate->format('Y-m-d'), $actualEndDate->format('Y-m-d'));
    }

    public function testDeliveryWorkflow(): void
    {
        $delivery = new Delivery();
        
        // Step 1: Set up delivery
        $delivery->setId(1);
        $delivery->setInvId(100);
        $delivery->setInvItemId(50);
        $delivery->setDeliveryLocationId(5);
        $delivery->setDeliveryPartyId(10);
        
        $this->assertSame(1, $delivery->reqId());
        $this->assertSame(100, $delivery->getInvId());
        $this->assertSame(50, $delivery->getInvItemId());
        $this->assertSame(5, $delivery->reqDeliveryLocationId());
        $this->assertSame(10, $delivery->reqDeliveryPartyId());
        $this->assertTrue($delivery->hasIdentity());

        // Step 2: Set delivery dates
        $startDate = new DateTimeImmutable('2023-01-01');
        $endDate = new DateTimeImmutable('2023-01-31');
        $actualDate = new DateTimeImmutable('2023-01-15');
        
        $delivery->setStartDate($startDate);
        $delivery->setEndDate($endDate);
        $delivery->setActualDeliveryDate($actualDate);
        
        $this->assertSame($startDate, $delivery->getStartDate());
        $this->assertSame($endDate, $delivery->getEndDate());
        $this->assertSame($actualDate, $delivery->getActualDeliveryDate());
    }

    public function testDateTimeImmutabilityProperties(): void
    {
        $delivery = new Delivery();
        
        $originalStartDate = $delivery->getStartDate();
        $originalEndDate = $delivery->getEndDate();
        $originalCreatedDate = $delivery->getDateCreated();
        $originalModifiedDate = $delivery->getDateModified();
        
        // DateTimeImmutable objects should be immutable
        $this->assertInstanceOf(DateTimeImmutable::class, $originalStartDate);
        $this->assertInstanceOf(DateTimeImmutable::class, $originalEndDate);
        $this->assertInstanceOf(DateTimeImmutable::class, $originalCreatedDate);
        $this->assertInstanceOf(DateTimeImmutable::class, $originalModifiedDate);
    }

    public function testZeroAndLargeIds(): void
    {
        $delivery = new Delivery();
        
        // Zero IDs
        $delivery->setId(0);
        $delivery->setInvId(0);
        $delivery->setInvItemId(0);
        $delivery->setDeliveryLocationId(0);
        $delivery->setDeliveryPartyId(0);
        
        $this->assertSame(0, $delivery->reqId());
        $this->assertSame(0, $delivery->getInvId());
        $this->assertSame(0, $delivery->getInvItemId());
        $this->assertSame(0, $delivery->reqDeliveryLocationId());
        $this->assertSame(0, $delivery->reqDeliveryPartyId());
        
        // Large IDs
        $delivery->setId(999999999);
        $delivery->setInvId(888888888);
        $delivery->setInvItemId(777777777);
        $delivery->setDeliveryLocationId(666666666);
        $delivery->setDeliveryPartyId(555555555);
        
        $this->assertSame(999999999, $delivery->reqId());
        $this->assertSame(888888888, $delivery->getInvId());
        $this->assertSame(777777777, $delivery->getInvItemId());
        $this->assertSame(666666666, $delivery->reqDeliveryLocationId());
        $this->assertSame(555555555, $delivery->reqDeliveryPartyId());
    }

    public function testCompleteDeliverySetup(): void
    {
        $delivery = new Delivery();
        
        // Complete setup
        $delivery->setId(1);
        $delivery->setInvId(100);
        $delivery->setInvItemId(200);
        $delivery->setDeliveryLocationId(5);
        $delivery->setDeliveryPartyId(10);
        
        $startDate = new DateTimeImmutable('2023-06-01 09:00:00');
        $endDate = new DateTimeImmutable('2023-06-30 17:00:00');
        $actualDate = new DateTimeImmutable('2023-06-15 14:30:00');
        $createdDate = new DateTimeImmutable('2023-05-15 10:00:00');
        $modifiedDate = new DateTimeImmutable('2023-06-16 11:00:00');
        
        $delivery->setStartDate($startDate);
        $delivery->setEndDate($endDate);
        $delivery->setActualDeliveryDate($actualDate);
        $delivery->setDateCreated($createdDate);
        $delivery->setDateModified($modifiedDate);
        
        // Verify all properties
        $this->assertSame(1, $delivery->reqId());
        $this->assertSame(100, $delivery->getInvId());
        $this->assertSame(200, $delivery->getInvItemId());
        $this->assertSame(5, $delivery->reqDeliveryLocationId());
        $this->assertSame(10, $delivery->reqDeliveryPartyId());
        $this->assertSame($startDate, $delivery->getStartDate());
        $this->assertSame($endDate, $delivery->getEndDate());
        $this->assertSame($actualDate, $delivery->getActualDeliveryDate());
        $this->assertSame($createdDate, $delivery->getDateCreated());
        $this->assertSame($modifiedDate, $delivery->getDateModified());
        $this->assertTrue($delivery->hasIdentity());
        $this->assertNull($delivery->getDeliveryLocation()); // Relationship set by ORM
    }

    public function testGetterMethodsConsistency(): void
    {
        $delivery = new Delivery(1, 100, 200, 5, 10);
        
        // Multiple calls should return same values
        $this->assertSame($delivery->reqId(), $delivery->reqId());
        $this->assertSame($delivery->getInvId(), $delivery->getInvId());
        $this->assertSame($delivery->getInvItemId(), $delivery->getInvItemId());
        $this->assertSame($delivery->reqDeliveryLocationId(), $delivery->reqDeliveryLocationId());
        $this->assertSame($delivery->reqDeliveryPartyId(), $delivery->reqDeliveryPartyId());
        $this->assertSame($delivery->getStartDate(), $delivery->getStartDate());
        $this->assertSame($delivery->getEndDate(), $delivery->getEndDate());
        $this->assertSame($delivery->getActualDeliveryDate(), $delivery->getActualDeliveryDate());
        $this->assertSame($delivery->getDateCreated(), $delivery->getDateCreated());
        $this->assertSame($delivery->getDateModified(), $delivery->getDateModified());
        $this->assertSame($delivery->getDeliveryLocation(), $delivery->getDeliveryLocation());
        $this->assertSame($delivery->hasIdentity(), $delivery->hasIdentity());
    }

    public function testDateComparisons(): void
    {
        $delivery = new Delivery();
        
        $startDate = new DateTimeImmutable('2023-01-01');
        $endDate = new DateTimeImmutable('2023-01-31');
        $actualDate = new DateTimeImmutable('2023-01-15');
        
        $delivery->setStartDate($startDate);
        $delivery->setEndDate($endDate);
        $delivery->setActualDeliveryDate($actualDate);
        
        // Test date logic
        $this->assertTrue($delivery->getStartDate() < $delivery->getEndDate());
        $this->assertTrue($delivery->getActualDeliveryDate() > $delivery->getStartDate());
        $this->assertTrue($delivery->getActualDeliveryDate() < $delivery->getEndDate());
    }

    public function testPropertyTypes(): void
    {
        $delivery = new Delivery(1, 100, 200, 5, 10);
        
        // Test return types
        $this->assertIsInt($delivery->reqId());
        $this->assertIsInt($delivery->getInvId());
        $this->assertIsInt($delivery->getInvItemId());
        $this->assertIsInt($delivery->reqDeliveryLocationId());
        $this->assertIsInt($delivery->reqDeliveryPartyId());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getStartDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getEndDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getActualDeliveryDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getDateModified());
        $this->assertNull($delivery->getDeliveryLocation());
        $this->assertIsBool($delivery->hasIdentity());
    }

    public function testNegativeIds(): void
    {
        $delivery = new Delivery();
        
        // Test negative IDs (though probably not used in practice)
        $delivery->setId(-1);
        $delivery->setInvId(-100);
        $delivery->setInvItemId(-200);
        $delivery->setDeliveryLocationId(-5);
        $delivery->setDeliveryPartyId(-10);
        
        $this->assertSame(-1, $delivery->reqId());
        $this->assertSame(-100, $delivery->getInvId());
        $this->assertSame(-200, $delivery->getInvItemId());
        $this->assertSame(-5, $delivery->reqDeliveryLocationId());
        $this->assertSame(-10, $delivery->reqDeliveryPartyId());
    }

    public function testDeliveryScenarios(): void
    {
        $scenarios = [
            [
                'id' => 1,
                'inv_id' => 1001,
                'item_id' => 501,
                'location_id' => 1,
                'party_id' => 1,
                'description' => 'Standard delivery scenario'
            ],
            [
                'id' => 2,
                'inv_id' => 1002,
                'item_id' => 502,
                'location_id' => 2,
                'party_id' => 2,
                'description' => 'Express delivery scenario'
            ],
            [
                'id' => 3,
                'inv_id' => 1003,
                'item_id' => 503,
                'location_id' => 3,
                'party_id' => 3,
                'description' => 'International delivery scenario'
            ]
        ];
        
        foreach ($scenarios as $scenario) {
            $delivery = new Delivery(
                $scenario['id'],
                $scenario['inv_id'],
                $scenario['item_id'],
                $scenario['location_id'],
                $scenario['party_id']
            );
            
            $this->assertSame($scenario['id'], $delivery->reqId());
            $this->assertSame($scenario['inv_id'], $delivery->getInvId());
            $this->assertSame($scenario['item_id'], $delivery->getInvItemId());
            $this->assertSame($scenario['location_id'], $delivery->reqDeliveryLocationId());
            $this->assertSame($scenario['party_id'], $delivery->reqDeliveryPartyId());
            $this->assertTrue($delivery->hasIdentity());
        }
    }

    public function testDateRangeValidation(): void
    {
        $delivery = new Delivery();
        
        // Set up a valid date range
        $startDate = new DateTimeImmutable('2023-06-01');
        $endDate = new DateTimeImmutable('2023-06-30');
        $actualDate = new DateTimeImmutable('2023-06-15');
        
        $delivery->setStartDate($startDate);
        $delivery->setEndDate($endDate);
        $delivery->setActualDeliveryDate($actualDate);
        
        // Verify date relationships
        $this->assertTrue($startDate <= $actualDate);
        $this->assertTrue($actualDate <= $endDate);
        $this->assertTrue($startDate < $endDate);
    }

    public function testRelationshipStructure(): void
    {
        $delivery = new Delivery();
        
        // Set up relationship references
        $delivery->setDeliveryLocationId(5);
        $delivery->setDeliveryPartyId(10);
        
        $this->assertSame(5, $delivery->reqDeliveryLocationId());
        $this->assertSame(10, $delivery->reqDeliveryPartyId());
        
        // DeliveryLocation relationship is null until set by ORM
        $this->assertNull($delivery->getDeliveryLocation());
    }

    public function testTimezoneHandling(): void
    {
        $delivery = new Delivery();
        
        // Test dates with different time zones (DateTimeImmutable handles this)
        $utcDate = new DateTimeImmutable('2023-06-15 12:00:00', new \DateTimeZone('UTC'));
        $estDate = new DateTimeImmutable('2023-06-15 08:00:00', new \DateTimeZone('America/New_York'));
        
        $delivery->setStartDate($utcDate);
        $delivery->setEndDate($estDate);
        
        $this->assertSame($utcDate, $delivery->getStartDate());
        $this->assertSame($estDate, $delivery->getEndDate());
    }

    public function testEntityStateAfterConstruction(): void
    {
        $delivery1 = new Delivery();
        $this->assertFalse($delivery1->hasIdentity());

        $delivery2 = new Delivery(1);
        $this->assertSame(1, $delivery2->reqId());
        $this->assertTrue($delivery2->hasIdentity());

        $delivery3 = new Delivery(null, 100);
        $this->assertFalse($delivery3->hasIdentity());
        $this->assertSame(100, $delivery3->getInvId());

        $delivery4 = new Delivery(1, 100, 200, 5, 10);
        $this->assertSame(1, $delivery4->reqId());
        $this->assertSame(100, $delivery4->getInvId());
        $this->assertSame(200, $delivery4->getInvItemId());
        $this->assertSame(5, $delivery4->reqDeliveryLocationId());
        $this->assertSame(10, $delivery4->reqDeliveryPartyId());
        $this->assertTrue($delivery4->hasIdentity());
    }

    public function testActualDeliveryDateNullHandling(): void
    {
        $delivery = new Delivery();
        
        // Initially set to current date
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getActualDeliveryDate());
        
        // Set to specific date
        $specificDate = new DateTimeImmutable('2023-06-15');
        $delivery->setActualDeliveryDate($specificDate);
        $this->assertSame($specificDate, $delivery->getActualDeliveryDate());
        
        // Set back to null
        $delivery->setActualDeliveryDate(null);
        $this->assertNull($delivery->getActualDeliveryDate());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Invoice\Entity\Delivery;
use App\Invoice\Entity\DeliveryLocation;
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
        
        $this->assertNull($delivery->getId());
        $this->assertNull($delivery->getInv_id());
        $this->assertNull($delivery->getInv_item_id());
        $this->assertSame('', $delivery->getDelivery_location_id());
        $this->assertSame('', $delivery->getDelivery_party_id());
        $this->assertNull($delivery->getDelivery_location());
        
        // Check that dates are set automatically
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getDate_created());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getDate_modified());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getStart_date());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getEnd_date());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getActual_delivery_date());
    }

    public function testConstructorWithAllParameters(): void
    {
        $delivery = new Delivery(1, 100, 200, 5, 10);
        
        $this->assertSame(1, $delivery->getId());
        $this->assertSame(100, $delivery->getInv_id());
        $this->assertSame(200, $delivery->getInv_item_id());
        $this->assertSame('5', $delivery->getDelivery_location_id());
        $this->assertSame('10', $delivery->getDelivery_party_id());
        $this->assertNull($delivery->getDelivery_location()); // Relationship set by ORM
    }

    public function testIdSetterAndGetter(): void
    {
        $delivery = new Delivery();
        
        $delivery->setId(123);
        $this->assertSame(123, $delivery->getId());
        
        $delivery->setId(456);
        $this->assertSame(456, $delivery->getId());
    }

    public function testInvIdSetterAndGetter(): void
    {
        $delivery = new Delivery();
        
        $delivery->setInv_id(100);
        $this->assertSame(100, $delivery->getInv_id());
        
        $delivery->setInv_id(200);
        $this->assertSame(200, $delivery->getInv_id());
    }

    public function testInvItemIdSetterAndGetter(): void
    {
        $delivery = new Delivery();
        
        $delivery->setInv_item_id(50);
        $this->assertSame(50, $delivery->getInv_item_id());
        
        $delivery->setInv_item_id(75);
        $this->assertSame(75, $delivery->getInv_item_id());
    }

    public function testDeliveryLocationIdSetterAndGetter(): void
    {
        $delivery = new Delivery();
        
        $delivery->setDelivery_location_id(1);
        $this->assertSame('1', $delivery->getDelivery_location_id());
        
        $delivery->setDelivery_location_id(999);
        $this->assertSame('999', $delivery->getDelivery_location_id());
    }

    public function testDeliveryPartyIdSetterAndGetter(): void
    {
        $delivery = new Delivery();
        
        $delivery->setDelivery_party_id(5);
        $this->assertSame('5', $delivery->getDelivery_party_id());
        
        $delivery->setDelivery_party_id(888);
        $this->assertSame('888', $delivery->getDelivery_party_id());
    }

    public function testStartDateSetterAndGetter(): void
    {
        $delivery = new Delivery();
        $startDate = new DateTimeImmutable('2023-01-15 10:00:00');
        
        $delivery->setStart_date($startDate);
        $this->assertSame($startDate, $delivery->getStart_date());
        $this->assertSame('2023-01-15 10:00:00', $delivery->getStart_date()->format($this->ymdHis));
    }

    public function testActualDeliveryDateSetterAndGetter(): void
    {
        $delivery = new Delivery();
        $actualDate = new DateTimeImmutable('2023-02-20 15:30:00');
        
        $delivery->setActual_delivery_date($actualDate);
        $this->assertSame($actualDate, $delivery->getActual_delivery_date());
        $this->assertSame('2023-02-20 15:30:00', $delivery->getActual_delivery_date()->format($this->ymdHis));
        
        // Test setting to null
        $delivery->setActual_delivery_date(null);
        $this->assertNull($delivery->getActual_delivery_date());
    }

    public function testEndDateSetterAndGetter(): void
    {
        $delivery = new Delivery();
        $endDate = new DateTimeImmutable('2023-03-31 23:59:59');
        
        $delivery->setEnd_date($endDate);
        $this->assertSame($endDate, $delivery->getEnd_date());
        $this->assertSame('2023-03-31 23:59:59', $delivery->getEnd_date()->format($this->ymdHis));
    }

    public function testDateCreatedSetterAndGetter(): void
    {
        $delivery = new Delivery();
        $dateCreated = new DateTimeImmutable('2023-01-01 12:00:00');
        
        $delivery->setDate_created($dateCreated);
        $this->assertSame($dateCreated, $delivery->getDate_created());
        $this->assertSame('2023-01-01 12:00:00', $delivery->getDate_created()->format($this->ymdHis));
    }

    public function testDateModifiedSetterAndGetter(): void
    {
        $delivery = new Delivery();
        $dateModified = new DateTimeImmutable('2023-12-31 18:45:30');
        
        $delivery->setDate_modified($dateModified);
        $this->assertSame($dateModified, $delivery->getDate_modified());
        $this->assertSame('2023-12-31 18:45:30', $delivery->getDate_modified()->format($this->ymdHis));
    }

    public function testGetDeliveryLocation(): void
    {
        $delivery = new Delivery();
        
        // Initially null (relationship set by ORM)
        $this->assertNull($delivery->getDelivery_location());
    }

    public function testIsNewRecord(): void
    {
        $delivery = new Delivery();
        
        // New record (ID is null)
        $this->assertTrue($delivery->isNewRecord());
        
        // Existing record (ID is set)
        $delivery->setId(1);
        $this->assertFalse($delivery->isNewRecord());
    }

    public function testConstructorDateDefaults(): void
    {
        $delivery = new Delivery();
        
        // Check that start_date is set to first day of current month
        $expectedStartDate = new DateTimeImmutable(date('Y-m-01'));
        $actualStartDate = $delivery->getStart_date();
        $this->assertSame($expectedStartDate->format('Y-m-d'), $actualStartDate->format('Y-m-d'));
        
        // Check that end_date is set to last day of current month
        $expectedEndDate = new DateTimeImmutable(date('Y-m-t'));
        $actualEndDate = $delivery->getEnd_date();
        $this->assertSame($expectedEndDate->format('Y-m-d'), $actualEndDate->format('Y-m-d'));
    }

    public function testDeliveryWorkflow(): void
    {
        $delivery = new Delivery();
        
        // Step 1: Set up delivery
        $delivery->setId(1);
        $delivery->setInv_id(100);
        $delivery->setInv_item_id(50);
        $delivery->setDelivery_location_id(5);
        $delivery->setDelivery_party_id(10);
        
        $this->assertSame(1, $delivery->getId());
        $this->assertSame(100, $delivery->getInv_id());
        $this->assertSame(50, $delivery->getInv_item_id());
        $this->assertSame('5', $delivery->getDelivery_location_id());
        $this->assertSame('10', $delivery->getDelivery_party_id());
        $this->assertFalse($delivery->isNewRecord());
        
        // Step 2: Set delivery dates
        $startDate = new DateTimeImmutable('2023-01-01');
        $endDate = new DateTimeImmutable('2023-01-31');
        $actualDate = new DateTimeImmutable('2023-01-15');
        
        $delivery->setStart_date($startDate);
        $delivery->setEnd_date($endDate);
        $delivery->setActual_delivery_date($actualDate);
        
        $this->assertSame($startDate, $delivery->getStart_date());
        $this->assertSame($endDate, $delivery->getEnd_date());
        $this->assertSame($actualDate, $delivery->getActual_delivery_date());
    }

    public function testDateTimeImmutabilityProperties(): void
    {
        $delivery = new Delivery();
        
        $originalStartDate = $delivery->getStart_date();
        $originalEndDate = $delivery->getEnd_date();
        $originalCreatedDate = $delivery->getDate_created();
        $originalModifiedDate = $delivery->getDate_modified();
        
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
        $delivery->setInv_id(0);
        $delivery->setInv_item_id(0);
        $delivery->setDelivery_location_id(0);
        $delivery->setDelivery_party_id(0);
        
        $this->assertSame(0, $delivery->getId());
        $this->assertSame(0, $delivery->getInv_id());
        $this->assertSame(0, $delivery->getInv_item_id());
        $this->assertSame('0', $delivery->getDelivery_location_id());
        $this->assertSame('0', $delivery->getDelivery_party_id());
        
        // Large IDs
        $delivery->setId(999999999);
        $delivery->setInv_id(888888888);
        $delivery->setInv_item_id(777777777);
        $delivery->setDelivery_location_id(666666666);
        $delivery->setDelivery_party_id(555555555);
        
        $this->assertSame(999999999, $delivery->getId());
        $this->assertSame(888888888, $delivery->getInv_id());
        $this->assertSame(777777777, $delivery->getInv_item_id());
        $this->assertSame('666666666', $delivery->getDelivery_location_id());
        $this->assertSame('555555555', $delivery->getDelivery_party_id());
    }

    public function testCompleteDeliverySetup(): void
    {
        $delivery = new Delivery();
        
        // Complete setup
        $delivery->setId(1);
        $delivery->setInv_id(100);
        $delivery->setInv_item_id(200);
        $delivery->setDelivery_location_id(5);
        $delivery->setDelivery_party_id(10);
        
        $startDate = new DateTimeImmutable('2023-06-01 09:00:00');
        $endDate = new DateTimeImmutable('2023-06-30 17:00:00');
        $actualDate = new DateTimeImmutable('2023-06-15 14:30:00');
        $createdDate = new DateTimeImmutable('2023-05-15 10:00:00');
        $modifiedDate = new DateTimeImmutable('2023-06-16 11:00:00');
        
        $delivery->setStart_date($startDate);
        $delivery->setEnd_date($endDate);
        $delivery->setActual_delivery_date($actualDate);
        $delivery->setDate_created($createdDate);
        $delivery->setDate_modified($modifiedDate);
        
        // Verify all properties
        $this->assertSame(1, $delivery->getId());
        $this->assertSame(100, $delivery->getInv_id());
        $this->assertSame(200, $delivery->getInv_item_id());
        $this->assertSame('5', $delivery->getDelivery_location_id());
        $this->assertSame('10', $delivery->getDelivery_party_id());
        $this->assertSame($startDate, $delivery->getStart_date());
        $this->assertSame($endDate, $delivery->getEnd_date());
        $this->assertSame($actualDate, $delivery->getActual_delivery_date());
        $this->assertSame($createdDate, $delivery->getDate_created());
        $this->assertSame($modifiedDate, $delivery->getDate_modified());
        $this->assertFalse($delivery->isNewRecord());
        $this->assertNull($delivery->getDelivery_location()); // Relationship set by ORM
    }

    public function testGetterMethodsConsistency(): void
    {
        $delivery = new Delivery(1, 100, 200, 5, 10);
        
        // Multiple calls should return same values
        $this->assertSame($delivery->getId(), $delivery->getId());
        $this->assertSame($delivery->getInv_id(), $delivery->getInv_id());
        $this->assertSame($delivery->getInv_item_id(), $delivery->getInv_item_id());
        $this->assertSame($delivery->getDelivery_location_id(), $delivery->getDelivery_location_id());
        $this->assertSame($delivery->getDelivery_party_id(), $delivery->getDelivery_party_id());
        $this->assertSame($delivery->getStart_date(), $delivery->getStart_date());
        $this->assertSame($delivery->getEnd_date(), $delivery->getEnd_date());
        $this->assertSame($delivery->getActual_delivery_date(), $delivery->getActual_delivery_date());
        $this->assertSame($delivery->getDate_created(), $delivery->getDate_created());
        $this->assertSame($delivery->getDate_modified(), $delivery->getDate_modified());
        $this->assertSame($delivery->getDelivery_location(), $delivery->getDelivery_location());
        $this->assertSame($delivery->isNewRecord(), $delivery->isNewRecord());
    }

    public function testDateComparisons(): void
    {
        $delivery = new Delivery();
        
        $startDate = new DateTimeImmutable('2023-01-01');
        $endDate = new DateTimeImmutable('2023-01-31');
        $actualDate = new DateTimeImmutable('2023-01-15');
        
        $delivery->setStart_date($startDate);
        $delivery->setEnd_date($endDate);
        $delivery->setActual_delivery_date($actualDate);
        
        // Test date logic
        $this->assertTrue($delivery->getStart_date() < $delivery->getEnd_date());
        $this->assertTrue($delivery->getActual_delivery_date() > $delivery->getStart_date());
        $this->assertTrue($delivery->getActual_delivery_date() < $delivery->getEnd_date());
    }

    public function testPropertyTypes(): void
    {
        $delivery = new Delivery(1, 100, 200, 5, 10);
        
        // Test return types
        $this->assertIsInt($delivery->getId());
        $this->assertIsInt($delivery->getInv_id());
        $this->assertIsInt($delivery->getInv_item_id());
        $this->assertIsString($delivery->getDelivery_location_id());
        $this->assertIsString($delivery->getDelivery_party_id());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getStart_date());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getEnd_date());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getActual_delivery_date());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getDate_created());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getDate_modified());
        $this->assertNull($delivery->getDelivery_location());
        $this->assertIsBool($delivery->isNewRecord());
    }

    public function testNegativeIds(): void
    {
        $delivery = new Delivery();
        
        // Test negative IDs (though probably not used in practice)
        $delivery->setId(-1);
        $delivery->setInv_id(-100);
        $delivery->setInv_item_id(-200);
        $delivery->setDelivery_location_id(-5);
        $delivery->setDelivery_party_id(-10);
        
        $this->assertSame(-1, $delivery->getId());
        $this->assertSame(-100, $delivery->getInv_id());
        $this->assertSame(-200, $delivery->getInv_item_id());
        $this->assertSame('-5', $delivery->getDelivery_location_id());
        $this->assertSame('-10', $delivery->getDelivery_party_id());
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
            
            $this->assertSame($scenario['id'], $delivery->getId());
            $this->assertSame($scenario['inv_id'], $delivery->getInv_id());
            $this->assertSame($scenario['item_id'], $delivery->getInv_item_id());
            $this->assertSame((string)$scenario['location_id'], $delivery->getDelivery_location_id());
            $this->assertSame((string)$scenario['party_id'], $delivery->getDelivery_party_id());
            $this->assertFalse($delivery->isNewRecord());
        }
    }

    public function testDateRangeValidation(): void
    {
        $delivery = new Delivery();
        
        // Set up a valid date range
        $startDate = new DateTimeImmutable('2023-06-01');
        $endDate = new DateTimeImmutable('2023-06-30');
        $actualDate = new DateTimeImmutable('2023-06-15');
        
        $delivery->setStart_date($startDate);
        $delivery->setEnd_date($endDate);
        $delivery->setActual_delivery_date($actualDate);
        
        // Verify date relationships
        $this->assertTrue($startDate <= $actualDate);
        $this->assertTrue($actualDate <= $endDate);
        $this->assertTrue($startDate < $endDate);
    }

    public function testRelationshipStructure(): void
    {
        $delivery = new Delivery();
        
        // Set up relationship references
        $delivery->setDelivery_location_id(5);
        $delivery->setDelivery_party_id(10);
        
        $this->assertSame('5', $delivery->getDelivery_location_id());
        $this->assertSame('10', $delivery->getDelivery_party_id());
        
        // DeliveryLocation relationship is null until set by ORM
        $this->assertNull($delivery->getDelivery_location());
    }

    public function testTimezoneHandling(): void
    {
        $delivery = new Delivery();
        
        // Test dates with different time zones (DateTimeImmutable handles this)
        $utcDate = new DateTimeImmutable('2023-06-15 12:00:00', new \DateTimeZone('UTC'));
        $estDate = new DateTimeImmutable('2023-06-15 08:00:00', new \DateTimeZone('America/New_York'));
        
        $delivery->setStart_date($utcDate);
        $delivery->setEnd_date($estDate);
        
        $this->assertSame($utcDate, $delivery->getStart_date());
        $this->assertSame($estDate, $delivery->getEnd_date());
    }

    public function testEntityStateAfterConstruction(): void
    {
        // Test various constructor states
        $delivery1 = new Delivery();
        $this->assertNull($delivery1->getId());
        $this->assertTrue($delivery1->isNewRecord());
        
        $delivery2 = new Delivery(1);
        $this->assertSame(1, $delivery2->getId());
        $this->assertFalse($delivery2->isNewRecord());
        
        $delivery3 = new Delivery(null, 100);
        $this->assertNull($delivery3->getId());
        $this->assertSame(100, $delivery3->getInv_id());
        $this->assertTrue($delivery3->isNewRecord());
        
        $delivery4 = new Delivery(1, 100, 200, 5, 10);
        $this->assertSame(1, $delivery4->getId());
        $this->assertSame(100, $delivery4->getInv_id());
        $this->assertSame(200, $delivery4->getInv_item_id());
        $this->assertSame('5', $delivery4->getDelivery_location_id());
        $this->assertSame('10', $delivery4->getDelivery_party_id());
        $this->assertFalse($delivery4->isNewRecord());
    }

    public function testActualDeliveryDateNullHandling(): void
    {
        $delivery = new Delivery();
        
        // Initially set to current date
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getActual_delivery_date());
        
        // Set to specific date
        $specificDate = new DateTimeImmutable('2023-06-15');
        $delivery->setActual_delivery_date($specificDate);
        $this->assertSame($specificDate, $delivery->getActual_delivery_date());
        
        // Set back to null
        $delivery->setActual_delivery_date(null);
        $this->assertNull($delivery->getActual_delivery_date());
    }
}

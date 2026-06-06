<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Invoice\DeliveryLocation\DeliveryLocationForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DeliveryLocationFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new DeliveryLocationForm();

        // date fields default to '' — getter returns DateTimeImmutable('now') for ''
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateModified());
        $this->assertSame('', $form->getClientId());
        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getAddress1());
        $this->assertSame('', $form->getZip());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new DeliveryLocationForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new DeliveryLocation();
        // client_id is int = 0 (not nullable); setClientId(int) sets it
        $entity->setClientId(6);
        $entity->setName('Warehouse A');
        $entity->setBuildingNumber('12');
        $entity->setAddress1('Industrial Estate');
        $entity->setAddress2('Unit 5');
        $entity->setCity('Birmingham');
        $entity->setState('West Midlands');
        $entity->setZip('B1 1AA');
        $entity->setCountry('GB');
        $entity->setGlobalLocationNumber('5012345678906');
        $entity->setElectronicAddressScheme('9925');

        $form = DeliveryLocationForm::show($entity);

        $this->assertSame('6', $form->getClientId());
        $this->assertSame('Warehouse A', $form->getName());
        $this->assertSame('12', $form->getBuildingNumber());
        $this->assertSame('Industrial Estate', $form->getAddress1());
        $this->assertSame('B1 1AA', $form->getZip());
        $this->assertSame('GB', $form->getCountry());
        $this->assertSame('5012345678906', $form->getGlobalLocationNumber());
        $this->assertSame('9925', $form->getElectronicAddressScheme());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateModified());
    }

    public function testClientIdCastToString(): void
    {
        // DeliveryLocation::client_id is int (default 0), not nullable
        // show() does (string) reqClientId() so '0' when not set
        $entity = new DeliveryLocation();
        // no setClientId call — uses default 0

        $form = DeliveryLocationForm::show($entity);

        $this->assertSame('0', $form->getClientId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new DeliveryLocation();
        $entity->setClientId(1);

        $this->assertNotSame(
            DeliveryLocationForm::show($entity),
            DeliveryLocationForm::show($entity)
        );
    }
}

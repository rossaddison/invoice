<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Delivery\Delivery;
use App\Invoice\Delivery\DeliveryForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DeliveryFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new DeliveryForm();

        $this->assertSame('', $form->getDateCreated());
        $this->assertSame('', $form->getDateModified());
        $this->assertSame('', $form->getStartDate());
        $this->assertSame('', $form->getActualDeliveryDate());
        $this->assertSame('', $form->getEndDate());
        $this->assertNull($form->getDeliveryLocationId());
        $this->assertNull($form->getDeliveryPartyId());
        $this->assertNull($form->getInvId());
        $this->assertNull($form->getInvItemId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new DeliveryForm())->getFormName());
    }

    public function testShowPopulatesDateFieldsAsDateTimeImmutable(): void
    {
        $entity = new Delivery(inv_id: 10, inv_item_id: 3);

        $form = DeliveryForm::show($entity);

        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateModified());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getStartDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getActualDeliveryDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getEndDate());
    }

    public function testShowWithoutOptionalIdsPopulatesNull(): void
    {
        $entity = new Delivery(inv_id: 5);

        $form = DeliveryForm::show($entity);

        $this->assertSame(5, $form->getInvId());
        $this->assertNull($form->getDeliveryLocationId());
        $this->assertNull($form->getDeliveryPartyId());
        $this->assertNull($form->getInvItemId());
    }

    public function testShowWithDeliveryLocationId(): void
    {
        $entity = new Delivery(inv_id: 1);
        $entity->setDeliveryLocationId(7);
        $entity->setDeliveryPartyId(9);

        $form = DeliveryForm::show($entity);

        $this->assertSame(7, $form->getDeliveryLocationId());
        $this->assertSame(9, $form->getDeliveryPartyId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new Delivery(inv_id: 1);

        $this->assertNotSame(
            DeliveryForm::show($entity),
            DeliveryForm::show($entity)
        );
    }
}

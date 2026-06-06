<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\DeliveryParty\DeliveryParty;
use App\Invoice\DeliveryParty\DeliveryPartyForm;
use PHPUnit\Framework\TestCase;

class DeliveryPartyFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new DeliveryPartyForm();

        $this->assertSame('', $form->getPartyName());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new DeliveryPartyForm())->getFormName());
    }

    public function testShowPopulatesPartyName(): void
    {
        $entity = new DeliveryParty();
        $entity->setPartyName('Acme Distribution Ltd');

        $form = DeliveryPartyForm::show($entity);

        $this->assertSame('Acme Distribution Ltd', $form->getPartyName());
    }

    public function testShowWithEmptyPartyName(): void
    {
        $entity = new DeliveryParty();

        $form = DeliveryPartyForm::show($entity);

        $this->assertSame('', $form->getPartyName());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new DeliveryParty();
        $entity->setPartyName('Party A');

        $this->assertNotSame(
            DeliveryPartyForm::show($entity),
            DeliveryPartyForm::show($entity)
        );
    }

    public function testTypeIsString(): void
    {
        $entity = new DeliveryParty();
        $entity->setPartyName('Logistics Co');

        $this->assertIsString(DeliveryPartyForm::show($entity)->getPartyName());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\UnitPeppol\UnitPeppol;
use App\Invoice\UnitPeppol\UnitPeppolForm;
use PHPUnit\Framework\TestCase;

class UnitPeppolFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new UnitPeppolForm();

        $this->assertNull($form->getUnitId());
        $this->assertSame('', $form->getCode());
        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getDescription());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new UnitPeppolForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new UnitPeppol();
        $entity->setUnitId(3);
        $entity->setCode('C62');
        $entity->setName('Each');
        $entity->setDescription('A unit of count defining the number of pieces');

        $form = UnitPeppolForm::show($entity);

        $this->assertSame(3, $form->getUnitId());
        $this->assertSame('C62', $form->getCode());
        $this->assertSame('Each', $form->getName());
        $this->assertSame('A unit of count defining the number of pieces', $form->getDescription());
    }

    public function testShowWithKilogramUnit(): void
    {
        $entity = new UnitPeppol();
        $entity->setUnitId(5);
        $entity->setCode('KGM');
        $entity->setName('Kilogram');
        $entity->setDescription('A unit of mass equal to 1000 grams');

        $form = UnitPeppolForm::show($entity);

        $this->assertSame('KGM', $form->getCode());
        $this->assertSame('Kilogram', $form->getName());
        $this->assertSame(5, $form->getUnitId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new UnitPeppol();
        $entity->setUnitId(1);
        $entity->setCode('C62');
        $entity->setName('Each');
        $entity->setDescription('desc');

        $this->assertNotSame(
            UnitPeppolForm::show($entity),
            UnitPeppolForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new UnitPeppol();
        $entity->setUnitId(2);
        $entity->setCode('MTR');
        $entity->setName('Metre');
        $entity->setDescription('SI unit of length');

        $form = UnitPeppolForm::show($entity);

        $this->assertIsInt($form->getUnitId());
        $this->assertIsString($form->getCode());
        $this->assertIsString($form->getName());
        $this->assertIsString($form->getDescription());
    }
}

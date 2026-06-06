<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Gentor\Gentor;
use App\Invoice\Generator\GeneratorForm;
use PHPUnit\Framework\TestCase;

class GeneratorFormTest extends TestCase
{
    public function testDefaultsAreEmptyStrings(): void
    {
        $form = new GeneratorForm();

        $this->assertSame('', $form->getRoutePrefix());
        $this->assertSame('', $form->getRouteSuffix());
        $this->assertSame('', $form->getCamelcaseCapitalName());
        $this->assertSame('', $form->getSmallSingularName());
        $this->assertSame('', $form->getSmallPluralName());
        $this->assertSame('', $form->getNamespacePath());
        $this->assertSame('', $form->getPreEntityTable());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new GeneratorForm())->getFormName());
    }

    public function testShowPopulatesStringFields(): void
    {
        $entity = new Gentor();
        $entity->setRoutePrefix('inv');
        $entity->setRouteSuffix('view');
        $entity->setCamelcaseCapitalName('Invoice');
        $entity->setSmallSingularName('invoice');
        $entity->setSmallPluralName('invoices');
        $entity->setNamespacePath('App\\Invoice\\Inv');
        $entity->setControllerLayoutDir('views/invoice');
        $entity->setControllerLayoutDirDotPath('@invoice/layout/main.php');
        $entity->setPreEntityTable('inv_');

        $form = GeneratorForm::show($entity);

        $this->assertSame('inv', $form->getRoutePrefix());
        $this->assertSame('view', $form->getRouteSuffix());
        $this->assertSame('Invoice', $form->getCamelcaseCapitalName());
        $this->assertSame('invoice', $form->getSmallSingularName());
        $this->assertSame('invoices', $form->getSmallPluralName());
        $this->assertSame('App\\Invoice\\Inv', $form->getNamespacePath());
        $this->assertSame('@invoice/layout/main.php', $form->getControllerLayoutDirDotPath());
        $this->assertSame('inv_', $form->getPreEntityTable());
    }

    public function testShowWithEmptyEntityReturnsEntityDefaults(): void
    {
        $entity = new Gentor();

        $form = GeneratorForm::show($entity);

        $this->assertSame('', $form->getRoutePrefix());
        $this->assertSame('', $form->getSmallSingularName());
        $this->assertSame('', $form->getPreEntityTable());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new Gentor();

        $this->assertNotSame(
            GeneratorForm::show($entity),
            GeneratorForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new Gentor();
        $entity->setRoutePrefix('test');

        $form = GeneratorForm::show($entity);

        $this->assertIsString($form->getRoutePrefix());
        $this->assertIsString($form->getNamespacePath());
        $this->assertIsString($form->getControllerLayoutDir());
    }
}

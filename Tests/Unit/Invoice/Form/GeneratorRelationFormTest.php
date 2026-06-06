<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\GentorRelation\GentorRelation;
use App\Invoice\GeneratorRelation\GeneratorRelationForm;
use PHPUnit\Framework\TestCase;

class GeneratorRelationFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new GeneratorRelationForm();

        $this->assertSame('', $form->getLowercaseName());
        $this->assertSame('', $form->getCamelcaseName());
        $this->assertSame('', $form->getViewFieldName());
        $this->assertNull($form->reqGentorId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new GeneratorRelationForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new GentorRelation();
        $entity->setLowercaseName('invoice');
        $entity->setCamelcaseName('Invoice');
        $entity->setViewFieldName('inv_number');
        $entity->setGentorId(3);

        $form = GeneratorRelationForm::show($entity);

        $this->assertSame('invoice', $form->getLowercaseName());
        $this->assertSame('Invoice', $form->getCamelcaseName());
        $this->assertSame('inv_number', $form->getViewFieldName());
        $this->assertSame(3, $form->reqGentorId());
    }

    public function testShowWithNoGentorIdReturnsNull(): void
    {
        // GentorRelation::reqGentorId() returns ?int directly (not via requireId)
        $entity = new GentorRelation();

        $form = GeneratorRelationForm::show($entity);

        $this->assertNull($form->reqGentorId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new GentorRelation();
        $entity->setGentorId(1);

        $this->assertNotSame(
            GeneratorRelationForm::show($entity),
            GeneratorRelationForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new GentorRelation();
        $entity->setLowercaseName('client');
        $entity->setCamelcaseName('Client');
        $entity->setViewFieldName('client_name');
        $entity->setGentorId(5);

        $form = GeneratorRelationForm::show($entity);

        $this->assertIsString($form->getLowercaseName());
        $this->assertIsString($form->getCamelcaseName());
        $this->assertIsInt($form->reqGentorId());
    }
}

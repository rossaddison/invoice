<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Task\Task;
use App\Invoice\Task\TaskForm;
use PHPUnit\Framework\TestCase;

class TaskFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new TaskForm();

        $this->assertNull($form->getProjectId());
        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getDescription());
        $this->assertNull($form->getPrice());
        $this->assertSame('', $form->getFinishDate());
        $this->assertNull($form->getStatus());
        $this->assertNull($form->getTaxRateId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new TaskForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new Task();
        $entity->setProjectId(2);
        $entity->setTaxRateId(1);
        $entity->setName('Design mockups');
        $entity->setDescription('Create UI wireframes');
        $entity->setPrice(150.00);
        $entity->setStatus(1);

        $form = TaskForm::show($entity);

        $this->assertSame(2, $form->getProjectId());
        $this->assertSame(1, $form->getTaxRateId());
        $this->assertSame('Design mockups', $form->getName());
        $this->assertSame('Create UI wireframes', $form->getDescription());
        $this->assertSame(150.00, $form->getPrice());
        $this->assertSame('', $form->getFinishDate());
        $this->assertSame(1, $form->getStatus());
    }

    public function testShowWithNullableFields(): void
    {
        $entity = new Task();
        $entity->setProjectId(1);
        $entity->setTaxRateId(1);
        $entity->setName('Minimal task');
        $entity->setDescription('');
        $entity->setPrice(0.00);
        $entity->setStatus(0);

        $form = TaskForm::show($entity);

        $this->assertSame('', $form->getDescription());
        $this->assertSame(0.00, $form->getPrice());
        $this->assertSame(0, $form->getStatus());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new Task();
        $entity->setProjectId(1);
        $entity->setTaxRateId(1);
        $entity->setName('Test');
        $entity->setDescription('desc');
        $entity->setPrice(10.00);
        $entity->setStatus(1);

        $this->assertNotSame(
            TaskForm::show($entity),
            TaskForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new Task();
        $entity->setProjectId(3);
        $entity->setTaxRateId(2);
        $entity->setName('Type check');
        $entity->setDescription('desc');
        $entity->setPrice(99.99);
        $entity->setStatus(1);

        $form = TaskForm::show($entity);

        $this->assertIsInt($form->getProjectId());
        $this->assertIsInt($form->getTaxRateId());
        $this->assertIsString($form->getName());
        $this->assertIsFloat($form->getPrice());
        $this->assertIsInt($form->getStatus());
    }
}

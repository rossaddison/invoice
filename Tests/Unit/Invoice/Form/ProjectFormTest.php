<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Project\Project;
use App\Invoice\Project\ProjectForm;
use PHPUnit\Framework\TestCase;

class ProjectFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new ProjectForm();

        $this->assertNull($form->getClientId());
        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ProjectForm())->getFormName());
    }

    public function testShowPopulatesFields(): void
    {
        $entity = new Project();
        $entity->setClientId(5);
        $entity->setName('Website Redesign');

        $form = ProjectForm::show($entity);

        $this->assertSame(5, $form->getClientId());
        $this->assertSame('Website Redesign', $form->getName());
    }

    public function testShowWithEmptyName(): void
    {
        $entity = new Project();
        $entity->setClientId(2);
        $entity->setName('');

        $form = ProjectForm::show($entity);

        $this->assertSame('', $form->getName());
        $this->assertSame(2, $form->getClientId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new Project();
        $entity->setClientId(1);
        $entity->setName('App Build');

        $this->assertNotSame(
            ProjectForm::show($entity),
            ProjectForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new Project();
        $entity->setClientId(10);
        $entity->setName('CRM');

        $form = ProjectForm::show($entity);

        $this->assertIsInt($form->getClientId());
        $this->assertIsString($form->getName());
    }
}

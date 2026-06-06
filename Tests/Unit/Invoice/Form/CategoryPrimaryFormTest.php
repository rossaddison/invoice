<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Invoice\CategoryPrimary\CategoryPrimaryForm;
use PHPUnit\Framework\TestCase;

class CategoryPrimaryFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new CategoryPrimaryForm();

        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new CategoryPrimaryForm())->getFormName());
    }

    public function testShowPopulatesName(): void
    {
        $entity = new CategoryPrimary();
        $entity->setName('Electronics');

        $form = CategoryPrimaryForm::show($entity);

        $this->assertSame('Electronics', $form->getName());
    }

    public function testShowWithEmptyName(): void
    {
        $entity = new CategoryPrimary();
        $entity->setName('');

        $form = CategoryPrimaryForm::show($entity);

        $this->assertSame('', $form->getName());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new CategoryPrimary();
        $entity->setName('Books');

        $this->assertNotSame(
            CategoryPrimaryForm::show($entity),
            CategoryPrimaryForm::show($entity)
        );
    }

    public function testNameIsString(): void
    {
        $entity = new CategoryPrimary();
        $entity->setName('Hardware');

        $this->assertIsString(CategoryPrimaryForm::show($entity)->getName());
    }
}

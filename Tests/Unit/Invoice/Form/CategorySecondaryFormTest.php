<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Invoice\CategorySecondary\CategorySecondaryForm;
use PHPUnit\Framework\TestCase;

class CategorySecondaryFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new CategorySecondaryForm();

        $this->assertNull($form->getCategoryPrimaryId());
        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new CategorySecondaryForm())->getFormName());
    }

    public function testShowPopulatesFields(): void
    {
        $entity = new CategorySecondary();
        $entity->setCategoryPrimaryId(3);
        $entity->setName('Laptops');

        $form = CategorySecondaryForm::show($entity);

        $this->assertSame(3, $form->getCategoryPrimaryId());
        $this->assertSame('Laptops', $form->getName());
    }

    public function testShowWithEmptyName(): void
    {
        $entity = new CategorySecondary();
        $entity->setCategoryPrimaryId(1);
        $entity->setName('');

        $form = CategorySecondaryForm::show($entity);

        $this->assertSame('', $form->getName());
        $this->assertSame(1, $form->getCategoryPrimaryId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new CategorySecondary();
        $entity->setCategoryPrimaryId(1);
        $entity->setName('Tablets');

        $this->assertNotSame(
            CategorySecondaryForm::show($entity),
            CategorySecondaryForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new CategorySecondary();
        $entity->setCategoryPrimaryId(7);
        $entity->setName('Monitors');

        $form = CategorySecondaryForm::show($entity);

        $this->assertIsInt($form->getCategoryPrimaryId());
        $this->assertIsString($form->getName());
    }
}

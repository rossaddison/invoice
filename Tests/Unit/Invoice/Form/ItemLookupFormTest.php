<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\ItemLookup\ItemLookup;
use App\Invoice\ItemLookup\ItemLookupForm;
use PHPUnit\Framework\TestCase;

class ItemLookupFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new ItemLookupForm();

        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getDescription());
        $this->assertNull($form->getPrice());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ItemLookupForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new ItemLookup();
        $entity->setName('Premium Support');
        $entity->setDescription('24/7 support contract');
        $entity->setPrice(499.99);

        $form = ItemLookupForm::show($entity);

        $this->assertSame('Premium Support', $form->getName());
        $this->assertSame('24/7 support contract', $form->getDescription());
        $this->assertSame(499.99, $form->getPrice());
    }

    public function testShowWithZeroPrice(): void
    {
        $entity = new ItemLookup();
        $entity->setName('Free tier');
        $entity->setDescription('No cost');
        $entity->setPrice(0.00);

        $form = ItemLookupForm::show($entity);

        $this->assertSame(0.00, $form->getPrice());
    }

    public function testShowWithEmptyDescription(): void
    {
        $entity = new ItemLookup();
        $entity->setName('Widget');
        $entity->setDescription('');
        $entity->setPrice(9.99);

        $form = ItemLookupForm::show($entity);

        $this->assertSame('', $form->getDescription());
        $this->assertSame('Widget', $form->getName());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new ItemLookup();
        $entity->setName('Thing');
        $entity->setDescription('desc');
        $entity->setPrice(1.00);

        $this->assertNotSame(
            ItemLookupForm::show($entity),
            ItemLookupForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new ItemLookup();
        $entity->setName('Item');
        $entity->setDescription('desc');
        $entity->setPrice(25.00);

        $form = ItemLookupForm::show($entity);

        $this->assertIsString($form->getName());
        $this->assertIsString($form->getDescription());
        $this->assertIsFloat($form->getPrice());
    }
}

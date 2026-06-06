<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Invoice\QuoteItem\QuoteItemForm;
use PHPUnit\Framework\TestCase;

class QuoteItemFormTest extends TestCase
{
    public function testDefaultsAreEmptyOrNull(): void
    {
        $form = new QuoteItemForm();

        $this->assertSame('', $form->getTaxRateId());
        $this->assertSame('', $form->getProductId());
        $this->assertSame('', $form->getTaskId());
        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getDescription());
        $this->assertNull($form->getQuantity());
        $this->assertNull($form->getPrice());
        $this->assertNull($form->getDiscountAmount());
        $this->assertNull($form->getOrder());
        $this->assertSame('', $form->getProductUnit());
        $this->assertNull($form->getProductUnitId());
        $this->assertNull($form->getQuoteId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new QuoteItemForm())->getFormName());
    }

    public function testShowUsesPassedQuoteId(): void
    {
        $entity = new QuoteItem();
        $entity->setTaxRateId(9);

        $form = QuoteItemForm::show($entity, 4);

        $this->assertSame(4, $form->getQuoteId());
        $this->assertSame('9', $form->getTaxRateId());
    }

    public function testShowProductAndTaskAreNullWhenNotLoaded(): void
    {
        // Relations not loaded in unit tests — getProduct()/getTask() return null
        $entity = new QuoteItem();
        $entity->setTaxRateId(1);

        $form = QuoteItemForm::show($entity, 1);

        $this->assertNull($form->getProductId());
        $this->assertNull($form->getTaskId());
    }

    public function testShowPopulatesNumericFields(): void
    {
        $entity = new QuoteItem();
        $entity->setTaxRateId(2);
        $entity->setQuantity(3.0);
        $entity->setPrice(99.99);
        $entity->setDiscountAmount(5.00);
        $entity->setOrder(1);

        $form = QuoteItemForm::show($entity, 2);

        $this->assertSame(3.0, $form->getQuantity());
        $this->assertSame(99.99, $form->getPrice());
        $this->assertSame(5.0, $form->getDiscountAmount());
        $this->assertSame(1, $form->getOrder());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new QuoteItem();
        $entity->setTaxRateId(1);

        $this->assertNotSame(
            QuoteItemForm::show($entity, 1),
            QuoteItemForm::show($entity, 1)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new QuoteItem();
        $entity->setTaxRateId(5);

        $form = QuoteItemForm::show($entity, 1);

        $this->assertIsString($form->getTaxRateId());
        $this->assertIsInt($form->getQuoteId());
    }
}

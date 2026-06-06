<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\QuoteItemAllowanceCharge\QuoteItemAllowanceCharge;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeForm;
use PHPUnit\Framework\TestCase;

class QuoteItemAllowanceChargeFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new QuoteItemAllowanceChargeForm();

        $this->assertNull($form->getAllowanceChargeId());
        $this->assertNull($form->getAmount());
        $this->assertNull($form->getQuoteId());
        $this->assertNull($form->getQuoteItemId());
        $this->assertNull($form->getVatOrTax());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new QuoteItemAllowanceChargeForm())->getFormName());
    }

    public function testShowUsesPassedQuoteItemId(): void
    {
        $entity = new QuoteItemAllowanceCharge();
        $entity->setQuoteId(5);
        $entity->setAllowanceChargeId(3);

        $form = QuoteItemAllowanceChargeForm::show($entity, 10);

        $this->assertSame(3, $form->getAllowanceChargeId());
        $this->assertSame(5, $form->getQuoteId());
        $this->assertSame(10, $form->getQuoteItemId());
    }

    public function testShowWithNullAmountsAreZeroFloat(): void
    {
        // getAmount()/getVatOrTax() return (string) null = ''; (float)'' = 0.0
        $entity = new QuoteItemAllowanceCharge();
        $entity->setQuoteId(1);
        $entity->setAllowanceChargeId(1);

        $form = QuoteItemAllowanceChargeForm::show($entity, null);

        $this->assertSame(0.0, $form->getAmount());
        $this->assertSame(0.0, $form->getVatOrTax());
        $this->assertNull($form->getQuoteItemId());
    }

    public function testShowPopulatesAmounts(): void
    {
        $entity = new QuoteItemAllowanceCharge();
        $entity->setQuoteId(2);
        $entity->setAllowanceChargeId(2);
        $entity->setAmount(150.00);
        $entity->setVatOrTax(25.50);

        $form = QuoteItemAllowanceChargeForm::show($entity, 7);

        $this->assertSame(150.0, $form->getAmount());
        $this->assertSame(25.5, $form->getVatOrTax());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new QuoteItemAllowanceCharge();
        $entity->setQuoteId(1);
        $entity->setAllowanceChargeId(1);

        $this->assertNotSame(
            QuoteItemAllowanceChargeForm::show($entity, 1),
            QuoteItemAllowanceChargeForm::show($entity, 1)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new QuoteItemAllowanceCharge();
        $entity->setQuoteId(3);
        $entity->setAllowanceChargeId(4);
        $entity->setAmount(100.00);

        $form = QuoteItemAllowanceChargeForm::show($entity, 2);

        $this->assertIsInt($form->getAllowanceChargeId());
        $this->assertIsInt($form->getQuoteId());
        $this->assertIsFloat($form->getAmount());
    }
}

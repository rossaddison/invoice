<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\QuoteAllowanceCharge\QuoteAllowanceCharge;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeForm;
use PHPUnit\Framework\TestCase;

class QuoteAllowanceChargeFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new QuoteAllowanceChargeForm();

        $this->assertNull($form->getAllowanceChargeId());
        $this->assertNull($form->getAmount());
        $this->assertNull($form->getQuoteId());
        $this->assertNull($form->getVatOrTax());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new QuoteAllowanceChargeForm())->getFormName());
    }

    public function testShowUsesPassedQuoteId(): void
    {
        // show() takes ?int $quote_id as 2nd param; allowance_charge_id from entity
        $entity = new QuoteAllowanceCharge();
        $entity->setAllowanceChargeId(3);

        $form = QuoteAllowanceChargeForm::show($entity, 10);

        $this->assertSame(3, $form->getAllowanceChargeId());
        $this->assertSame(10, $form->getQuoteId());
    }

    public function testShowWithNullAmountsAreZero(): void
    {
        // Entity defaults: amount=null, vat_or_tax=null; form casts (int) null = 0
        $entity = new QuoteAllowanceCharge();
        $entity->setAllowanceChargeId(1);

        $form = QuoteAllowanceChargeForm::show($entity, null);

        $this->assertSame(0, $form->getAmount());
        $this->assertSame(0, $form->getVatOrTax());
        $this->assertNull($form->getQuoteId());
    }

    public function testShowPopulatesAmounts(): void
    {
        $entity = new QuoteAllowanceCharge();
        $entity->setAllowanceChargeId(2);
        $entity->setAmount(100.00);
        $entity->setVatOrTax(20.00);

        $form = QuoteAllowanceChargeForm::show($entity, 5);

        $this->assertSame(100, $form->getAmount());
        $this->assertSame(20, $form->getVatOrTax());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new QuoteAllowanceCharge();
        $entity->setAllowanceChargeId(1);

        $this->assertNotSame(
            QuoteAllowanceChargeForm::show($entity, 1),
            QuoteAllowanceChargeForm::show($entity, 1)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new QuoteAllowanceCharge();
        $entity->setAllowanceChargeId(4);
        $entity->setAmount(50.00);

        $form = QuoteAllowanceChargeForm::show($entity, 2);

        $this->assertIsInt($form->getAllowanceChargeId());
        $this->assertIsInt($form->getAmount());
        $this->assertIsInt($form->getQuoteId());
    }
}

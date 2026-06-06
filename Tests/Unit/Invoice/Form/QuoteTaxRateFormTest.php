<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\QuoteTaxRate\QuoteTaxRate;
use App\Invoice\QuoteTaxRate\QuoteTaxRateForm;
use PHPUnit\Framework\TestCase;

class QuoteTaxRateFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new QuoteTaxRateForm();

        $this->assertNull($form->getQuoteId());
        $this->assertNull($form->getTaxRateId());
        $this->assertNull($form->getIncludeItemTax());
        $this->assertSame(0.0, $form->getQuoteTaxRateAmount());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new QuoteTaxRateForm())->getFormName());
    }

    public function testShowPopulatesIds(): void
    {
        $entity = new QuoteTaxRate();
        $entity->setQuoteId(4);
        $entity->setTaxRateId(2);

        $form = QuoteTaxRateForm::show($entity);

        $this->assertSame(4, $form->getQuoteId());
        $this->assertSame(2, $form->getTaxRateId());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new QuoteTaxRate();
        $entity->setQuoteId(6);
        $entity->setTaxRateId(3);
        $entity->setIncludeItemTax(1);
        $entity->setQuoteTaxRateAmount(75.25);

        $form = QuoteTaxRateForm::show($entity);

        $this->assertSame(6, $form->getQuoteId());
        $this->assertSame(3, $form->getTaxRateId());
        $this->assertSame(1, $form->getIncludeItemTax());
        $this->assertSame(75.25, $form->getQuoteTaxRateAmount());
    }

    public function testGetQuoteTaxRateAmountFallsBackToZero(): void
    {
        // Form getter uses ?? 0.00; entity default is 0.00
        $entity = new QuoteTaxRate();
        $entity->setQuoteId(1);
        $entity->setTaxRateId(1);

        $form = QuoteTaxRateForm::show($entity);

        $this->assertSame(0.0, $form->getQuoteTaxRateAmount());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new QuoteTaxRate();
        $entity->setQuoteId(1);
        $entity->setTaxRateId(1);

        $this->assertNotSame(
            QuoteTaxRateForm::show($entity),
            QuoteTaxRateForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new QuoteTaxRate();
        $entity->setQuoteId(2);
        $entity->setTaxRateId(3);

        $form = QuoteTaxRateForm::show($entity);

        $this->assertIsInt($form->getQuoteId());
        $this->assertIsInt($form->getTaxRateId());
        $this->assertIsFloat($form->getQuoteTaxRateAmount());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Invoice\QuoteAmount\QuoteAmountForm;
use PHPUnit\Framework\TestCase;

class QuoteAmountFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new QuoteAmountForm();

        $this->assertNull($form->getQuoteId());
        $this->assertNull($form->getItemSubtotal());
        $this->assertNull($form->getItemTaxTotal());
        $this->assertNull($form->getPackhandleshipTotal());
        $this->assertNull($form->getPackhandleshipTax());
        $this->assertNull($form->getTaxTotal());
        $this->assertNull($form->getTotal());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new QuoteAmountForm())->getFormName());
    }

    public function testGetRulesContainsRequiredFields(): void
    {
        $rules = (new QuoteAmountForm())->getRules();

        $this->assertArrayHasKey('quote_id', $rules);
        $this->assertArrayHasKey('item_subtotal', $rules);
        $this->assertArrayHasKey('item_tax_total', $rules);
        $this->assertArrayHasKey('packhandleship_total', $rules);
        $this->assertArrayHasKey('packhandleship_tax', $rules);
        $this->assertArrayHasKey('tax_total', $rules);
        $this->assertArrayHasKey('total', $rules);
        $this->assertCount(7, $rules);
    }

    public function testNewInstanceIsIsolated(): void
    {
        $this->assertNotSame(new QuoteAmountForm(), new QuoteAmountForm());
    }

    public function testAllFloatGettersReturnNullByDefault(): void
    {
        $form = new QuoteAmountForm();

        $this->assertNull($form->getItemSubtotal());
        $this->assertNull($form->getTaxTotal());
        $this->assertNull($form->getTotal());
    }
}

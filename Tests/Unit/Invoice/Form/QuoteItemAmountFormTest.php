<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Invoice\QuoteItemAmount\QuoteItemAmountForm;
use PHPUnit\Framework\TestCase;

class QuoteItemAmountFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new QuoteItemAmountForm();

        $this->assertNull($form->getQuoteItemId());
        $this->assertNull($form->getSubtotal());
        $this->assertNull($form->getTaxTotal());
        $this->assertNull($form->getDiscount());
        $this->assertNull($form->getTotal());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new QuoteItemAmountForm())->getFormName());
    }

    public function testGetRulesHasFourRequiredFields(): void
    {
        $rules = (new QuoteItemAmountForm())->getRules();

        $this->assertArrayHasKey('subtotal', $rules);
        $this->assertArrayHasKey('tax_total', $rules);
        $this->assertArrayHasKey('discount', $rules);
        $this->assertArrayHasKey('total', $rules);
        $this->assertCount(4, $rules);
    }

    public function testGetRulesDoesNotContainQuoteItemId(): void
    {
        $rules = (new QuoteItemAmountForm())->getRules();

        $this->assertArrayNotHasKey('quote_item_id', $rules);
    }

    public function testNewInstancesAreIndependent(): void
    {
        $this->assertNotSame(new QuoteItemAmountForm(), new QuoteItemAmountForm());
    }
}

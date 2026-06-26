<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\QuoteAmount;

use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class QuoteAmountTest
{
    public function defaultsToUnpersisted(): void
    {
        $qa = new QuoteAmount();

        Assert::false($qa->hasIdentity());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new QuoteAmount())->reqId();
    }

    public function reqIdThrowsWithCorrectMessage(): void
    {
        try {
            (new QuoteAmount())->reqId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'Quote Amount not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setIdMakesEntityIdentifiable(): void
    {
        $qa = new QuoteAmount();
        $qa->setId(12);

        Assert::true($qa->hasIdentity());
        Assert::same($qa->reqId(), 12);
    }

    #[ExpectException(\LogicException::class)]
    public function reqQuoteIdThrowsWhenNull(): void
    {
        (new QuoteAmount())->reqQuoteId();
    }

    public function reqQuoteIdThrowsWithCorrectMessage(): void
    {
        try {
            (new QuoteAmount())->reqQuoteId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'Quote not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setQuoteIdAndRetrieve(): void
    {
        $qa = new QuoteAmount();
        $qa->setQuoteId(99);

        Assert::same($qa->reqQuoteId(), 99);
    }

    public function constructorDefaultsAllAmountsToZero(): void
    {
        $qa = new QuoteAmount();

        Assert::same($qa->getItemSubtotal(), 0.00);
        Assert::same($qa->getItemTaxTotal(), 0.00);
        Assert::same($qa->getPackhandleshipTotal(), 0.00);
        Assert::same($qa->getPackhandleshipTax(), 0.00);
        Assert::same($qa->getTaxTotal(), 0.00);
        Assert::same($qa->getTotal(), 0.00);
    }

    public function settersAndGetters(): void
    {
        $qa = new QuoteAmount(
            id: 1,
            quote_id: 5,
            item_subtotal: 100.00,
            item_tax_total: 20.00,
            packhandleship_total: 10.00,
            packhandleship_tax: 2.00,
            tax_total: 22.00,
            total: 132.00,
        );

        Assert::same($qa->reqId(), 1);
        Assert::same($qa->reqQuoteId(), 5);
        Assert::same($qa->getItemSubtotal(), 100.00);
        Assert::same($qa->getItemTaxTotal(), 20.00);
        Assert::same($qa->getPackhandleshipTotal(), 10.00);
        Assert::same($qa->getPackhandleshipTax(), 2.00);
        Assert::same($qa->getTaxTotal(), 22.00);
        Assert::same($qa->getTotal(), 132.00);
    }

    public function settersMutateValues(): void
    {
        $qa = new QuoteAmount();
        $qa->setItemSubtotal(200.50);
        $qa->setItemTaxTotal(40.10);
        $qa->setPackhandleshipTotal(15.00);
        $qa->setPackhandleshipTax(3.00);
        $qa->setTaxTotal(43.10);
        $qa->setTotal(258.60);

        Assert::same($qa->getItemSubtotal(), 200.50);
        Assert::same($qa->getItemTaxTotal(), 40.10);
        Assert::same($qa->getPackhandleshipTotal(), 15.00);
        Assert::same($qa->getPackhandleshipTax(), 3.00);
        Assert::same($qa->getTaxTotal(), 43.10);
        Assert::same($qa->getTotal(), 258.60);
    }

    public function quoteRelationNullByDefault(): void
    {
        Assert::null((new QuoteAmount())->getQuote());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\QuoteItemAmount;

use App\Infrastructure\Persistence\QuoteItemAmount\QuoteItemAmount;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class QuoteItemAmountTest
{
    public function defaultsToUnpersisted(): void
    {
        Assert::false((new QuoteItemAmount())->hasIdentity());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new QuoteItemAmount())->reqId();
    }

    public function reqIdThrowsWithCorrectMessage(): void
    {
        try {
            (new QuoteItemAmount())->reqId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'QuoteItemAmount not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setIdMakesEntityIdentifiable(): void
    {
        $qia = new QuoteItemAmount();
        $qia->setId(7);

        Assert::true($qia->hasIdentity());
        Assert::same($qia->reqId(), 7);
    }

    #[ExpectException(\LogicException::class)]
    public function reqQuoteItemIdThrowsWhenNull(): void
    {
        (new QuoteItemAmount())->reqQuoteItemId();
    }

    public function reqQuoteItemIdThrowsWithCorrectMessage(): void
    {
        try {
            (new QuoteItemAmount())->reqQuoteItemId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'QuoteItem not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setQuoteItemIdAndRetrieve(): void
    {
        $qia = new QuoteItemAmount();
        $qia->setQuoteItemId(42);

        Assert::same($qia->reqQuoteItemId(), 42);
    }

    public function constructorDefaultsAllAmountsToZero(): void
    {
        $qia = new QuoteItemAmount();

        Assert::same($qia->getSubtotal(), 0.00);
        Assert::same($qia->getTaxTotal(), 0.00);
        Assert::same($qia->getDiscount(), 0.00);
        Assert::same($qia->getCharge(), 0.00);
        Assert::same($qia->getAllowance(), 0.00);
        Assert::same($qia->getTotal(), 0.00);
    }

    public function settersAndGetters(): void
    {
        $qia = new QuoteItemAmount(
            quote_item_id: 3,
            subtotal: 50.00,
            tax_total: 10.00,
            discount: 5.00,
            charge: 2.00,
            allowance: 1.00,
            total: 56.00,
        );

        Assert::same($qia->reqQuoteItemId(), 3);
        Assert::same($qia->getSubtotal(), 50.00);
        Assert::same($qia->getTaxTotal(), 10.00);
        Assert::same($qia->getDiscount(), 5.00);
        Assert::same($qia->getCharge(), 2.00);
        Assert::same($qia->getAllowance(), 1.00);
        Assert::same($qia->getTotal(), 56.00);
    }

    public function settersMutateValues(): void
    {
        $qia = new QuoteItemAmount();
        $qia->setSubtotal(75.00);
        $qia->setTaxTotal(15.00);
        $qia->setDiscount(7.50);
        $qia->setCharge(3.00);
        $qia->setAllowance(2.00);
        $qia->setTotal(83.50);

        Assert::same($qia->getSubtotal(), 75.00);
        Assert::same($qia->getTaxTotal(), 15.00);
        Assert::same($qia->getDiscount(), 7.50);
        Assert::same($qia->getCharge(), 3.00);
        Assert::same($qia->getAllowance(), 2.00);
        Assert::same($qia->getTotal(), 83.50);
    }

    public function quoteItemRelationNullByDefault(): void
    {
        Assert::null((new QuoteItemAmount())->getQuoteItem());
    }
}

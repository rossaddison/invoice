<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\SalesOrderAmount;

use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class SalesOrderAmountTest
{
    public function defaultsToUnpersisted(): void
    {
        Assert::false((new SalesOrderAmount())->hasIdentity());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new SalesOrderAmount())->reqId();
    }

    public function reqIdThrowsWithCorrectMessage(): void
    {
        try {
            (new SalesOrderAmount())->reqId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'SalesOrderAmount not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setIdMakesEntityIdentifiable(): void
    {
        $soa = new SalesOrderAmount();
        $soa->setId(9);

        Assert::true($soa->hasIdentity());
        Assert::same($soa->reqId(), 9);
    }

    public function salesOrderIdNullByDefault(): void
    {
        Assert::null((new SalesOrderAmount())->getSalesOrderId());
    }

    public function setSalesOrderIdAndRetrieve(): void
    {
        $soa = new SalesOrderAmount();
        $soa->setSalesOrderId(55);

        Assert::same($soa->getSalesOrderId(), 55);
    }

    public function constructorDefaultsAllAmountsToZero(): void
    {
        $soa = new SalesOrderAmount();

        Assert::same($soa->getItemSubtotal(), 0.00);
        Assert::same($soa->getItemTaxTotal(), 0.00);
        Assert::same($soa->getPackhandleshipTotal(), 0.00);
        Assert::same($soa->getPackhandleshipTax(), 0.00);
        Assert::same($soa->getTaxTotal(), 0.00);
        Assert::same($soa->getTotal(), 0.00);
    }

    public function settersAndGetters(): void
    {
        $soa = new SalesOrderAmount(
            id: 2,
            sales_order_id: 10,
            item_subtotal: 300.00,
            item_tax_total: 60.00,
            packhandleship_total: 20.00,
            packhandleship_tax: 4.00,
            tax_total: 64.00,
            total: 384.00,
        );

        Assert::same($soa->reqId(), 2);
        Assert::same($soa->getSalesOrderId(), 10);
        Assert::same($soa->getItemSubtotal(), 300.00);
        Assert::same($soa->getItemTaxTotal(), 60.00);
        Assert::same($soa->getPackhandleshipTotal(), 20.00);
        Assert::same($soa->getPackhandleshipTax(), 4.00);
        Assert::same($soa->getTaxTotal(), 64.00);
        Assert::same($soa->getTotal(), 384.00);
    }

    public function settersMutateValues(): void
    {
        $soa = new SalesOrderAmount();
        $soa->setItemSubtotal(150.00);
        $soa->setItemTaxTotal(30.00);
        $soa->setPackhandleshipTotal(8.00);
        $soa->setPackhandleshipTax(1.60);
        $soa->setTaxTotal(31.60);
        $soa->setTotal(189.60);

        Assert::same($soa->getItemSubtotal(), 150.00);
        Assert::same($soa->getItemTaxTotal(), 30.00);
        Assert::same($soa->getPackhandleshipTotal(), 8.00);
        Assert::same($soa->getPackhandleshipTax(), 1.60);
        Assert::same($soa->getTaxTotal(), 31.60);
        Assert::same($soa->getTotal(), 189.60);
    }

    public function salesOrderRelationNullByDefault(): void
    {
        Assert::null((new SalesOrderAmount())->getSalesOrder());
    }
}

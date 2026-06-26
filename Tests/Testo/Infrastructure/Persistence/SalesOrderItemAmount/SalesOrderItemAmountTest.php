<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\SalesOrderItemAmount;

use App\Infrastructure\Persistence\SalesOrderItemAmount\SalesOrderItemAmount;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class SalesOrderItemAmountTest
{
    public function defaultsToUnpersisted(): void
    {
        Assert::false((new SalesOrderItemAmount())->hasIdentity());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new SalesOrderItemAmount())->reqId();
    }

    public function reqIdThrowsWithCorrectMessage(): void
    {
        try {
            (new SalesOrderItemAmount())->reqId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'SalesOrderItemAmount not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setIdMakesEntityIdentifiable(): void
    {
        $soia = new SalesOrderItemAmount();
        $soia->setId(3);

        Assert::true($soia->hasIdentity());
        Assert::same($soia->reqId(), 3);
    }

    #[ExpectException(\LogicException::class)]
    public function reqSalesOrderItemIdThrowsWhenNull(): void
    {
        (new SalesOrderItemAmount())->reqSalesOrderItemId();
    }

    public function reqSalesOrderItemIdThrowsWithCorrectMessage(): void
    {
        try {
            (new SalesOrderItemAmount())->reqSalesOrderItemId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'SalesOrderItem not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setSalesOrderItemIdAndRetrieve(): void
    {
        $soia = new SalesOrderItemAmount();
        $soia->setSalesOrderItemId(88);

        Assert::same($soia->reqSalesOrderItemId(), 88);
    }

    public function constructorDefaultsAllAmountsToZero(): void
    {
        $soia = new SalesOrderItemAmount();

        Assert::same($soia->getSubtotal(), 0.00);
        Assert::same($soia->getTaxTotal(), 0.00);
        Assert::same($soia->getDiscount(), 0.00);
        Assert::same($soia->getCharge(), 0.00);
        Assert::same($soia->getAllowance(), 0.00);
        Assert::same($soia->getTotal(), 0.00);
    }

    public function settersAndGetters(): void
    {
        $soia = new SalesOrderItemAmount(
            sales_order_item_id: 6,
            subtotal: 120.00,
            tax_total: 24.00,
            discount: 12.00,
            charge: 5.00,
            allowance: 3.00,
            total: 134.00,
        );

        Assert::same($soia->reqSalesOrderItemId(), 6);
        Assert::same($soia->getSubtotal(), 120.00);
        Assert::same($soia->getTaxTotal(), 24.00);
        Assert::same($soia->getDiscount(), 12.00);
        Assert::same($soia->getCharge(), 5.00);
        Assert::same($soia->getAllowance(), 3.00);
        Assert::same($soia->getTotal(), 134.00);
    }

    public function settersMutateValues(): void
    {
        $soia = new SalesOrderItemAmount();
        $soia->setSubtotal(90.00);
        $soia->setTaxTotal(18.00);
        $soia->setDiscount(9.00);
        $soia->setCharge(4.00);
        $soia->setAllowance(2.00);
        $soia->setTotal(101.00);

        Assert::same($soia->getSubtotal(), 90.00);
        Assert::same($soia->getTaxTotal(), 18.00);
        Assert::same($soia->getDiscount(), 9.00);
        Assert::same($soia->getCharge(), 4.00);
        Assert::same($soia->getAllowance(), 2.00);
        Assert::same($soia->getTotal(), 101.00);
    }

    public function salesOrderItemRelationNullByDefault(): void
    {
        Assert::null((new SalesOrderItemAmount())->getSalesOrderItem());
    }
}

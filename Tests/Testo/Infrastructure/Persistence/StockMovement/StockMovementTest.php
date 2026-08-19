<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\StockMovement;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\StockMovement\StockMovement;
use App\Invoice\Enum\StockMovementType;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class StockMovementTest
{
    public function defaultsToUnpersistedAdjustment(): void
    {
        $movement = new StockMovement();

        Assert::false($movement->isPersisted());
        Assert::same(StockMovementType::Adjustment, $movement->getType());
        Assert::same(0.00, $movement->getQuantityDelta());
        Assert::null($movement->getInv());
        Assert::null($movement->getInvId());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new StockMovement())->reqId();
    }

    #[ExpectException(\LogicException::class)]
    public function reqProductIdThrowsWhenNotSet(): void
    {
        (new StockMovement())->reqProductId();
    }

    public function setIdMakesEntityPersisted(): void
    {
        $movement = new StockMovement();
        $movement->setId(7);

        Assert::true($movement->isPersisted());
        Assert::same(7, $movement->reqId());
    }

    public function constructorSetsAllFields(): void
    {
        $movement = new StockMovement(
            product_id: 5,
            type: StockMovementType::Sale->value,
            quantity_delta: -3.00,
            inv_id: 12,
            note: 'Webshop order #12',
        );

        Assert::same(5, $movement->reqProductId());
        Assert::same(StockMovementType::Sale, $movement->getType());
        Assert::same(-3.00, $movement->getQuantityDelta());
        Assert::same(12, $movement->getInvId());
        Assert::same('Webshop order #12', $movement->getNote());
    }

    public function settersMutateValues(): void
    {
        $movement = new StockMovement();
        $movement->setProductId(9);
        $movement->setType(StockMovementType::Receipt);
        $movement->setQuantityDelta(25.00);
        $movement->setInvId(null);
        $movement->setNote('Restock from supplier');

        Assert::same(9, $movement->reqProductId());
        Assert::same(StockMovementType::Receipt, $movement->getType());
        Assert::same(25.00, $movement->getQuantityDelta());
        Assert::null($movement->getInvId());
        Assert::same('Restock from supplier', $movement->getNote());
    }

    public function productAndInvRelationsAreSettable(): void
    {
        $product = new Product();
        $product->setId(1);
        $inv = new Inv();
        $inv->setId(2);

        $movement = new StockMovement();
        $movement->setProduct($product);
        $movement->setInv($inv);

        Assert::same($product, $movement->getProduct());
        Assert::same($inv, $movement->getInv());
    }
}

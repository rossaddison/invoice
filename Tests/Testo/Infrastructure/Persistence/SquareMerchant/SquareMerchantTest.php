<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\SquareMerchant;

use App\Infrastructure\Persistence\SquareMerchant\SquareMerchant;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * Covers SquareMerchant — Square's own per-provider payment audit record,
 * storing both order_id (webhook-lookup) and payment_id (refund-capable),
 * unlike the generic Merchant table's single provider_reference. See the
 * entity's own docblock and docs/SQUARE_MERCHANT_PER_PROVIDER_ENTITY_AUGUST_2026.md.
 */
#[Test]
final class SquareMerchantTest
{
    public function defaultsToUnpersisted(): void
    {
        Assert::false((new SquareMerchant())->isPersisted());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new SquareMerchant())->reqId();
    }

    public function reqIdThrowsWithCorrectMessage(): void
    {
        try {
            (new SquareMerchant())->reqId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'SquareMerchant not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setIdMakesEntityPersisted(): void
    {
        $sm = new SquareMerchant();
        $sm->setId(7);

        Assert::true($sm->isPersisted());
        Assert::same($sm->reqId(), 7);
    }

    #[ExpectException(\LogicException::class)]
    public function reqInvIdThrowsWhenNull(): void
    {
        (new SquareMerchant())->reqInvId();
    }

    public function setInvIdMakesReqInvIdSucceed(): void
    {
        $sm = new SquareMerchant();
        $sm->setInvId(42);

        Assert::same($sm->reqInvId(), 42);
    }

    public function orderIdAndPaymentIdDefaultToNull(): void
    {
        $sm = new SquareMerchant();

        Assert::null($sm->getOrderId());
        Assert::null($sm->getPaymentId());
    }

    public function orderIdAndPaymentIdAreIndependentlySettable(): void
    {
        $sm = new SquareMerchant();
        $sm->setOrderId('order-abc');
        $sm->setPaymentId('payment-xyz');

        // The whole reason this entity exists — two distinct provider
        // references, not one shared string.
        Assert::same($sm->getOrderId(), 'order-abc');
        Assert::same($sm->getPaymentId(), 'payment-xyz');
        Assert::notSame($sm->getOrderId(), $sm->getPaymentId());
    }

    public function successfulDefaultsToTrue(): void
    {
        Assert::true((new SquareMerchant())->getSuccessful());
    }

    public function responseAndReferenceRoundTrip(): void
    {
        $sm = new SquareMerchant();
        $sm->setResponse('payment confirmed');
        $sm->setReference('INV-0001-square-payment-xyz');

        Assert::same($sm->getResponse(), 'payment confirmed');
        Assert::same($sm->getReference(), 'INV-0001-square-payment-xyz');
    }
}

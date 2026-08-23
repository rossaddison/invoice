<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\WorldpayMerchant;

use App\Infrastructure\Persistence\WorldpayMerchant\WorldpayMerchant;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * Covers WorldpayMerchant — Worldpay's own per-provider payment audit
 * record, storing payment_id (short, correlatable), self_href (the
 * long opaque HATEOAS query URL verifyPayment()/refund() actually
 * call), transaction_reference (this app's own generated
 * webhook-correlation key), and pending_action_href (the current 3DS
 * step's next action). See the entity's own docblock.
 */
#[Test]
final class WorldpayMerchantTest
{
    public function defaultsToUnpersisted(): void
    {
        Assert::false((new WorldpayMerchant())->isPersisted());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new WorldpayMerchant())->reqId();
    }

    public function reqIdThrowsWithCorrectMessage(): void
    {
        try {
            (new WorldpayMerchant())->reqId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'WorldpayMerchant not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setIdMakesEntityPersisted(): void
    {
        $wm = new WorldpayMerchant();
        $wm->setId(7);

        Assert::true($wm->isPersisted());
        Assert::same($wm->reqId(), 7);
    }

    #[ExpectException(\LogicException::class)]
    public function reqInvIdThrowsWhenNull(): void
    {
        (new WorldpayMerchant())->reqInvId();
    }

    public function setInvIdMakesReqInvIdSucceed(): void
    {
        $wm = new WorldpayMerchant();
        $wm->setInvId(42);

        Assert::same($wm->reqInvId(), 42);
    }

    public function transactionReferencePaymentIdSelfHrefAndPendingActionHrefDefaultToNull(): void
    {
        $wm = new WorldpayMerchant();

        Assert::null($wm->getTransactionReference());
        Assert::null($wm->getPaymentId());
        Assert::null($wm->getSelfHref());
        Assert::null($wm->getPendingActionHref());
    }

    public function allFourProviderFieldsAreIndependentlySettable(): void
    {
        $wm = new WorldpayMerchant();
        $wm->setTransactionReference('inv-1-abc123');
        $wm->setPaymentId('payI-1234');
        $wm->setSelfHref('https://try.access.worldpay.com/api/payments/opaque-token');
        $wm->setPendingActionHref('https://try.access.worldpay.com/api/payments/opaque-token/3dsDeviceData');

        // The whole reason this entity exists — a short correlation key,
        // a short id, and a long opaque href are genuinely different
        // things, not one shared string.
        Assert::same($wm->getTransactionReference(), 'inv-1-abc123');
        Assert::same($wm->getPaymentId(), 'payI-1234');
        Assert::same($wm->getSelfHref(), 'https://try.access.worldpay.com/api/payments/opaque-token');
        Assert::same($wm->getPendingActionHref(), 'https://try.access.worldpay.com/api/payments/opaque-token/3dsDeviceData');
    }

    public function pendingActionHrefCanBeClearedBackToNull(): void
    {
        $wm = new WorldpayMerchant();
        $wm->setPendingActionHref('https://try.access.worldpay.com/api/payments/opaque-token/3dsChallenges');
        $wm->setPendingActionHref(null);

        Assert::null($wm->getPendingActionHref());
    }

    public function successfulDefaultsToTrue(): void
    {
        Assert::true((new WorldpayMerchant())->getSuccessful());
    }

    public function responseAndReferenceRoundTrip(): void
    {
        $wm = new WorldpayMerchant();
        $wm->setResponse('Worldpay payment settled for invoice INV-0001.');
        $wm->setReference('INV-0001-inv-1-abc123');

        Assert::same($wm->getResponse(), 'Worldpay payment settled for invoice INV-0001.');
        Assert::same($wm->getReference(), 'INV-0001-inv-1-abc123');
    }
}

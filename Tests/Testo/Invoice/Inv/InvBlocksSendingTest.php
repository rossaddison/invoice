<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use Testo\Assert;
use Testo\Test;

/**
 * Covers Inv::blocksSending() — the do_not_send guard every status-to-sent
 * code path checks before writing status_id 2 (see InvService::applyInvReferenceIds(),
 * InvBatchEmailService, Trait\Email, Trait\Typescript::markAsSent(),
 * Setting\Trait\SettingInvoiceMarkTrait::invoiceMarkSent()).
 */
#[Test]
final class InvBlocksSendingTest
{
    public function newInvoiceDoesNotBlockSendingByDefault(): void
    {
        $inv = new Inv();

        Assert::false($inv->getDoNotSend());
        Assert::same('', $inv->getDoNotSendReason());
        Assert::false($inv->blocksSending());
    }

    public function settingDoNotSendBlocksSending(): void
    {
        $inv = new Inv();

        $inv->setDoNotSend(true);
        $inv->setDoNotSendReason('property_inaccessible');

        Assert::true($inv->getDoNotSend());
        Assert::same('property_inaccessible', $inv->getDoNotSendReason());
        Assert::true($inv->blocksSending());
    }

    public function clearingDoNotSendUnblocksSending(): void
    {
        $inv = new Inv();
        $inv->setDoNotSend(true);
        $inv->setDoNotSendReason('job_incomplete');

        $inv->setDoNotSend(false);
        $inv->setDoNotSendReason('');

        Assert::false($inv->getDoNotSend());
        Assert::same('', $inv->getDoNotSendReason());
        Assert::false($inv->blocksSending());
    }
}

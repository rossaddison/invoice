<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\{
    InvAllowanceCharge\InvAllowanceCharge,
    Inv\Inv, InvItemAllowanceCharge\InvItemAllowanceCharge,
    InvItem\InvItem, InvItemAmount\InvItemAmount, InvAmount\InvAmount,
    InvCustom\InvCustom, InvRecurring\InvRecurring, InvSentLog\InvSentLog,
    InvTaxRate\InvTaxRate, Payment\Payment, PaymentCustom\PaymentCustom
};

use App\Invoice\{
    Inv\InvFlushCoreDeps,
    Inv\InvFlushItemDeps,
};
use Psr\Http\Message\ResponseInterface as Response;

trait Flush
{
    public function flush(
        InvFlushCoreDeps $core,
        InvFlushItemDeps $item,
    ): Response {
        /** @var InvSentLog $isl */
        foreach ($core->islR->findAllPreloaded() as $isl) {
            $core->islR->delete($isl);
        }
        /** @var InvRecurring $ir */
        foreach ($core->irR->findAllPreloaded() as $ir) {
            $core->irR->delete($ir);
        }
        /** @var InvItemAmount $iia */
        foreach ($item->iiaR->findAllPreloaded() as $iia) {
            $item->iiaR->delete($iia);
        }
        /** @var InvAmount $ia */
        foreach ($core->iaR->findAllPreloaded() as $ia) {
            $core->iaR->delete($ia);
        }
        /** @var InvTaxRate $itr */
        foreach ($item->itrR->findAllPreloaded() as $itr) {
            $item->itrR->delete($itr);
        }
        /** @var InvItemAllowanceCharge $iiac */
        foreach ($item->aciiR->findAllPreloaded() as $iiac) {
            $item->aciiR->delete($iiac);
        }
        /** @var InvItem $ii */
        foreach ($core->iiR->findAllPreloaded() as $ii) {
            $core->iiR->delete($ii);
        }
        /** @var InvCustom $ic */
        foreach ($core->icR->findAllPreloaded() as $ic) {
            $core->icR->delete($ic);
        }
        /** @var InvAllowanceCharge $iac */
        foreach ($item->aciR->findAllPreloaded() as $iac) {
            $item->aciR->delete($iac);
        }
        /** @var PaymentCustom $pc */
        foreach ($item->pcR->findAllPreloaded() as $pc) {
            $item->pcR->delete($pc);
        }
        /** @var Payment $pym */
        foreach ($item->pymR->findAllPreloaded() as $pym) {
            $item->pymR->delete($pym);
        }
        /** @var Inv $i */
        foreach ($core->iR->findAllPreloaded() as $i) {
            $core->iR->delete($i);
        }
        $this->flashMessage('danger',
            $this->translator->translate('caution.deleted.invoices'));
        return $this->webService->getRedirectResponse('inv/index');
    }
}

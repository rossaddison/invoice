<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Entity\
{
    Inv, InvItemAllowanceCharge, InvAllowanceCharge, InvItem, InvItemAmount,
    InvAmount, InvCustom, InvRecurring, InvSentLog, InvTaxRate, Payment,
    PaymentCustom};

use App\Invoice\{
    Inv\InvRepository as IR,
    InvAllowanceCharge\InvAllowanceChargeRepository as ACIR,
    InvCustom\InvCustomRepository as ICR,
    InvItem\InvItemRepository as IIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvAmount\InvAmountRepository as IAR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    InvRecurring\InvRecurringRepository as IRR,
    InvSentLog\InvSentLogRepository as ISLR,
    InvTaxRate\InvTaxRateRepository as ITRR,
    Payment\PaymentRepository as PYMR,
    PaymentCustom\PaymentCustomRepository as PCR
};
use Psr\Http\Message\ResponseInterface as Response;

trait Flush
{
    public function flush(
        ISLR $islR,
        IRR $irR,
        IIAR $iiaR,
        IAR $iaR,
        ITRR $itrR,
        IIR $iiR,
        ICR $icR,
        ACIIR $aciiR,
        ACIR $aciR,
        PCR $pcR,
        PYMR $pymR,
        IR $iR,
    ): Response {
        /** @var InvSentLog $isl */
        foreach ($islR->findAllPreloaded() as $isl) {
            $islR->delete($isl);
        }
        /** @var InvRecurring $ir */
        foreach ($irR->findAllPreloaded() as $ir) {
            $irR->delete($ir);
        }
        /** @var InvItemAmount $iia */
        foreach ($iiaR->findAllPreloaded() as $iia) {
            $iiaR->delete($iia);
        }
        /** @var InvAmount $ia */
        foreach ($iaR->findAllPreloaded() as $ia) {
            $iaR->delete($ia);
        }
        /** @var InvTaxRate $itr */
        foreach ($itrR->findAllPreloaded() as $itr) {
            $itrR->delete($itr);
        }
        /** @var InvItemAllowanceCharge $iiac */
            foreach ($aciiR->findAllPreloaded() as $iiac) {
            $aciiR->delete($iiac);
        }
        /** @var InvItem $ii */
        foreach ($iiR->findAllPreloaded() as $ii) {
            $iiR->delete($ii);
        }
        /** @var InvCustom $ic */
        foreach ($icR->findAllPreloaded() as $ic) {
            $icR->delete($ic);
        }
        /** @var InvAllowanceCharge $iac */
        foreach ($aciR->findAllPreloaded() as $iac) {
            $aciR->delete($iac);
        }
        /** @var PaymentCustom $pc */
        foreach ($pcR->findAllPreloaded() as $pc) {
            $pcR->delete($pc);
        }
        /** @var Payment $pym */
        foreach ($pymR->findAllPreloaded() as $pym) {
            $pymR->delete($pym);
        }
        /** @var Inv $i */
        foreach ($iR->findAllPreloaded() as $i) {
            $iR->delete($i);
        }
        $this->flashMessage('danger',
            $this->translator->translate('caution.deleted.invoices'));
        return $this->webService->getRedirectResponse('inv/index');
    } 
}

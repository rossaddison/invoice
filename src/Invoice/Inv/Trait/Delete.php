<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\{
    InvItem\InvItemService,
    InvAmount\InvAmountService, 
    InvTaxRate\InvTaxRateService, InvCustom\InvCustomService,
    Inv\InvRepository as IR,
    InvAllowanceCharge\InvAllowanceChargeRepository as ACIR,
    InvCustom\InvCustomRepository as ICR,
    InvItem\InvItemRepository as IIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvAmount\InvAmountRepository as IAR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    InvTaxRate\InvTaxRateRepository as ITRR
};
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Psr\Http\Message\ResponseInterface as Response;

trait Delete
{
    public function delete(
        #[RouteArgument('id')]
        int $id,
        IR $invRepo,
        ACIR $aciR,
        ACIIR $aciiR,
        IIAR $iiaR,
        ICR $icR,
        InvCustomService $icS,
        IIR $iiR,
        InvItemService $iiS,
        ITRR $itrR,
        InvTaxRateService $itrS,
        IAR $iaR,
        InvAmountService $iaS
    ): Response {
        try {
            $inv = $this->inv($id, $invRepo);
            if ($inv) {
                $this->inv_service->deleteInv(
                    $inv, $aciR, $aciiR, $iiaR, $icR, $icS, $iiR, $iiS, $itrR,
                        $itrS, $iaR, $iaS);
                $this->flashMessage('info',
                    $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('inv/index');
            }
            return $this->webService->getRedirectResponse('inv/index');
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            unset($e);
            return $this->webService->getRedirectResponse('inv/index');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\{
    Client\ClientRepository as CR,
    CustomValue\CustomValueRepository as CVR,
    CustomField\CustomFieldRepository as CFR,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Inv\InvRepository as IR,
    InvAllowanceCharge\InvAllowanceChargeRepository as ACIR,
    InvCustom\InvCustomRepository as ICR,
    InvItem\InvItemRepository as IIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvAmount\InvAmountRepository as IAR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    InvTaxRate\InvTaxRateRepository as ITRR,
    SalesOrder\SalesOrderRepository as SOR,
    UserInv\UserInvRepository as UIR
};
use Yiisoft\{Html\Html, Json\Json, Router\HydratorAttribute\RouteArgument
};
use Psr\Http\Message\ResponseInterface as Response;

trait HtmlTrait
{
    public function html(#[RouteArgument('include')] int $include, CR $cR,
        CVR $cvR, CFR $cfR, DLR $dlR, ACIR $aciR, IAR $iaR, ICR $icR,
            IIR $iiR, ACIIR $aciiR, IIAR $iiaR, IR $iR, ITRR $itrR,
            UIR $uiR, SOR $soR): Response
    {
        $inv_id = (string) $this->session->get('inv_id');
        $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ?
                $iaR->repoInvquery((int) $inv_id) : null);
        if ($inv_amount) {
            $custom = ($include === 1);
            $inv_custom_values = $this->invCustomValues($inv_id, $icR);
            $inv = $iR->repoInvUnloadedquery($inv_id);
            if ($inv) {
                $so = ($inv->getSoId() ? $soR->repoSalesOrderLoadedquery(
                    $inv->getSoId()) : null);
                $html = $this->pdfHelper->generateInvHtml(
                    $inv_id,
                    $inv->getUserId(),
                    $custom,
                    $so,
                    $inv_amount,
                    $inv_custom_values,
                    $cR,
                    $cvR,
                    $cfR,
                    $dlR,
                    $aciR,
                    $iiR,
                    $aciiR,
                    $iiaR,
                    $inv,
                    $itrR,
                    $uiR,
                    $this->webViewRenderer,
                );
                return $this->factory->createResponse('<pre>'
                    . Html::encode($html) . '</pre>');
            } // $inv
            return $this->factory->createResponse(
                Json::encode(['success' => 0]));
        } // $inv_amount
        return $this->factory->createResponse(
                Json::encode(['success' => 0]));
    }
}

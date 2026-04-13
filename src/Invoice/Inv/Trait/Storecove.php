<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\{
    ClientPeppol\ClientPeppolRepository as cpR,
    Contract\ContractRepository as ContractRepo,
    Delivery\DeliveryRepository as DelRepo,
    DeliveryParty\DeliveryPartyRepository as DelPartyRepo,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Inv\InvRepository as IR,
    InvAllowanceCharge\InvAllowanceChargeRepository as ACIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    PostalAddress\PostalAddressRepository as paR,
    ProductProperty\ProductPropertyRepository as ppR,
    SalesOrder\SalesOrderRepository as SOR,
    SalesOrderItem\SalesOrderItemRepository as SOIR,
    TaxRate\TaxRateRepository as TRR,
    UnitPeppol\UnitPeppolRepository as unpR,
    Upload\UploadRepository as UPR,    
};
use App\Invoice\Helpers\StoreCove\StoreCoveHelper;
use Yiisoft\{Json\Json, Router\HydratorAttribute\RouteArgument, User\CurrentUser};
use Psr\Http\Message\ResponseInterface as Response;

trait Storecove
{
    /**
     * Related logic: see https://www.storecove.com/docs#_json_object
     * Related logic: see StoreCove API key stored under Online Payment keys
     * under Settings...View...Online Payment
     */
    public function storecove(
        #[RouteArgument('id')]
        int $id,
        CurrentUser $currentUser,
        cpR $cpR,
        IIAR $iiaR,
        IR $invRepo,
        ContractRepo $contractRepo,
        DelRepo $delRepo,
        DelPartyRepo $delPartyRepo,
        DLR $dlR,
        paR $paR,
        ppR $ppR,
        unpR $unpR,
        SOR $soR,
        UPR $upR,
        ACIR $aciR,
        ACIIR $aciiR,
        SOIR $soiR,
        TRR $trR,
    ): Response {
        if ($currentUser->isGuest()) {
            return $this->webService->getNotFoundResponse();
        }
        // Load the inv's HASONE relation 'invamount'
        if ($id) {
            $invoice = $invRepo->repoInvLoadInvAmountquery((string) $id);
            if ($invoice) {
                $client_id = $invoice->getClient()?->getClientId();
                if (null !== $client_id) {
                    $delivery_location = $dlR->repoDeliveryLocationquery(
                        (string) $client_id);
                    if (null !== $delivery_location) {
                        $storecovehelper = new StoreCoveHelper(
                            $this->sR,
                            $this->delRepo,
                            $delivery_location,
                            $this->translator
                        );
                        $storecove_array =
                    $storecovehelper->maximumPreJsonPhpObjectForAnInvoice(
                            $soR,
                            $invoice,
                            $iiaR,
                            //$iiR,
                            $contractRepo,
                            $delRepo,
                            $delPartyRepo,
                            $paR,
                            $cpR,
                            $ppR,
                            $unpR,
                            $upR,
                            $aciR,
                            $aciiR,
                            $soiR,
                            $trR,
                        );
                        echo Json::encode(
                            $storecove_array,
                            JSON_UNESCAPED_SLASHES
                            | JSON_UNESCAPED_UNICODE
                            | JSON_THROW_ON_ERROR,
                            512,
                        );
                    }
                }
            }
        }
        exit;
    }
}

<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\{
    Inv\InvPeppolChargeDeps,
    Inv\InvPeppolCoreDeps,
    Inv\InvPeppolNetworkDeps,
    ProductProperty\ProductPropertyRepository as ppR,
};
use App\Invoice\Helpers\StoreCove\{
    StoreCoveHelper,
    StoreCoveHelperChargeDeps,
    StoreCoveHelperInvDeps,
    StoreCoveHelperNetDeps,
};
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
        InvPeppolCoreDeps $core,
        InvPeppolNetworkDeps $net,
        InvPeppolChargeDeps $charge,
        ppR $ppR,
    ): Response {
        if ($currentUser->isGuest()) {
            return $this->webService->getNotFoundResponse();
        }
        // Load the inv's HASONE relation 'invamount'
        if ($id) {
            $invoice = $core->invRepo->repoInvLoadInvAmountquery($id);
            if ($invoice) {
                $client_id = $invoice->getClient()?->reqId();
                if (null !== $client_id) {
                    $delivery_location = $core->dlR->repoDeliveryLocationquery($client_id);
                    if (null !== $delivery_location) {
                        $storecovehelper = new StoreCoveHelper(
                            $this->sR,
                            $this->delRepo,
                            $delivery_location,
                            $this->translator
                        );
                        $storecove_array =
                    $storecovehelper->maximumPreJsonPhpObjectForAnInvoice(
                            $invoice,
                            new StoreCoveHelperInvDeps(
                                $core->soR, $core->iiaR, $core->paR, $core->cpR,
                            ),
                            new StoreCoveHelperNetDeps(
                                $net->contractRepo, $net->delRepo,
                                $net->delPartyRepo, $net->unpR, $net->upR, $ppR,
                            ),
                            new StoreCoveHelperChargeDeps(
                                $charge->aciR, $charge->aciiR,
                                $charge->soiR, $charge->trR,
                            ),
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

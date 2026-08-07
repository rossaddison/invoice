<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\PaymentInformation\PaymentGatewayInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Router\CurrentRoute;

/**
 * The load-invoice + isConfigured + positive-balance guard chain every
 * gateway's own `*InForm()` action starts with — previously duplicated
 * near-verbatim across Square/PayPal/Razorpay/Robokassa/YooKassa's own
 * controllers (each had its own resolveConfiguredInvoiceWithBalance()/
 * requireXConfigured()/requirePositiveBalance() trio, differing only in
 * which service and gateway display name they referenced). SonarCloud's
 * copy-paste detector flagged the result — see
 * docs/PAYMENT_GATEWAY_GUARD_TRAIT_AUGUST_2026.md.
 *
 * Requires the consuming class to have: a private loadInvoice(CurrentRoute):
 * Response|Inv method (every gateway controller already does), $webService
 * (WebControllerService), $translator (TranslatorInterface), $iaR
 * (InvAmountRepository), and the FlashMessage trait's flashMessage() method.
 */
trait PaymentGatewayGuardTrait
{
    /**
     * @return Response|array{invoice: Inv, balance: float}
     */
    private function resolveConfiguredInvoiceWithBalance(
        CurrentRoute $currentRoute,
        PaymentGatewayInterface $service,
        string $gatewayLabel,
    ): Response|array {
        $invoice = $this->loadInvoice($currentRoute);
        if ($invoice instanceof Response) {
            return $invoice;
        }

        $configured = $this->requireGatewayConfigured($service, $gatewayLabel);
        if ($configured instanceof Response) {
            return $configured;
        }

        return $this->requirePositiveBalance($invoice);
    }

    private function requireGatewayConfigured(PaymentGatewayInterface $service, string $gatewayLabel): ?Response
    {
        if (!$service->isConfigured()) {
            $this->flashMessage('warning', "{$gatewayLabel} payment gateway is not properly configured.");
            return $this->webService->getNotFoundResponse();
        }
        return null;
    }

    /**
     * @return Response|array{invoice: Inv, balance: float}
     */
    private function requirePositiveBalance(Inv $invoice): Response|array
    {
        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        $balance = $invoiceAmountRecord->getBalance() ?? 0.00;
        if ($balance <= 0.00) {
            $this->flashMessage('warning', $this->translator->translate('already.paid'));
            return $this->webService->getNotFoundResponse();
        }
        return ['invoice' => $invoice, 'balance' => $balance];
    }
}

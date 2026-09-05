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
 * A guard failure renders a real page via the shared `payment_message`
 * partial (the same one every gateway's own `*Complete()` action already
 * renders successfully) rather than flashMessage() + getNotFoundResponse()
 * — that response has no body and never goes through this app's view
 * renderer at all, so the flash was silently never shown, for every
 * gateway using this trait (found live 2026-09-05, chasing an identical
 * bug in BitPayPaymentController's own createPayment() failure path,
 * which doesn't go through this trait's own guard chain at all but has
 * the exact same flash-then-bare-404 shape). `renderGuardFailure()` is
 * deliberately reused directly by `BitPayPaymentController::bitPayInForm()`
 * for that unrelated failure too, rather than a second near-duplicate
 * rendering implementation — its own docblock explains why.
 *
 * Requires the consuming class to have: a private loadInvoice(CurrentRoute):
 * Response|Inv method (every gateway controller already does), $webService
 * (WebControllerService), $translator (TranslatorInterface), $iaR
 * (InvAmountRepository), $sR (SettingRepository), and $webViewRenderer
 * (WebViewRenderer) — confirmed present, identically named, on all 10
 * current consumers of this trait.
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

        $configured = $this->requireGatewayConfigured($invoice, $service, $gatewayLabel);
        if ($configured instanceof Response) {
            return $configured;
        }

        return $this->requirePositiveBalance($invoice, $service, $gatewayLabel);
    }

    private function requireGatewayConfigured(
        Inv $invoice,
        PaymentGatewayInterface $service,
        string $gatewayLabel,
    ): ?Response {
        if (!$service->isConfigured()) {
            return $this->renderGuardFailure(
                $invoice,
                $service,
                $gatewayLabel,
                "{$gatewayLabel} payment gateway is not properly configured.",
            );
        }
        return null;
    }

    /**
     * @return Response|array{invoice: Inv, balance: float}
     */
    private function requirePositiveBalance(
        Inv $invoice,
        PaymentGatewayInterface $service,
        string $gatewayLabel,
    ): Response|array {
        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());
        $balance = $invoiceAmountRecord->getBalance() ?? 0.00;
        if ($balance <= 0.00) {
            return $this->renderGuardFailure(
                $invoice,
                $service,
                $gatewayLabel,
                $this->translator->translate('already.paid'),
            );
        }
        return ['invoice' => $invoice, 'balance' => $balance];
    }

    /**
     * See this trait's own docblock for why a real rendered page, not
     * flashMessage() + getNotFoundResponse(). Reuses the identical
     * `payment_message`/`payment_completion_page` pairing every gateway's
     * own `*Complete()` action already renders — passing $message
     * directly as the partial's own `message` string sidesteps the Flash
     * session mechanism entirely, rather than depending on it working.
     */
    private function renderGuardFailure(
        Inv $invoice,
        PaymentGatewayInterface $service,
        string $gatewayLabel,
        string $message,
    ): Response {
        $view_data = [
            'render' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/paymentinformation/payment_message',
                [
                    'heading' => "Unable to start the {$gatewayLabel} payment",
                    'message' => $message,
                    'url' => 'inv/urlKey',
                    'url_key' => $invoice->getUrlKey(),
                    'gateway' => $gatewayLabel,
                    'sandbox_url' => (string) ($this->sR->sandboxUrlArray()[$service->getDriverKey()] ?? ''),
                ],
            ),
        ];
        return $this->webViewRenderer->render('payment_completion_page', $view_data);
    }
}

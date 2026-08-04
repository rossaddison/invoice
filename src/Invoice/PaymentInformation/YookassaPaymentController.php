<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\YookassaWebhookHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Kept separate from PaymentInformationController deliberately — same
 * reasoning as AdyenPaymentController/GoCardlessPaymentController/
 * RobokassaPaymentController (the shared controller is already at
 * SonarQube's php:S1448 method-count ceiling).
 *
 * Currently only the webhook notification is wired here — the
 * customer-facing "in-form"/guest-checkout redirect step (matching the
 * other gateways' `xInForm()` actions and PaymentInformationController's big
 * dispatch switch) is a follow-up, not built in this pass. See
 * docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md.
 */
final class YookassaPaymentController
{
    public function __construct(
        private readonly YookassaWebhookHandler $yookassaWebhookHandler,
    ) {
    }

    public function yookassaWebhook(Request $request): Response
    {
        return $this->yookassaWebhookHandler->handle($request);
    }
}

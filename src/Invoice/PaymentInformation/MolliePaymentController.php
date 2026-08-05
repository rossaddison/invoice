<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\MollieWebhookHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Kept separate from PaymentInformationController deliberately — same
 * reasoning as AdyenPaymentController/GoCardlessPaymentController/
 * RobokassaPaymentController/YookassaPaymentController: the shared
 * controller is already at SonarQube's php:S1448 method-count ceiling (20
 * methods), so a single new webhook action doesn't fit there.
 *
 * Mollie's own `mollieInForm()`/`mollieComplete()` in-form flow stays in
 * PaymentInformationController — only the webhook is new, and moving the
 * existing, already-working flow out isn't worth the churn for a single
 * extra method here.
 */
final class MolliePaymentController
{
    public function __construct(
        private readonly MollieWebhookHandler $mollieWebhookHandler,
    ) {
    }

    public function mollieWebhook(Request $request): Response
    {
        return $this->mollieWebhookHandler->handle($request);
    }
}
